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
 * @version    $Id$
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
     * Required
     *
     * @var MUtil_Acl
     */
    protected $acl;

    /**
     * Required
     *
     * @var Gems_Util_BasePath
     */
    protected $basepath;

    /**
     * Required
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Required, set in constructor
     *
     * @var Gems_User_UserDefinitionInterface
     */
    protected $definition;

    /**
     * Required
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * Required
     *
     * @var Gems_User_UserLoader
     */
    protected $userLoader;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

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
            // Use the USERS organization, not the one he or she is using currently
			$formValues['organization'] = $this->getBaseOrganizationId();
            $this->definition->afterLogin($this->_authResult, $formValues);
        }
    }

    /**
     * Helper method for the case a user tries to authenticate while he is inactive
     *
     * @param array $params
     * @return boolean
     */
    public function alwaysFalse($params)
    {
        return false;
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

        $formValues['allowed_ip_ranges'] = $this->getAllowedIPRanges();
        $formValues['organization'] = $this->getBaseOrganizationId();

        if ($this->isActive()) {
            $adapter = $this->definition->getAuthAdapter($formValues);
        } else {
            $adapter = new Gems_Auth_Adapter_Callback(array($this,'alwaysFalse'), $formValues['userlogin'], $formValues);
        }

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
        if (! (($this->db instanceof Zend_Db_Adapter_Abstract) && ($this->session instanceof Zend_Session_Namespace))) {
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

        return (boolean) $this->acl && $this->basepath && $this->userLoader;
    }

    /**
     * Returns the list of allowed IP ranges (separated by colon)
     *
     * @return string
     */
    public function getAllowedIPRanges()
    {
        return $this->_getVar('user_allowed_ip_ranges');
    }

    /**
     * Get an array of OrgId => Org Name for all allowed organizations for the current loggedin user
     *
     * @return array
     */
    public function getAllowedOrganizations()
    {

        if (! $this->_hasVar('__allowedOrgs')) {
            $this->refreshAllowedOrganizations();
        }

        return $this->_getVar('__allowedOrgs');
    }

    /**
     * Returns the original (not the current) organization used by this user.
     *
     * @return Gems_User_Organization
     */
    public function getBaseOrganization()
    {
        return $this->userLoader->getOrganization($this->getBaseOrganizationId());
    }

    /**
     * Returns the original (not the current) organization id of this user.
     *
     * @return int
     */
    public function getBaseOrganizationId()
    {
        return $this->_getVar('user_base_org_id');
    }

    /**
     * Returns the organization that is currently used by this user.
     *
     * @return Gems_User_Organization
     */
    public function getCurrentOrganization()
    {
        return $this->userLoader->getOrganization($this->getCurrentOrganizationId());
    }

    /**
     * Returns the organization id that is currently used by this user.
     *
     * @return int
     */
    public function getCurrentOrganizationId()
    {
        $orgId = $this->_getVar('user_organization_id');

        //If not set, read it from the cookie
        if ($this->isCurrentUser() && is_null($orgId)) {
            $orgId = Gems_Cookies::getOrganization(Zend_Controller_Front::getInstance()->getRequest());
        }
        return $orgId;
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
     * Get the array to use for authenticate()
     *
     * @param string $password
     * @return array
     */
    public function getFormValuesForPassword($password)
    {
        return array(
            'userlogin'    => $this->getLoginName(),
            'password'     => $password,
            'organization' => $this->getCurrentOrganizationId());
    }

    /**
     * Returns the full user name (first, prefix, last).
     *
     * @return string
     */
    public function getFullName()
    {
        if (! $this->_getVar('user_name')) {
            $name = ltrim($this->_getVar('user_first_name') . ' ') .
                    ltrim($this->_getVar('user_surname_prefix') . ' ') .
                    $this->_getVar('user_last_name');

            if (! $name) {
                // Use obfuscated login name
                $name = $this->getLoginName();
                $name = substr($name, 0, 3) . str_repeat('*', strlen($name) - 2);
            }

            $this->_setVar('user_name', $name);

            // MUtil_Echo::track($name);
        }

        return $this->_getVar('user_name');
    }

    /**
     * Returns a standard greeting for the current user.
     *
     * @return int
     */
    public function getGreeting()
    {
        if (! $this->_getVar('user_greeting')) {
            $greeting  = array();
            $greetings = $this->util->getTranslated()->getGenderGreeting();

            if (isset($greetings[$this->_getVar('user_gender')])) {
                $greeting[] = $greetings[$this->_getVar('user_gender')];
            }
            if ($this->_getVar('user_last_name')) {
                if ($this->_getVar('user_surname_prefix')) {
                    $greeting[] = $this->_getVar('user_surname_prefix');
                }
                $greeting[] = $this->_getVar('user_last_name');
            } else {
                $name = $this->getLoginName();
                $name = substr($name, 0, 3) . str_repeat('*', strlen($name) - 2);
                $greeting[] = $name;
            }

            $this->_setVar('user_greeting', implode(' ', $greeting));
        }

        return $this->_getVar('user_greeting');
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

            $menuItem = $menu->findController('option', 'change-password');
            // This may not yet be true, but is needed for the redirect.
            $menuItem->set('allowed', true);
            $menuItem->set('visible', true);
        } else {
            $menuItem = $menu->findFirst(array('allowed' => true, 'visible' => true));
        }

        if ($menuItem) {
            // Prevent redirecting to the current page.
            if (! ($menuItem->is('controller', $request->getControllerName()) && $menuItem->is('action', $request->getActionName()))) {
                //Probably a debug statement so commented out MD20120308
                //echo $menuItem->get('label') . '<br/>';

                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoRoute($menuItem->toRouteUrl($request), null, true);
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
     * Returns true if the role of the current user has the given privilege
     *
     * @param string $privilege
     * @return bool
     */
    public function hasPrivilege($privilege)
    {
        return (! $this->acl) || $this->acl->isAllowed($this->getRole(), null, $privilege);
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
     * Is this organization in the list of currently allowed organizations?
     *
     * @param int $organizationId
     * @return boolean
     */
    public function isAllowedOrganization($organizationId)
    {
        $orgs = $this->getAllowedOrganizations();

        return isset($orgs[$organizationId]);
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
     * Returns true when this user is a staff member.
     *
     * @return boolean
     */
    public function isStaff()
    {
        return (boolean) $this->_getVar('user_staff');
    }

    /**
     * Allowes a refresh of the existing list of organizations
     * for this user.
     *
     * @return Gems_User_User (continuation pattern)
     */
    public function refreshAllowedOrganizations()
    {
        // Privilege overrules organizational settings
        if ($this->hasPrivilege('pr.organization-switch')) {
            $orgs = $this->db->fetchPairs("SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active = 1 ORDER BY gor_name");
            natsort($orgs);
        } else {
            $orgs = $this->getBaseOrganization()->getAllowedOrganizations();
        }
        // MUtil_Echo::track($orgs);

        $this->_setVar('__allowedOrgs', $orgs);

        return $this;
    }

    /**
     * Check for password weakness.
     *
     * @param string $password Or null when you want a report on all the rules for this password.
     * @return mixed String or array of strings containing warning messages or nothing
     */
    public function reportPasswordWeakness($password = null)
    {
        if ($this->canSetPassword()) {
            $checker = $this->userLoader->getPasswordChecker();

            $codes[] = $this->getCurrentOrganization()->getCode();
            $codes[] = $this->getRoles();
            $codes[] = $this->_getVar('__user_definition');

            return $checker->reportPasswordWeakness($this, $password, MUtil_Ra::flatten($codes));
        }
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
     * Set the currently selected organization for this user
     *
     * @param mixed $organization Gems_User_Organization or an organization id.
     * @return Gems_User_User (continuation pattern)
     */
    public function setCurrentOrganization($organization)
    {
        if ($organization instanceof Gems_User_Organization) {
            $organizationId = $organization->getId();
        } else {
            $organizationId = $organization;
            $organization = $this->userLoader->getOrganization($organizationId);
        }

        $oldOrganizationId = $this->getCurrentOrganizationId();

        if ($organizationId) {
            if ($organizationId != $oldOrganizationId) {
                $this->_setVar('user_organization_id', $organizationId);

                // Depreciation warning: the settings will be removed in
                // version 1.6 at the latest.
                $this->_setVar('user_organization_name', $organization->getName());
                $this->_setVar('user_style', $organization->getStyle());
                // End depreciation warning

                if ($this->isCurrentUser()) {
                    // Now update the requestcache to change the oldOrgId to the new orgId
                    // Don't do it when the oldOrgId doesn't match
                    if ($requestCache = $this->session->requestCache) {

                        //Create the list of request cache keys that match an organization ID (to be extended)
                        $possibleOrgIds = array(
                            'gr2o_id_organization',
                            'gto_id_organization');

                        foreach ($requestCache as $key => $value) {
                            if (is_array($value)) {
                                foreach ($value as $paramKey => $paramValue) {
                                    if (in_array($paramKey, $possibleOrgIds)) {
                                        if ($paramValue == $oldOrganizationId) {
                                            $requestCache[$key][$paramKey] = $organizationId;
                                        }
                                    }
                                }
                            }
                        }
                        $this->session->requestCache = $requestCache;
                    }
                }

                if (! Gems_Cookies::setOrganization($organizationId, $this->basepath->getBasePath())) {
                    throw new Exception($this->translate->_('Cookies must be enabled for this site.'));
                }
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
        $this->setPasswordResetRequired(false);
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
