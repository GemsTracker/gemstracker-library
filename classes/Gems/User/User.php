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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage user
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * User object that mimicks the old $this->session behaviour
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_User extends MUtil_Registry_TargetAbstract
{
    /**
     *
     * @var Zend_Auth_Result
     */
    protected $_authResult;

    /**
     *
     * @var ArrayObject or Zend_Session_Namespace
     */
    private $_vars;

    /**
     *
     * @var MUtil_Acl
     */
    protected $acl;

    /**
     *
     * @var Gems_User_UserDefinitionInterface
     */
    protected $definition;

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     *
     * @var Gems_User_UserLoader
     */
    protected $userLoader;

    /**
     * Creates the class for this user.
     *
     * @param mixed $settings Array, Zend_Session_Namespace or ArrayObject for this user.
     * @param Gems_User_UserDefinitionInterface $definition The user class definition.
     */
    public function __construct($settings, Gems_User_UserDefinitionInterface $definition)
    {
        if (is_array($settings)) {
            $this->_vars = new ArrayObject($settings);
            $this->_vars->setFlags(ArrayObject::STD_PROP_LIST);
        } else {
            $this->_vars = $settings;
        }
        $this->definition = $definition;
    }

    /**
     * Get a value in whatever store is used by this object.
     *
     * @param string $name
     * @return mixed
     */
    protected function _getVar($name)
    {
        $store = $this->_getVariableStore();

        if ($store instanceof Zend_Session_Namespace) {
            if ($store->__isset($name)) {
                return $store->__get($name);
            }
        } else {
            if ($store->offsetExists($name)) {
                return $store->offsetGet($name);
            }
        }

        return null;
    }

    /**
     * The store currently used.
     *
     * @return ArrayObject or Zend_Session_Namespace
     */
    private function _getVariableStore()
    {
        return $this->_vars;
    }

    /**
     * Checks for existence of a value in whatever store is used by this object.
     *
     * @param string $name
     * @return boolean
     */
    protected function _hasVar($name)
    {
        $store = $this->_getVariableStore();

        if ($store instanceof Zend_Session_Namespace) {
            return $store->__isset($name);
        } else {
            return $store->offsetExists($name);
        }
    }

    /**
     * Sets a value in whatever store is used by this object.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function _setVar($name, $value)
    {
        $store = $this->_getVariableStore();

        if ($store instanceof Zend_Session_Namespace) {
            $store->__set($name, $value);
        } else {
            $store->offsetSet($name, $value);
        }
    }

    /**
     * Sets a value in whatever store is used by this object.
     *
     * @param string $name
     * @return void
     */
    protected function _unsetVar($name)
    {
        $store = $this->_getVariableStore();

        if ($store instanceof Zend_Session_Namespace) {
            $store->__unset($name);
        } else {
            $store->offsetUnset($name, $value);
        }
    }

    /**
     * Perform project specific after login logic here, can also delegate to the user definition
     *
     * @return void
     */
    public function afterLogin($formValues) {
        if (is_callable(array($this->definition, 'afterLogin'))) {
            $this->definition->afterLogin($this->_authResult, $formValues);
        }
    }

    /**
     * Authenticate a users credentials using the submitted form
     *
     * @param array $formValues the array containing all formvalues from the login form
     * @return Zend_Auth_Result
     */
    public function authenticate($formValues)
    {
       $auth = Gems_Auth::getInstance();
       $adapter = $this->definition->getAuthAdapter($formValues);
       $authResult = $auth->authenticate($adapter, $formValues);

       $this->_authResult = $authResult;

       return $authResult;
    }

    /**
     * Return true if a password reset key can be created.
     *
     * @return boolean
     */
    public function canResetPassword()
    {
        return $this->isActive() && $this->definition->canResetPassword($this);
    }

    /**
     * Return true if the password can be set.
     *
     * @return boolean
     */
    public function canSetPassword()
    {
        return $this->definition->canSetPassword();
    }

    /**
     * Check whether a reset key is really linked to this user.
     *
     * @param string The key
     * @return boolean
     */
    public function checkPasswordResetKey($key)
    {
        return $this->definition->checkPasswordResetKey($this, $key);
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if (! $this->session instanceof Zend_Session_Namespace) {
            return false;
        }

        // Checks if this is the current user
        if (! $this->_vars instanceof Zend_Session_Namespace) {
            $sessionStore = $this->session;

            $notCurrent = true;
            foreach (array('user_id', 'user_organization_id') as $key) {
                if ($sessionStore->__isset($key) && $this->_vars->offsetExists($key)) {
                    $notCurrent = $sessionStore->__get($key) != $this->_vars->offsetGet($key);
                } else {
                    $notCurrent = $sessionStore->__isset($key) || $this->_vars->offsetExists($key);
                }

                if ($notCurrent) {
                    break;
                }
            }
            if (! $notCurrent) {
                // When this is the case, use the Zend_Session_Namespace object with the current set values
                // This way changes to this user object are reflected in the CurrentUser object and vice versa.
                $this->setAsCurrentUser();
            }
        }
        return true;
    }

    /**
     * Get an array of OrgId => Org Name for all allowed organizations for the current loggedin user
     *
     * @return array
     */
    public function getAllowedOrganizations()
    {
        return $this->_getVar('allowedOrgs');
    }

    /**
     * Return true if this user has a password.
     *
     * @return boolean
     */
    public function getEmailAddress()
    {
        return $this->_getVar('user_email');
    }

    /**
     * Returns the full user name (first, prefix, last).
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->_getVar('user_name');
    }

    /**
     * Returns the group number of the current user.
     *
     * @return int
     */
    public function getGroup()
    {
        return $this->_getVar('user_group');
    }

    /**
     * The locale set for this user..
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->_getVar('user_locale');
    }

    /**
     *
     * @return string
     */
    public function getLoginName()
    {
        return $this->_getVar('user_login');
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId()
    {
        $orgId = $this->_getVar('user_organization_id');

        //If not set, read it from the cookie
        if (is_null($orgId)) {
            $orgId = Gems_Cookies::getOrganization(Zend_Controller_Front::getInstance()->getRequest());
        }
        return $orgId;
    }

    /**
     * Gets the (optional) organization code.
     *
     * @return string
     */
    public function getOrganizationCode()
    {
        $organizationId = $this->getOrganizationId();

        return $this->userLoader->getOrganization($organizationId)->getCode();
    }

    /**
     * Return a password reset key
     *
     * @return string
     */
    public function getPasswordResetKey()
    {
        return $this->definition->getPasswordResetKey($this);
    }

    /**
     * Returns the current user role.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->_getVar('user_role');
    }

    /**
     * Returns the current user role.
     *
     * @return string
     */
    public function getRoles()
    {
        return $this->acl->getRoleAndParents($this->getRole());
    }

    /**
     * Returns the user id, that identifies this user within this installation.
     *
     * One user id might be connected to multiple logins for multiple organizations.
     *
     * YES! This is the one you need, not getUserLoginId().
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->_getVar('user_id');
    }

    /**
     * Use ONLY in User package.
     *
     * Returns the User package user id, that is unique for each login / organization id
     * combination, but does not directly identify this person.
     *
     * In other words, this is not the id you use to track who changed what. It is only
     * used by parts of the User package.
     *
     * @return int
     */
    public function getUserLoginId()
    {
        if ($this->_hasVar('user_login_id')) {
            return $this->_getVar('user_login_id');
        }
        return 0;
    }

    /**
     * Redirects the user to his/her start page.
     *
     * @param Gems_Menu $menu
     * @param Zend_Controller_Request_Abstract $request
     * @return Gems_Menu_SubMenuItem
     */
    public function gotoStartPage(Gems_Menu $menu, Zend_Controller_Request_Abstract $request)
    {
        if ($this->isPasswordResetRequired()) {
            // Set menu OFF
            $menu->setVisible(false);

            $menuItem = $menu->findFirst(array($request->getControllerKey() => 'option', $request->getActionKey() => 'change-password'));
            // This may not yet be true, but is needed for the redirect.
            $menuItem->set('allowed', true);
            $menuItem->set('visible', true);
        } else {
            $menuItem = $menu->findFirst(array('allowed' => true, 'visible' => true));
        }

        if ($menuItem) {
            // Prevent redirecting to the current page.
            if (! ($menuItem->is('controller', $request->getControllerName()) && $menuItem->is('action', $request->getActionName()))) {
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoRoute($menuItem->toRouteUrl($request));
            }
        }

        return $menuItem;
    }

    /**
     * Return true if this user has a password.
     *
     * @return boolean
     */
    public function hasEmailAddress()
    {
        return $this->_hasVar('user_email');
    }

    /**
     * Return true if this user has a password.
     *
     * @return boolean
     */
    public function hasPassword()
    {
        return $this->definition->hasPassword($this);
    }

    /**
     *
     * @return boolean True when a user can log in.
     */
    public function isActive()
    {
        return (boolean) $this->_getVar('user_active');
    }

    /**
     * Checks if this user is the current user
     *
     * @return boolean
     */
    public function isCurrentUser()
    {
        return $this->_vars instanceof Zend_Session_Namespace;
    }

    /**
     * True when this user requires a logout after answering a survey
     *
     * @return boolean
     */
    public function isLogoutOnSurvey()
    {
        return (boolean) $this->_getVar('user_logout');
    }

    /**
     * True when this user must enter a new password.
     *
     * @return boolean
     */
    public function isPasswordResetRequired()
    {
        return (boolean) $this->_getVar('user_password_reset');
    }

    /**
     * Set this user as the current user.
     *
     * This means that the data about this user will be stored in a session.
     *
     * @param boolean $signalLoader Do not set, except from UserLoader
     * @return Gems_User_User (continuation pattern)
     */
    public function setAsCurrentUser($signalLoader = true)
    {
        // Get the current variables
        $oldStore = $this->_getVariableStore();

        // When $oldStore is a Zend_Session_Namespace, then this user is already the current user.
        if (! $this->isCurrentUser()) {
            $this->userLoader->unsetCurrentUser();

            $this->_vars = $this->session;

            foreach ($oldStore as $name => $value) {
                $this->_vars->__set($name, $value);
            }

            if ($signalLoader) {
                $this->userLoader->setCurrentUser($this);
            }
        }

        return $this;
    }


    /**
     * Set the password, if allowed for this user type.
     *
     * @param string $password
     * @return Gems_User_User (continuation pattern)
     */
    public function setPassword($password)
    {
        $this->definition->setPassword($this, $password);
        return $this;
    }

    /**
     *
     * @param boolean $reset
     * @return Gems_User_User  (continuation pattern)
     */
    public function setPasswordResetRequired($reset = true)
    {
        $this->_setVar('user_password_reset', (boolean) $reset);
        return $this;
    }

    /**
     * Unsets this user as the current user.
     *
     * This means that the data about this user will no longer be stored in a session.
     *
     * @param boolean $signalLoader Do not set, except from UserLoader
     * @return Gems_User_User (continuation pattern)
     */
    public function unsetAsCurrentUser($signalLoader = true)
    {
        // When $oldStore is a Zend_Session_Namespace, then this user is already the current user.
        if ($this->isCurrentUser()) {
            // Get the current variables
            $oldStore = $this->_vars;

            $this->_vars = new ArrayObject();
            $this->_vars->setFlags(ArrayObject::STD_PROP_LIST);

            foreach ($oldStore as $name => $value) {
                $this->_vars->offsetSet($name, $value);
            }

            // Clean up what is there now in the session.
            $oldStore->unsetAll();

            if ($signalLoader) {
                // Signal the loader
                $this->userLoader->unsetCurrentUser();
            }
        }

        return $this;
    }
}
