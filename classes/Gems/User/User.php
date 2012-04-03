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
     * Sets number failed accounts that trigger a block
     *
     * @var int
     */
    protected $failureBlockCount = 6;

    /**
     * Sets number of seconds until a previous failed login can be ignored
     *
     * @var int
     */
    protected $failureIgnoreTime = 600;

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
     * Use Zend_Auth for authentication
     *
     * Warning: Zend_Auth contains only a partial ID of the current user, the base organization is missing
     *
     * @var boolean
     */
    protected $useZendAuth = false;

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
     * Process everything after authentication.
     *
     * @param Zend_Auth_Result $result
     */
    protected function afterAuthorization(Zend_Auth_Result $result)
    {
        try {
            $select = $this->db->select();
            $select->from('gems__user_login_attempts', array('gula_failed_logins', 'gula_last_failed', 'gula_block_until', 'UNIX_TIMESTAMP() - UNIX_TIMESTAMP(gula_last_failed) AS since_last'))
                    ->where('gula_login = ?', $this->getLoginName())
                    ->where('gula_id_organization = ?', $this->getCurrentOrganizationId())
                    ->limit(1);

            $values = $this->db->fetchRow($select);

            // The first login attempt
            if (! $values) {
                $values['gula_login']           = $this->getLoginName();
                $values['gula_id_organization'] = $this->getCurrentOrganizationId();
                $values['gula_failed_logins']   = 0;
                $values['gula_last_failed']     = null;
                $values['gula_block_until']     = null;
                $values['since_last']           = $this->failureBlockCount + 1;
            }

            if ($result->isValid()) {
                // Reset login failures
                $values['gula_failed_logins']   = 0;
                $values['gula_last_failed']     = null;
                $values['gula_block_until']     = null;

            } else {

                // Reset the counters when the last login was longer ago than the delay factor
                if ($values['since_last'] > $this->failureIgnoreTime) {
                    $values['gula_failed_logins'] = 1;
                } else {
                    $values['gula_failed_logins'] += 1;
                }

                // If block is already set
                if ($values['gula_block_until']) {
                    // Do not change it anymore
                    unset($values['gula_block_until']);

                } else {
                    // Only set the block when needed
                    if ($this->failureBlockCount <= $values['gula_failed_logins']) {
                        $values['gula_block_until'] = new Zend_Db_Expr('DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ' . $this->failureIgnoreTime . ' SECOND)');
                    }
                }

                // Always record the last fail
                $values['gula_last_failed']     = new Zend_Db_Expr('CURRENT_TIMESTAMP');

                // Response gets slowly slower
                $sleepTime = min($values['gula_failed_logins'] - 1, 10) * 2;
                sleep($sleepTime);
                // MUtil_Echo::track($sleepTime, $values, $result->getMessages());
            }

            // Value not saveable
            unset($values['since_last']);

            if (isset($values['gula_login'])) {
                $this->db->insert('gems__user_login_attempts', $values);
            } else {
                $where = $this->db->quoteInto('gula_login = ? AND ', $this->getLoginName());
                $where .= $this->db->quoteInto('gula_id_organization = ?', $this->getCurrentOrganizationId());
                $this->db->update('gems__user_login_attempts', $values, $where);
            }

        } catch (Zend_Db_Exception $e) {
            // Fall through as this does not work if the database upgrade did not yet run
            // MUtil_Echo::r($e);
        }

    }

    /**
     * Authenticate a users credentials using the submitted form
     *
     * @param string $password The password to test
     * @return Zend_Auth_Result
     */
    public function authenticate($password)
    {
        if ($this->useZendAuth) {
            $zendAuth = Zend_Auth::getInstance();
        }
        $auths = $this->loadAuthorizers($password);

        foreach ($auths as $result) {
            if (is_callable($result)) {
                $result = call_user_func($result);
            }

            if ($result instanceof Zend_Auth_Adapter_Interface) {
                if ($this->useZendAuth) {
                    $result = $zendAuth->authenticate($result);
                } else {
                    $result = $result->authenticate();
                }
            }

            if ($result instanceof Zend_Auth_Result) {
                if (! $result->isValid()) {
                    break;
                }
            } else {
                if (true === $result) {
                    $result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->getLoginName());

                } else {
                    // Always a fail when not true
                    if ($result === false) {
                        $code   = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                        $result = array();
                    } else {
                        $code   = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
                        if (is_string($result)) {
                            $result = array($result);
                        }
                    }
                    $result = new Zend_Auth_Result($code, $this->getLoginName(), $result);
                    break;
                }
            }
        }

        $this->afterAuthorization($result);

        // MUtil_Echo::track($result);
        $this->_authResult = $result;

        return $result;
    }

    /**
     * Checks if the user is allowed to login or is blocked
     *
     * An adapter authorizes and if the end resultis boolean, string or array
     * it is converted into a Zend_Auth_Result.
     *
     * @return mixed Zend_Auth_Adapter_Interface|Zend_Auth_Result|boolean|string|array
     */
    protected function authorizeBlock()
    {
        try {
            $select = $this->db->select();
            $select->from('gems__user_login_attempts', array('UNIX_TIMESTAMP(gula_block_until) - UNIX_TIMESTAMP() AS wait'))
                    ->where('gula_block_until is not null')
                    ->where('gula_login = ?', $this->getLoginName())
                    ->where('gula_id_organization = ?', $this->getCurrentOrganizationId())
                    ->limit(1);

            // Not the first login
            if ($block = $this->db->fetchOne($select)) {
                if ($block > 0) {
                    $minutes = intval($block / 60) + 1;

                    // Report all is not well
                    return sprintf($this->translate->plural('Your account is temporarily blocked, please wait a minute.', 'Your account is temporarily blocked, please wait %d minutes.', $minutes), $minutes);

                } else {
                    // Clean the block once it's past
                    $values['gula_failed_logins'] = 0;
                    $values['gula_last_failed']   = null;
                    $values['gula_block_until']   = null;
                    $where = $this->db->quoteInto('gula_login = ? AND ', $this->getLoginName());
                    $where .= $this->db->quoteInto('gula_id_organization = ?', $this->getCurrentOrganizationId());

                    $this->db->update('gems__user_login_attempts', $values, $where);
                }
            }

        } catch (Zend_Db_Exception $e) {
            // Fall through as this does not work if the database upgrade did not run
            // MUtil_Echo::r($e);
        }

        return true;
    }

    /**
     * Checks if the user is allowed to login using the current IP address
     *
     * An adapter authorizes and if the end resultis boolean, string or array
     * it is converted into a Zend_Auth_Result.
     *
     * @return mixed Zend_Auth_Adapter_Interface|Zend_Auth_Result|boolean|string|array
     */
    protected function authorizeIp()
    {
        if ($this->util->isAllowedIP($_SERVER['REMOTE_ADDR'], $this->getAllowedIPRanges())) {
            return true;
        } else {
            return $this->translate->_('You are not allowed to login from this location.');
        }
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

        return (boolean) $this->acl && $this->userLoader;
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
        // MUtil_Echo::track($this->_getVar('__allowedOrgs'));

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
     * Returns a form to change the possword for this user.
     *
     * @param boolean $askOld Ask for the old password, calculated when not set.
     * @return Gems_Form
     */
    public function getChangePasswordForm($args_array = null)
    {
        if (! $this->canSetPassword()) {
            return;
        }

        return $this->userLoader->getChangePasswordForm($this, func_get_args());
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
        if ($this->isCurrentUser() && (null === $orgId)) {
            $orgId = Gems_Cookies::getOrganization($this->getRequest());
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
     * Returns the from address
     *
     * @return string E-Mail address
     */
    public function getFrom()
    {
        // Gather possible sources of a from address
        $sources[] = $this->getBaseOrganization();
        if ($this->getBaseOrganizationId() != $this->getCurrentOrganizationId()) {
            $sources[] = $this->getCurrentOrganization();
        }
        $sources[] = $this->project;

        foreach ($sources as $source) {
            if ($from = $source->getFrom()) {
                return $from;
            }
        }

        // We really don't like it, but sometimes the only way to get a from address.
        return $this->getEmailAddress();
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
     * Returns the gender for use as part of a sentence, e.g. Dear Mr/Mrs
     *
     * In practice: starts lowercase
     *
     * @param string $locale
     * @return array gender => string
     */
    protected function getGenderGreeting($locale = null)
    {
        $greetings = $this->util->getTranslated()->getGenderGreeting($locale);

        if (isset($greetings[$this->_getVar('user_gender')])) {
            return $greetings[$this->_getVar('user_gender')];
        }
    }

    /**
     * Returns the gender for use in stand-alone name display
     *
     * In practice: starts uppercase
     *
     * @param string $locale
     * @return array gender => string
     */
    protected function getGenderHello($locale = null)
    {
        $greetings = $this->util->getTranslated()->getGenderHello($locale);

        if (isset($greetings[$this->_getVar('user_gender')])) {
            return $greetings[$this->_getVar('user_gender')];
        }
    }

    /**
     * Returns a standard greeting for the current user.
     *
     * @param string $locale
     * @return int
     */
    public function getGreeting($locale = null)
    {
        if (! $this->_getVar('user_greeting')) {
            $greeting[] = $this->getGenderGreeting($locale);

            if ($this->_getVar('user_last_name')) {
                $greeting[] = $this->_getVar('user_surname_prefix');
                $greeting[] = $this->_getVar('user_last_name');
            } else {
                $name = $this->getLoginName();
                $name = substr($name, 0, 3) . str_repeat('*', strlen($name) - 2);
                $greeting[] = $name;
            }
            array_filter($greeting);

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
     * Array of field name => values for sending E-Mail
     *
     * @param string $locale
     * @return array
     */
    public function getMailFields($locale = null)
    {
        $org         = $this->getBaseOrganization();
        $orgResults  = $org->getMailFields();
        $projResults = $this->project->getMailFields();

        $result['bcc']        = $projResults['project_bcc'];
        $result['email']      = $this->getEmailAddress();
        $result['first_name'] = $this->_getVar('user_first_name');
        $result['from']       = $this->getFrom();
        $result['full_name']  = trim($this->getGenderHello($locale) . ' ' . $this->getFullName());
        $result['greeting']   = $this->getGreeting($locale);
        $result['last_name']  = ltrim($this->_getVar('user_surname_prefix') . ' ') . $this->_getVar('user_last_name');
        $result['login_url']  = $orgResults['organization_login_url'];
        $result['name']       = $this->getFullName();

        $result = $result + $orgResults + $projResults;

        $result['reset_ask']  = $orgResults['organization_login_url'] . '/index/resetpassword';
        $result['reply_to']   = $result['from'];
        $result['to']         = $result['email'];

        return $result;
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
     * Return the Request object
     *
     * @return Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        if (! $this->request) {
            $this->request = Zend_Controller_Front::getInstance()->getRequest();
        }
        return $this->request;
    }

    /**
     * Array of field name => values for sending a reset password E-Mail
     *
     * @param string $locale
     * @return array
     */
    public function getResetPasswordMailFields($locale = null)
    {
        $result['reset_key']     = $this->getPasswordResetKey();
        $result['reset_url']     = $this->getBaseOrganization()->getLoginUrl() . '/index/resetpassword/key/' . $result['reset_key'];

        return $result + $this->getMailFields($locale);
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
     * True when the reset key is within it's timeframe
     *
     * @return boolean
     */
    public function hasValidResetKey()
    {
        return (boolean) $this->_getVar('user_resetkey_valid');
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
     * True when this user must enter a new password.
     *
     * @return boolean
     */
    public function isBlockable()
    {
        if ($this->_hasVar('user_blockable')) {
            return (boolean) $this->_getVar('user_blockable');
        } else {
            return true;
        }
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
     * Load the callables | results needed to authenticate/authorize this user
     *
     * A callable will be called, then an adapter authorizes and if the end result
     * is boolean, string or array it is converted into a Zend_Auth_Result.
     *
     * @param string $password
     * @return array Of Callable|Zend_Auth_Adapter_Interface|Zend_Auth_Result|boolean|string|array
     */
    protected function loadAuthorizers($password)
    {
        $auths['ip'] = array($this, 'authorizeIp');

        if ($this->isBlockable()) {
            $auths['block'] = array($this, 'authorizeBlock');
        }

        if ($this->isActive()) {
            $auths['pwd'] = $this->definition->getAuthAdapter($this, $password);
        } else {
            $auths['pwd'] = false;
        }

        return $auths;
    }

    /**
     * Send an e-mail to this user
     *
     * @param string $subjectTemplate A subject template in which {fields} are replaced
     * @param string $bbBodyTemplate A BB Code body template in which {fields} are replaced
     * @param boolean $useResetFields When true get a reset key for this user
     * @param string $locale Optional locale
     * @return mixed String or array of warnings when something went wrong
     */
    public function sendMail($subjectTemplate, $bbBodyTemplate, $useResetFields = false, $locale = null)
    {
        if ($useResetFields && (! $this->canResetPassword())) {
            return $this->translate->_('Trying to send a password reset to a user that cannot be reset.');
        }

        $mail = new Gems_Mail();
        $mail->setTemplateStyle($this->getBaseOrganization()->getStyle());
        $mail->setFrom($this->getFrom());
        $mail->addTo($this->getEmailAddress(), $this->getFullName(), $this->project->getEmailBounce());
        if ($bcc = $this->project->getEmailBcc()) {
            $mail->addBcc($bcc);
        }

        if ($useResetFields) {
            $fields = $this->getResetPasswordMailFields($locale);
        } else {
            $fields = $this->getMailFields($locale);
        }
        $fields = MUtil_Ra::braceKeys($fields, '{', '}');

        $mail->setSubject(strtr($subjectTemplate, $fields));
        $mail->setBodyBBCode(strtr($bbBodyTemplate, $fields));

        try {
            $mail->send();
            return null;

        } catch (Exception $e) {
            return array(
                $this->_('Unable to send e-mail.'),
                $e->getMessage());
        }
    }

    /**
     *
     * @param string $defName Optional
     * @return Gems_User_User (continuation pattern)
     */
    public function refresh($defName = null)
    {
        if ($defName) {
            $this->definition = $this->userLoader->getUserDefinition($defName);
        }

        $newData = $this->definition->getUserData($this->getLoginName(), $this->getBaseOrganizationId());
        $newData = $this->userLoader->ensureDefaultUserValues($newData, $this->definition, $defName);

        foreach ($newData as $key => $value) {
            $this->_setVar($key, $value);
        }

        return $this;
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

        $this->getCurrentOrganization()->setAsCurrentOrganization();

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
            }
            if ($this->isCurrentUser()) {
                $this->getCurrentOrganization()->setAsCurrentOrganization();

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
