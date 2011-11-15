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
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
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
    const USER_NOLOGIN   = 'NoLogin';
    const USER_OLD_STAFF = 'OldStaffUser';
    const USER_PROJECT   = 'ProjectUser';
    const USER_STAFF     = 'StaffUser';

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

            $extras['session'] = $this->session;
        }

        $this->addRegistryContainer($extras);
    }

    /**
     * Get an array of OrgId => Org Name for all allowed organizations for the current loggedin user
     *
     * @return array
     */
    public function getAllowedOrganizations()
    {
        return $this->db->fetchPairs("SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active = 1 ORDER BY gor_name");
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
            $organizationId = intval(self::$currentUser->getOrganizationId());
        }

        if (! isset($organizations[$organizationId])) {
            $organizations[$organizationId] = $this->_loadClass('Organization', true, array($organizationId));
        }

        return $organizations[$organizationId];
    }

    /**
     * Returns a user object, that may be empty if no user exist.
     *
     * @param string $login_name
     * @param int $organization
     * @return Gems_User_User But ! ->isActive when the user does not exist
     */
    public function getUser($login_name, $organization)
    {
        $defName = $this->getUserClassName($login_name, $organization);

        $definition = $this->_getClass($defName);

        $values = $definition->getUserData($login_name, $organization);

        if (! isset($values['user_active'])) {
            $values['user_active'] = true;
        }

        if (! isset($values['allowedOrgs'])) {
            //Load the allowed organizations
            $values['allowedOrgs'] = $this->getAllowedOrganizations();
        }
        $values['__user_definition'] = $defName;

        return $this->_loadClass('User', true, array($values, $definition));
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
     * @return string
     */
    protected function getUserClassName($login_name, $organization)
    {
        if ((null == $login_name) || (null == $organization)) {
            return 'NoLoginDefinition';
        }
        if ($this->isProjectUser($login_name)) {
            return 'ProjectUserDefinition';
        }

        try {
            $sql = "SELECT gul_user_class FROM gems__user_logins WHERE gul_can_login = 1 AND gul_login = ? AND gul_id_organization = ?";
            if ($class = $this->db->fetchOne($sql, array($login_name, $organization))) {
                return $class . 'Definition';
            }

        } catch (Zend_Db_Exception $e) {
            // Intentional fall through
        }

        // Fail over for pre 1.5 projects
        $sql = "SELECT gsf_id_user FROM gems__staff WHERE gsf_active = 1 AND gsf_login = ? AND gsf_id_organization = ?";

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

            return self::USER_OLD_STAFF . 'Definition';
        }

        return 'NoLoginDefinition';
    }

    protected function isProjectUser($login_name)
    {
        return $this->project->getSuperAdminName() == $login_name;
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
