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
     * Get the currently loggin in user
     *
     * @return Gems_User_User
     */
    public final function getCurrentUser()
    {
        if (! self::$currentUser) {
            if ($this->session->__isset('__user_definition')) {
                $defName = $this->session->__get('__user_definition');
                self::$currentUser = $this->_loadClass('User', true, array($this->session, $this->_getClass($defName)));
            } else {
                self::$currentUser = $this->getUser(null, null);
                self::$currentUser->setAsCurrentUser();
            }
        }

        return self::$currentUser;
    }

    /**
     * Returns an organization object, initiated from the database or from
     * self::$_noOrganization when the database does not yet exist.
     *
     * @param int $organizationId Optional, uses current user when empty
     * @return Gems_User_Organization
     */
    public function getOrganization($organizationId = null)
    {
        static $organizations = array();

        if (null === $organizationId) {
            $organizationId = intval(self::getCurrentUser()->getCurrentOrganizationId());
        }

        if (! isset($organizations[$organizationId])) {
            $organizations[$organizationId] = $this->_loadClass('Organization', true, array($organizationId));
        }

        return $organizations[$organizationId];
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
     * Returns a user object, that may be empty if no user exist.
     *
     * @param string $login_name
     * @param int $organization
     * @return Gems_User_User But ! ->isActive when the user does not exist
     */
    public function getUser($login_name, $currentOrganization)
    {
        list($defName, $userOrganization) = $this->getUserClassInfo($login_name, $currentOrganization);
        // MUtil_Echo::track($defName, $userOrganization);

        $definition = $this->getUserDefinition($defName);

        $values = $definition->getUserData($login_name, $userOrganization);
        // MUtil_Echo::track($defName, $login_name, $userOrganization, $values);

        if (! isset($values['user_active'])) {
            $values['user_active'] = true;
        }

        $values['__user_definition'] = $defName;

        $user = $this->_loadClass('User', true, array($values, $definition));

        $user->setCurrentOrganization($currentOrganization);

        return $user;
    }

    /**
     * Retrieve a userdefinition, so we can check it's capabilities without
     * instantiating a user
     *
     * @param string $userClassName
     * @return Gems_User_UserDefinitionInterface
     */
    public function getUserDefinition($userClassName)
    {
        $definition = $this->_getClass($userClassName);

        return $definition;
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
     * @return array Containing definitionName, organizationId
     */
    protected function getUserClassInfo($login_name, $organization)
    {
        if ((null == $login_name) || (null == $organization)) {
            return array(self::USER_NOLOGIN . 'Definition', $organization);
        }
        if ($this->isProjectUser($login_name)) {
            return array(self::USER_PROJECT . 'Definition', $organization);
        }

        try {
            $sql = "SELECT CONCAT(gul_user_class, 'Definition'), gul_id_organization
                FROM gems__user_logins INNER JOIN gems__organizations ON gor_id_organization = gul_id_organization
                WHERE gor_active = 1 AND
                    gul_can_login = 1 AND
                    gul_login = ? AND
                    gul_id_organization = ?
                LIMIT 1";

            $params[] = $login_name;
            $params[] = $organization;
            // MUtil_Echo::track($sql, $params);

            $row = $this->db->fetchRow($sql, $params, Zend_Db::FETCH_NUM);

            if (! $row) {
                // Try to get see if this is another allowed organization for this user
                $sql = "SELECT CONCAT(gul_user_class, 'Definition'), gul_id_organization
                    FROM gems__user_logins INNER JOIN gems__organizations ON gor_id_organization != gul_id_organization
                    WHERE gor_active = 1 AND
                        gul_can_login = 1 AND
                        gul_login = ? AND
                        gor_id_organization = ? AND
                        gor_accessible_by LIKE CONCAT('%:', gul_id_organization, ':%')
                    LIMIT 1";

                // MUtil_Echo::track($sql, $params);

                $row = $this->db->fetchRow($sql, $params, Zend_Db::FETCH_NUM);
            }

            if ($row) {
                // MUtil_Echo::track($row);
                return $row;
            }

        } catch (Zend_Db_Exception $e) {
            // Intentional fall through
        }

        // Fail over for pre 1.5 projects
        //
        // No login as other organization for first login
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

            return array(self::USER_OLD_STAFF . 'Definition', $organization);
        }

        return array(self::USER_NOLOGIN . 'Definition', $organization);
    }

    protected function isProjectUser($login_name)
    {
        return $this->project->getSuperAdminName() == $login_name;
    }

    /**
     * Check for password weakness.
     *
     * @param Gems_User_User $user The user for e.g. name checks
     * @param string $password
     * @return mixed String or array of strings containing warning messages
     */
    public function reportPasswordWeakness(Gems_User_User $user, $password)
    {
        $checker = $this->_getClass('passwordChecker');

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
