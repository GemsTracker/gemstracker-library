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
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
abstract class Gems_User_UserAbstract extends Gems_Registry_TargetAbstract implements Gems_User_UserInterface
{
    const SESSION_CLASS_NAME      = '__className';
    const SESSION_LOGIN_NAME      = '__loginName';
    const SESSION_ORGANIZATION_ID = '__organizationId';
    /**
     *
     * @var string
     */
    private $_login_name;

    /**
     *
     * @var int
     */
    private $_organization_id;

    /**
     *
     * @var mixed ArrayObject or Zend_Session_Namespace
     */
    private $_vars;

    /**
     *
     * @var MUtil_Acl
     */
    protected $acl;

    /**
     * The escort session.
     *
     * For compatibility reasons only.
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     * Creates this class for the current user.
     *
     * @param string $login_name
     * @param int $organization Only used when more than one organization uses this $login_name
     */
    public function __construct($login_name, $organization)
    {
        $this->_login_name = $login_name;
        $this->_organization_id = $organization;
    }

    /**
     * Returns the session namespace containing user data.
     *
     * @staticvar Zend_Session_Namespace $session
     * @return Zend_Session_Namespace
     */
    private static function _getSessionStore()
    {
        static $session;

        if (! $session) {
            $session = new Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.userdata', true);
        }

        return $session;
    }

    /**
     * The store currently used.
     *
     * @return mixed ArrayObject or Zend_Session_Namespace
     */
    private function _getVariableStore()
    {
        if (! $this->_vars) {
            $sessionStore = self::_getSessionStore();

            if ($sessionStore->__isset(self::SESSION_CLASS_NAME) &&
                    ($sessionStore->__get(self::SESSION_CLASS_NAME) == get_class($this)) &&
                    $sessionStore->__isset(self::SESSION_LOGIN_NAME) &&
                    ($sessionStore->__get(self::SESSION_LOGIN_NAME) == $this->_login_name) &&
                    $sessionStore->__isset(self::SESSION_ORGANIZATION_ID) &&
                    ($sessionStore->__get(self::SESSION_ORGANIZATION_ID) == $this->_organization_id)) {

                $this->_vars = $sessionStore;

            } else {
                $this->_vars = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
                $this->_vars->offsetSet(self::SESSION_CLASS_NAME, get_class($this));
                $this->_vars->offsetSet(self::SESSION_LOGIN_NAME, $this->_login_name);
                $this->_vars->offsetSet(self::SESSION_ORGANIZATION_ID, $this->_organization_id);
            }

        }

        return $this->_vars;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required variables are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->_getVariableStore() instanceof Zend_Session_Namespace) {
            $init = true;
        } else {
            $init = $this->initVariables($this->_login_name, $this->_organization_id);
        }

        return $init && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Returns the current user from the session store or false if there is no user.
     *
     * Use Gems_User_UserLoader->getCurrentUser() when you always want to have a user object returned.
     *
     * @see Gems_User_UserLoader->getCurrentUser()
     *
     * @staticvar Gems_User_UserAbstract $currentUser
     * @return Gems_User_UserAbstract or false if there is no current user.
     */
    public static function getCurrentUser()
    {
        static $currentUser;

        if (null === $currentUser) {
            $store = self::_getSessionStore();

            if ($className = $store->__get(self::SESSION_CLASS_NAME)) {
                $currentUser = new $className($store->__get(self::SESSION_LOGIN_NAME), $store->__get(self::SESSION_ORGANIZATION_ID));
            } else {
                $currentUser = false;
            }
        }

        return $currentUser;
    }

    /**
     *
     * @return string
     */
    protected function getLoginName()
    {
        return $this->_login_name;
    }

    /**
     *
     * @return int
     */
    protected function getOrganizationId()
    {
        return $this->_organization_id;
    }

    /**
     * Returns the (current) role of this user.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->getVar('user_role');
    }

    /**
     * Get a value in whatever store is used by this object.
     *
     * @param string $name
     * @return mixed
     */
    protected function getVar($name)
    {
        $store = $this->_getVariableStore();

        if ($store instanceof Zend_Session_Namespace) {
            if ($store->__isset($name)) {
                return $store->__get($name);
            }
        } else {
            if ($store->offsetExists($name)) {
                return $store->offsetSet($name);
            }
        }
        return null;
    }

    /**
     * Returns true if the role of this user has the given privilege.
     *
     * @param string $privilege
     * @return bool
     */
    public function hasPrivilege($privilege)
    {
        return (! $this->acl) || $this->acl->isAllowed($this->getRole(), null, $privilege);
    }

    /**
     * Intialize the values for this user.
     *
     * Skipped when the user is the active user and is stored in the session.
     *
     * @param string $login_name
     * @param int $organization Only used when more than one organization uses this $login_name
     * @return boolean False when the object could not load.
     */
    abstract protected function initVariables($login_name, $organization);

    /**
     * Set this user as the current user.
     *
     * This means that the data about this user will be stored in a session.
     *
     * @return Gems_User_UserAbstract
     */
    public function setAsCurrentUser()
    {
        // Get the current variables
        $oldStore = $this->_getVariableStore();

        // When $oldStore is a Zend_Session_Namespace, then this user is already the current user.
        if (! $oldStore instanceof Zend_Session_Namespace) {
            $this->_vars = self::_getSessionStore();

            // Clean up what is there now.
            $this->_vars->unsetAll();

            foreach ($oldStore as $name => $value) {
                $this->_vars->__set($name, $value);
            }

            // Copy to session
            // $this->session->unsetAll();
//            $this->session->user_id;
            $this->session->user_login = $this->getLoginName();
//            $this->session->user_email;
//            $this->session->user_group;
//            $this->session->user_style;
//            $this->session->user_locale;
//            $this->session->user_logout;
//            $this->session->user_name;
            $this->session->user_role = $this->getRole();
            $this->session->user_organization_id = $this->getOrganizationId();
//            $this->session->user_organization_name;
        }

        return $this;
    }

    /**
     * Sets the (current) role of this user.
     *
     * @param string $value Role
     * @return Gems_User_UserAbstract (continuation pattern)
     */
    protected function setRole($value)
    {
        $this->setVar('user_role', $value);

        return $this;
    }

    /**
     * Store a value in whatever store is used by this object.
     *
     * @param string $name
     * @param mixed $value
     * @return Gems_User_UserAbstract (continuation pattern)
     */
    protected function setVar($name, $value)
    {
        $store = $this->_getVariableStore();

        if ($store instanceof Zend_Session_Namespace) {
            $store->__set($name, $value);
        } else {
            $store->offsetSet($name, $value);
        }
        return $this;
    }
}
