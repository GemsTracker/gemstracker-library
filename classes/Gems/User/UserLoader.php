<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Loads users.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_User_UserLoader extends Gems_Loader_TargetLoaderAbstract
{
    /**
     * User class constants
     */
    const USER_NOLOGIN    = 'NoLogin';
    const USER_OLD_STAFF  = 'OldStaffUser';
    const USER_PROJECT    = 'ProjectUser';
    const USER_RESPONDENT = 'RespondentUser';
    const USER_STAFF      = 'StaffUser';

    /**
     * When true Respondent members can use their e-mail address as login name
     * @var boolean
     */
    public $allowRespondentEmailLogin = true;

    /**
     * When true Staff members can use their e-mail address as login name
     * @var boolean
     */
    public $allowStaffEmailLogin = true;

    /**
     *
     * @var Zend_Cache_Core
     */
    protected $cache;

    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'User';

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     * @var Zend_Translate_Adapter
     */
    protected $translate;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * There can be only one, current user that is.
     *
     * @var Gems_User_User
     */
    protected static $currentUser;

    /**
     * Session storage of loaded organizations.
     *
     * @var Zend_Session_Namespace
     */
    protected static $organizationStore;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        // Make sure Gems_User_User gets userLoader variable.
        $extras['userLoader'] = $this;

        // Make sure that this code keeps working when _initSession
        // is removed from GemsEscort
        if (! $this->session instanceof Zend_Session_Namespace) {
            $this->session = new Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.session');

            $idleTimeout = $this->project->getSessionTimeout();

            $this->session->setExpirationSeconds($idleTimeout);

            $extras['session'] = $this->session;
        }

        $this->addRegistryContainer($extras);
    }

    /**
     * Returns a user object, that may be empty if no user exist.
     *
     * @param string $login_name
     * @param int $organization
     * @param string $userClassName
     * @param int $userId The person creating the user.
     * @return Gems_User_User Newly created
     */
    public function createUser($login_name, $organization, $userClassName, $userId)
    {
        $now = new Zend_Db_Expr('CURRENT_TIMESTAMP');

        $values['gul_user_class'] = $userClassName;
        $values['gul_can_login']  = 1;
        $values['gul_changed']    = $now;
        $values['gul_changed_by'] = $userId;

        $select = $this->db->select();
        $select->from('gems__user_logins', array('gul_id_user'))
                ->where('gul_login = ?', $login_name)
                ->where('gul_id_organization = ?', $organization)
                ->limit(1);

        // Update class definition if it already exists
        if ($login_id = $this->db->fetchOne($select)) {
            $where = implode(' ', $select->getPart(Zend_Db_Select::WHERE));
            $this->db->update('gems__user_logins', $values, $where);

        } else {
            $values['gul_login']           = $login_name;
            $values['gul_id_organization'] = $organization;
            $values['gul_created']         = $now;
            $values['gul_created_by']      = $userId;

            $this->db->insert('gems__user_logins', $values);
        }

        return $this->getUser($login_name, $organization);
    }

    /**
     * Makes sure default values are set for a user
     *
     * @param array $values
     * @param Gems_User_UserDefinitionInterface $definition
     * @param string $defName Optional
     * @return array
     */
    public function ensureDefaultUserValues(array $values, Gems_User_UserDefinitionInterface $definition, $defName = null)
    {
        if (! isset($values['user_active'])) {
            $values['user_active'] = true;
        }
        if (! isset($values['user_staff'])) {
            $values['user_staff'] = $definition->isStaff();
        }

        if ($defName) {
            $values['__user_definition'] = $defName;
        }

        return $values;
    }

    /**
     * Get userclass / description array of available UserDefinitions for respondents
     *
     * @return array
     */
    public function getAvailableRespondentDefinitions()
    {
        $definitions = array(
            self::USER_RESPONDENT => $this->translate->_('Db storage')
        );

        return $definitions;
    }

    /**
     * Get userclass / description array of available UserDefinitions for staff
     *
     * @return array
     */
    public function getAvailableStaffDefinitions()
    {
        $definitions = array(
            self::USER_STAFF => $this->translate->_('Db storage'),
            'RadiusUser'     => $this->translate->_('Radius storage')
        );

        return $definitions;
    }

    /**
     * Returns a change password form for this user
     *
     * @param Gems_user_User $user
     * @param mixed $args_array MUtil_Ra::args array for LoginForm initiation.
     * @return Gems_User_Form_ChangePasswordForm
     */
    public function getChangePasswordForm($user, $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), array('user' => 'Gems_User_User'));

        $form = $this->_loadClass('Form_ChangePasswordForm', true, array($args));

        return $form;
    }

    /**
     * Get the currently loggin in user
     *
     * @return Gems_User_User
     */
    public final function getCurrentUser()
    {
        if (! self::$currentUser) {
            if ($this->session->__isset('__user_definition')) {
                $defName = $this->session->__get('__user_definition');

                // Check for during upgrade. Remove for version 1.6
                if (substr($defName, -10, 10) != 'Definition') {
                    $defName .= 'Definition';
                }

                self::$currentUser = $this->_loadClass('User', true, array($this->session, $this->_getClass($defName)));
            } else {
                self::$currentUser = $this->getUser(null, null);
                self::$currentUser->setAsCurrentUser();
            }
        }

        return self::$currentUser;
    }

    /**
     * Returns a login form
     *
     * @param mixed $args_array MUtil_Ra::args array for LoginForm initiation.
     * @return Gems_User_Form_LoginForm
     */
    public function getLoginForm($args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        $form = $this->_loadClass('Form_LoginForm', true, array($args));

        return $form;
    }

    /**
     * Returns an organization object, initiated from the database or from
     * self::$_noOrganization when the database does not yet exist.
     *
     * @param int $organizationId Optional, uses current user or url when empty
     * @return Gems_User_Organization
     */
    public function getOrganization($organizationId = null)
    {
        static $organizations = array();

        if (null === $organizationId) {
            $user = $this->getCurrentUser();

            if (! $user->isActive()) {
                $organizationId = $this->getOrganizationIdByUrl();
            }

            if (! $organizationId) {
                $organizationId = intval($user->getCurrentOrganizationId());
            }
        }

        if (! isset($organizations[$organizationId])) {
            $organizations[$organizationId] = $this->_loadClass('Organization', true, array($organizationId));
        }

        return $organizations[$organizationId];
    }

    /**
     * Returns the current organization according to the current site url.
     *
     * @static array $url An array of url => orgId values
     * @return int An organization id or null
     */
    public function getOrganizationIdByUrl()
    {
        static $urls;

        if (! is_array($urls)) {
            if ($this->cache) {
                $cacheId = GEMS_PROJECT_NAME . '__' . get_class($this) . '__organizations_url';
                $urls = $this->cache->load($cacheId);
            } else {
                $cacheId = false;
            }

            if (! $urls) {
                $data = $this->db->fetchPairs("SELECT gor_id_organization, gor_url_base FROM gems__organizations WHERE gor_active=1 AND gor_url_base IS NOT NULL");
                $urls = array();
                foreach ($data as $orgId => $urlsBase) {
                    foreach (explode(' ', $urlsBase) as $url) {
                        if ($url) {
                            $urls[$url] = $orgId;
                        }
                    }
                }

                if ($cacheId) {
                    $this->cache->save($urls, $cacheId, array('organization', 'organizations'));
                }
            }
            // MUtil_Echo::track($urls);
        }

        $current = $this->util->getCurrentURI();

        if (isset($urls[$current])) {
            return $urls[$current];
        }
    }

    /**
     * Get password weakness checker.
     *
     * @return Gems_User_PasswordChecker
     */
    public function getPasswordChecker()
    {
        return $this->_getClass('passwordChecker');
    }

    /**
     * Returns a reset form for handling both the incoming request and the outgoing reset request
     *
     * @param mixed $args_array MUtil_Ra::args array for LoginForm initiation.
     * @return Gems_User_Form_ResetForm
     */
    public function getResetForm($args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        if (isset($args['description'])) {
            $args['description'] = sprintf($args['description'], $this->project->getName());
        }

        $form = $this->_loadClass('Form_ResetForm', true, array($args));

        return $form;
    }

    /**
     * Returns a user object, that may be empty if no user exist.
     *
     * @param string $login_name
     * @param int $currentOrganization
     * @return Gems_User_User But ! ->isActive when the user does not exist
     */
    public function getUser($login_name, $currentOrganization)
    {
        $user = $this->getUserClass($login_name, $currentOrganization);

        // Check: can the user log in as this organization, if not load non-existing user
        $orgs = $user->getAllowedOrganizations();
        if (! isset($orgs[$currentOrganization])) {
            $user = $this->loadUser(self::USER_NOLOGIN, $currentOrganization, $login_name);
        }

        $user->setCurrentOrganization($currentOrganization);

        return $user;
    }

    /**
     * Get a staff user using the $staff_id
     *
     * @param int $staff_id
     * @return Gems_User_User But ! ->isActive when the user does not exist
     */
    public function getUserByStaffId($staff_id)
    {
        $data = $this->db->fetchRow("SELECT gsf_login, gsf_id_organization FROM gems__staff WHERE gsf_id_user = ?", $staff_id);

        // MUtil_Echo::track($data);
        if (false == $data) {
            $data = array('gsf_login' => null, 'gsf_id_organization' => null);
        }

        return $this->getUser($data['gsf_login'], $data['gsf_id_organization']);
    }

    /**
     * Returns the name of the user definition class of this user.
     *
     * @param string $login_name
     * @param int $organization
     * @return Gems_User_User But ! ->isActive when the user does not exist
     */
    protected function getUserClass($login_name, $organization)
    {
        if ((null == $login_name) || (null == $organization)) {
            return $this->loadUser(self::USER_NOLOGIN, $organization, $login_name);
        }
        if ($this->isProjectUser($login_name)) {
            return $this->loadUser(self::USER_PROJECT, $organization, $login_name);
        }

        try {
            $select = $this->getUserClassSelect($login_name, $organization);

            if ($row = $this->db->fetchRow($select, null, Zend_Db::FETCH_NUM)) {
                // MUtil_Echo::track($row);
                return $this->loadUser($row[0], $row[1], $row[2]);
            }

        } catch (Zend_Db_Exception $e) {
            // Intentional fall through
        }

        // Fail over for pre 1.5 projects
        //
        // No login as other organization or with e-mail possible for first login
        $sql = "SELECT gsf_id_user
            FROM gems__staff INNER JOIN
                    gems__organizations ON gsf_id_organization = gor_id_organization
                    WHERE gor_active = 1 AND gsf_active = 1 AND gsf_login = ? AND gsf_id_organization = ?";

        if ($user_id = $this->db->fetchOne($sql, array($login_name, $organization))) {
            // Move user to new staff.
            $values['gul_login']           = $login_name;
            $values['gul_id_organization'] = $organization;
            $values['gul_user_class']      = self::USER_OLD_STAFF; // Old staff as password is still in gems__staff
            $values['gul_can_login']       = 1;
            $values['gul_changed']         = new Zend_Db_Expr('CURRENT_TIMESTAMP');
            $values['gul_changed_by']      = $user_id;
            $values['gul_created']         = new Zend_Db_Expr('CURRENT_TIMESTAMP');
            $values['gul_created_by']      = $user_id;

            try {
                $this->db->insert('gems__user_logins', $values);
            } catch (Zend_Db_Exception $e) {
                // Fall through as this does not work if the database upgrade did not run
                // MUtil_Echo::r($e);
            }

            return $this->loadUser(self::USER_OLD_STAFF, $organization, $login_name);
        }

        return $this->loadUser(self::USER_NOLOGIN, $organization, $login_name);
    }

    /**
     * Returns a select statement to find a corresponding user.
     *
     * @param string $login_name
     * @param int $organization
     * @return Zend_Db_Select
     */
    protected function getUserClassSelect($login_name, $organization)
    {
        $select = $this->db->select();

        $select->from('gems__user_logins', array("gul_user_class", 'gul_id_organization', 'gul_login'))
                ->from('gems__organizations', array())
                ->where('gor_active = 1')
                ->where('gul_can_login = 1')
                ->where('gor_id_organization = ?', $organization)
                ->order("CASE WHEN gor_id_organization = gul_id_organization THEN 1 WHEN gor_accessible_by LIKE CONCAT('%:', gul_id_organization, ':%') THEN 2 ELSE 3 END");

        $ids[] = 'gul_login';

        if ($this->allowStaffEmailLogin) {
            $select->joinLeft('gems__staff', 'gul_login = gsf_login AND gul_id_organization = gsf_id_organization', array());
            $ids[] = 'gsf_email';
        }
        if ($this->allowRespondentEmailLogin) {
            $select->joinLeft('gems__respondent2org', 'gul_login = gr2o_patient_nr AND gul_id_organization = gr2o_id_organization', array())
                ->joinLeft('gems__respondents', 'gr2o_id_user = grs_id_user', array());
            $ids[] = 'grs_email';
        }
        // Add search fields
        $select->where('(' . implode(' = ? OR ', $ids) . ' = ?)', $login_name);

        // MUtil_Echo::track($select->__toString());

        return $select;
    }

    /**
     * Retrieve a userdefinition, so we can check it's capabilities without
     * instantiating a user.
     *
     * @param string $userClassName
     * @return Gems_User_UserDefinitionInterface
     */
    public function getUserDefinition($userClassName)
    {
        $definition = $this->_getClass($userClassName . 'Definition');

        return $definition;
    }

    /**
     * Check: is this user the super user defined
     * in project.ini?
     *
     * @param string $login_name
     * @return boolean
     */
    protected function isProjectUser($login_name)
    {
        return $this->project->getSuperAdminName() == $login_name;
    }

    /**
     * Returns a loaded user object
     *
     * @param string $defName
     * @param int $userOrganization
     * @param string $userName
     * @return Gems_User_User But ! ->isActive when the user does not exist
     */
    protected function loadUser($defName, $userOrganization, $userName)
    {
        $definition = $this->getUserDefinition($defName);

        $values = $definition->getUserData($userName, $userOrganization);
        // MUtil_Echo::track($defName, $login_name, $userOrganization, $values);

        $values = $this->ensureDefaultUserValues($values, $definition, $defName);
        // MUtil_Echo::track($values, $userName, $userOrganization, $defName);

        return $this->_loadClass('User', true, array($values, $definition));
    }

    /**
     * Check for password weakness.
     *
     * @param Gems_User_User $user The user for e.g. name checks
     * @param string $password Or null when you want a report on all the rules for this password.
     * @return mixed String or array of strings containing warning messages
     */
    public function reportPasswordWeakness(Gems_User_User $user, $password = null)
    {
        return $user->reportPasswordWeakness($password);
    }

    /**
     * Sets a new user as the current user.
     *
     * @param Gems_User_User $user
     * @return Gems_User_UserLoader (continuation pattern)
     */
    public function setCurrentUser(Gems_User_User $user)
    {
        if ($user !== self::$currentUser) {
            $this->unsetCurrentUser();
            self::$currentUser = $user;

            // Double check in case this function was used as original
            // start for setting the user.
            if (! $user->isCurrentUser()) {
                $user->setAsCurrentUser(true);
            }
        }

        return $this;
    }

    /**
     * Removes the current user
     *
     * @return Gems_User_UserLoader (continuation pattern)
     */
    public function unsetCurrentUser()
    {
        // Remove if the currentUser still sees itself as the current user.
        if ((self::$currentUser instanceof Gems_User_User) && self::$currentUser->isCurrentUser()) {
            self::$currentUser->unsetAsCurrentUser(false);
        }
        self::$currentUser = null;
        return $this;
    }
}
