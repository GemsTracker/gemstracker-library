<?php

/**
 *
 * @package    Gems
 * @subpackage user
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;


use DateTimeImmutable;

use Gems\Locale\Locale;
use Gems\User\Group;
use Gems\User\Embed\EmbeddedAuthInterface;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\TwoFactor\TwoFactorAuthenticatorInterface;
use Gems\Util\Translated;
use MUtil\Model;

use Laminas\Authentication\Result;
use Laminas\Authentication\Adapter\AdapterInterface;

/**
 * User object that mimmicks the old $this->session behaviour
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class User extends \MUtil\Translate\TranslateableAbstract
{
    /**
     *
     * @var TwoFactorAuthenticatorInterface
     */
    protected $_authenticator;

    /**
     *
     * @var \Laminas\Authentication\Result
     */
    protected $_authResult;

    /**
     *
     * @var \Gems\User\Embed\EmbeddedUserData
     */
    protected $_embedderData;

    /**
     *
     * @var \Gems\User\Group
     */
    protected $_group;

    /**
     *
     * @var \ArrayObject or \Zend_Session_Namespace
     */
    private $_vars;

    /**
     * Required
     *
     * @var \MUtil\Acl
     */
    protected $acl;

    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    /**
     * @var \Gems\Communication\CommunicationRepository
     */
    protected $communicationRepository;

    /**
     * @var array
     */
    protected $config;

    /**
     * Required
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var string
     */
    protected $defaultAuthenticatorClass = 'GoogleAuthenticator';

    /**
     * Required, set in constructor
     *
     * @var \Gems\User\UserDefinitionInterface
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
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var Locale
     */
    protected $locale;

    /**
     * Array containing the parameter names that may point to an organization
     *
     * @var array
     */
    public $possibleOrgIds = [
        \MUtil\Model::REQUEST_ID2,
        'gr2o_id_organization',
        'gr2t_id_organization',
        'gap_id_organization',
        'gto_id_organization',
        'gor_id_organization',
        'gla_organization',
        'grco_organization',
    ];

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Required
     *
     * @var \Zend_Session_Namespace
     */
    protected $session;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * Required
     *
     * @var \Gems\User\UserLoader
     */
    protected $userLoader;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Creates the class for this user.
     *
     * @param mixed $settings Array, \Zend_Session_Namespace or \ArrayObject for this user.
     * @param \Gems\User\UserDefinitionInterface $definition The user class definition.
     */
    public function __construct($settings, \Gems\User\UserDefinitionInterface $definition)
    {
        if (is_array($settings)) {
            $this->_vars = new \ArrayObject($settings);
            $this->_vars->setFlags(\ArrayObject::STD_PROP_LIST);
        } else {
            $this->_vars = $settings;
        }
        $this->definition = $definition;
        // \MUtil\EchoOut\EchoOut::track($settings);
    }

    /**
     * Helper function for setcurrentOrganization
     *
     * Change value to $newId in an array if the key is in the $keys array (as key) and value is $oldId
     *
     * @param array $array
     * @param int $oldId
     * @param int $newId
     * @param array $keys
     * @return array
     */
    protected function _changeIds($array, $oldId, $newId, $keys) {
        if (!is_array($array)) {
            return $array;
        }

        $matches = array_intersect_key($array, $keys);
        foreach($matches as $key => &$curId) {
            if ($curId == $oldId) {
                $array[$key] = $newId;
            }
        }

        return $array;
    }

    /**
     * Get a role with a check on the value in case of integers
     *
     * @param string $roleField
     * @return mixed
     */
    protected function _getRole($roleField)
    {
        return $this->_getVar($roleField);
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

        if ($store instanceof \Zend_Session_Namespace) {
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
     * @return \ArrayObject or \Zend_Session_Namespace
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

        if ($store instanceof \Zend_Session_Namespace) {
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

        if ($store instanceof \Zend_Session_Namespace) {
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

        if ($store instanceof \Zend_Session_Namespace) {
            $store->__unset($name);
        } else {
            if ($store->offsetExists($name)) {
                $store->offsetUnset($name);
            }
        }
    }

    /**
     * Process everything after authentication.
     *
     * @param \Laminas\Authentication\Result $result
     */
    protected function afterAuthorization(Result $result, $lastAuthorizer = null)
    {
        try {
            $select = $this->db->select();
            $select->from('gems__user_login_attempts', array('gula_failed_logins', 'gula_last_failed', 'gula_block_until', new \Zend_Db_Expr('UNIX_TIMESTAMP() - UNIX_TIMESTAMP(gula_last_failed) AS since_last')))
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
                $values['since_last']           = $this->failureIgnoreTime + 1;
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
                } elseif ($lastAuthorizer === 'pwd') {
                    // Only increment failed login when password failed
                    $values['gula_failed_logins'] += 1;
                }

                // If block is already set
                if ($values['gula_block_until']) {
                    // Do not change it anymore
                    unset($values['gula_block_until']);

                } else {
                    // Only set the block when needed
                    if ($this->failureBlockCount <= $values['gula_failed_logins']) {
                        $values['gula_block_until'] = new \Zend_Db_Expr('DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ' . $this->failureIgnoreTime . ' SECOND)');
                    }
                }

                // Always record the last fail
                $values['gula_last_failed']     = new \MUtil\Db\Expr\CurrentTimestamp();
                $values['gula_failed_logins']   = max(1, $values['gula_failed_logins']);

                // Response gets slowly slower
                $sleepTime = min($values['gula_failed_logins'] - 1, 10) * 2;
                sleep($sleepTime);
                // \MUtil\EchoOut\EchoOut::track($sleepTime, $values, $result->getMessages());
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

        } catch (\Zend_Db_Exception $e) {
            // Fall through as this does not work if the database upgrade did not yet run
            // \MUtil\EchoOut\EchoOut::r($e);
        }

    }

    /**
     *
     * @param array $row
     * @return array $row
     */
    public function applyGroupMask(array $row)
    {
        $group = $this->getGroup();

        if (! $group) {
            return $row;
        }

        return $group->applyGroupToData($row);
    }

    /**
     * Function where the layout and style can be set, called in \Gems\Escort->prepareController()
     *
     * @param \Gems\Escort $escort
     * @return $this
     */
    public function applyLayoutSettings(\Gems\Escort $escort)
    {
        if ($this->_hasVar('current_user_crumbs')) {
            // \MUtil\EchoOut\EchoOut::track($this->_getVar('current_user_crumbs'));
            switch ($this->_getVar('current_user_crumbs')) {
                case 'no_display':
                    $this->project['layoutPrepare']['crumbs'] = null;
                    break;

                case 'no_top':
                    $this->project['layoutPrepareArgs']['crumbs']['always' ] = 1;
                    $this->project['layoutPrepareArgs']['crumbs']['hideTop' ] = 1;
                    break;
            }
        }

        if ($this->_hasVar('current_user_layout')) {
            $layout     = $escort->layout;
            $usedLayout = $this->_getVar('current_user_layout');

            if ($usedLayout && $layout instanceof \Zend_Layout) {
                $layout->setLayout($usedLayout);
            }
        }

        if ($escort instanceof \Gems\Project\Layout\MultiLayoutInterface) {
            if ($this->_hasVar('current_user_style')) {
                $style = $this->_getVar('current_user_style');
            } else {
                $style = null;
            }

            // Cookie org is often either \Gems\User\UserLoader::SYSTEM_NO_ORG (-1) or 0 if not set
            if (! ($style || \Gems\Cookies::getOrganization($this->getRequest()) > 0)) {
                $site = $this->util->getSites()->getSiteForCurrentUrl();
                if ($site) {
                    $style = $site->getStyle();
                }
            }
                
            if (! $style) {
                $style = $this->getCurrentOrganization()->getStyle();
            }

            $escort->layoutSwitch($style);
        }

        return $this;
    }

    /**
     * Set menu parameters from this user
     *
     * @param \Gems\Menu\ParameterSource $source
     * @return \Gems\User\User
     */
    public function applyToMenuSource(\Gems\Menu\ParameterSource $source)
    {
        $source->offsetSet('gsf_id_organization', $this->getBaseOrganizationId());
        $source->offsetSet('gsf_active',          $this->isActive() ? 1 : 0);
        $source->offsetSet('accessible_role',     $this->inAllowedGroup() ? 1 : 0);
        $source->offsetSet('can_mail',            $this->hasEmailAddress() ? 1 : 0);
        $source->offsetSet('has_2factor',         $this->isTwoFactorEnabled() ? 2 : 0);
    }

    /**
     *
     * @param string $fieldName1 First of unlimited number of field names
     * @return boolean True if this field is invisible
     */
    public function areAllFieldsInvisible($fieldName1, $fieldName2 = null)
    {
        $group = $this->getGroup();
        if (! $group) {
            return false;
        }

        foreach (func_get_args() as $fieldName) {
            if (! $group->isFieldInvisible($fieldName)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param string $fieldName1 First of unlimited number of field names
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function areAllFieldsMaskedPartial($fieldName1, $fieldName2 = null)
    {
        $group = $this->getGroup();
        if (! $group) {
            return false;
        }

        foreach (func_get_args() as $fieldName) {
            if (! $group->isFieldMaskedPartial($fieldName)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param string $fieldName1 First of unlimited number of field names
     * @return boolean True if this field is wholly masked (or invisible)
     */
    public function areAllFieldsMaskedWhole($fieldName1, $fieldName2 = null)
    {
        $group = $this->getGroup();
        if (! $group) {
            return false;
        }

        foreach (func_get_args() as $fieldName) {
            if (! $group->isFieldMaskedWhole($fieldName)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Authenticate a users credentials using the submitted form
     *
     * @param string $password The password to test
     * @param boolean $testPassword Set to false to test the non-password checks only
     * @return \Laminas\Authentication\Result
     */
    public function authenticate($password, $testPassword = true)
    {
        $auths = $this->loadAuthorizers($password, $testPassword);

        $lastAuthorizer = null;
        foreach ($auths as $lastAuthorizer => $result) {
            if (is_callable($result)) {
                $result = call_user_func($result);
            }

            if ($result instanceof AdapterInterface) {
                $result = $result->authenticate();
            }

            if ($result instanceof Result) {
                if (! $result->isValid()) {
                    break;
                }
            } else {
                if (true === $result) {
                    $result = new Result(Result::SUCCESS, $this->getLoginName());

                } else {
                    // Always a fail when not true
                    if ($result === false) {
                        $code   = Result::FAILURE_CREDENTIAL_INVALID;
                        $result = array();
                    } else {
                        $code   = Result::FAILURE_UNCATEGORIZED;
                        if (is_string($result)) {
                            $result = array($result);
                        }
                    }
                    $result = new Result($code, $this->getLoginName(), $result);
                    break;
                }
            }
        }

        if ($result->isValid() && $this->definition instanceof \Gems\User\DbUserDefinitionAbstract) {
            $this->definition->checkRehash($this, $password);
        }


        $this->afterAuthorization($result, $lastAuthorizer);

        // \MUtil\EchoOut\EchoOut::track($result);
        $this->_authResult = $result;

        return $result;
    }

    /**
     * Checks if the user is allowed to login or is blocked
     *
     * An adapter authorizes and if the end resultis boolean, string or array
     * it is converted into a \Laminas\Authenticate\Result.
     *
     * @return mixed \Laminas\Authentication\Adapter\AdapterInterface|Laminas\Authenticate\Result|boolean|string|array
     */
    protected function authorizeBlock()
    {
        try {
            $select = $this->db->select();
            $select->from('gems__user_login_attempts', new \Zend_Db_Expr('UNIX_TIMESTAMP(gula_block_until) - UNIX_TIMESTAMP() AS wait'))
                    ->where('gula_block_until is not null')
                    ->where('gula_login = ?', $this->getLoginName())
                    ->where('gula_id_organization = ?', $this->getCurrentOrganizationId())
                    ->limit(1);

            // Not the first login
            if ($block = $this->db->fetchOne($select)) {
                if ($block > 0) {
                    $minutes = intval($block / 60) + 1;

                    // Report all is not well
                    return sprintf($this->plural('Your account is temporarily blocked, please wait a minute.', 'Your account is temporarily blocked, please wait %d minutes.', $minutes), $minutes);

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

        } catch (\Zend_Db_Exception $e) {
            // Fall through as this does not work if the database upgrade did not run
            // \MUtil\EchoOut\EchoOut::r($e);
        }

        return true;
    }

    /**
     * Checks if the user is allowed to login using the current IP address
     * according to the group he is in
     *
     * An adapter authorizes and if the end resultis boolean, string or array
     * it is converted into a \Laminas\Authenticate\Result.
     *
     * @return mixed \Laminas\Authentication\Adapter\AdapterInterface|Laminas\Authenticate\Result|boolean|string|array
     */
    protected function authorizeIp()
    {
        //In unit test REMOTE_ADDR is not available and will return null
        $request = $this->getRequest();

        // E.g. command line user
        if (! $request instanceof \Zend_Controller_Request_Http) {
            return true;
        }

        $remoteIp = $request->getServer('REMOTE_ADDR');
        if ($this->util->isAllowedIP($remoteIp, $this->getAllowedIPRanges())) {
            return true;
        }

        return $this->_('You are not allowed to login from this location.');
    }

    /**
     * Checks if the user is allowed to login using the current IP address
     * according to his BASE organization
     *
     * An adapter authorizes and if the end resultis boolean, string or array
     * it is converted into a \Laminas\Authenticate\Result.
     *
     * @return mixed \Laminas\Authentication\Adapter\AdapterInterface|Laminas\Authenticate\Result|boolean|string|array
     */
    protected function authorizeOrgIp()
    {
        //special case: project user should have no restriction
        if ($this->project->getSuperAdminName() == $this->getLoginName()) {
            return true;
        }

        //In unit test REMOTE_ADDR is not available and will return null
        $request = $this->getRequest();

        // E.g. command line user
        if (! $request instanceof \Zend_Controller_Request_Http) {
            return true;
        }

        $remoteIp = $request->getServer('REMOTE_ADDR');
        if ($this->util->isAllowedIP($remoteIp, $this->getBaseOrganization()->getAllowedIpRanges())) {
            return true;
        }

        return $this->_('You are not allowed to login from this location.');
    }

    /**
     * True when the current url is one where this user is allowed to login.
     *
     * If the url is a fixed organization url and the user is not allowed to
     * access this organization, then this function returns false.
     *
     * @return boolean
     */
    public function canLoginHere()
    {
        if (! $this->_hasVar('can_login_here')) {
            $site = $this->util->getSites()->getSiteForCurrentUrl();
            
            $this->_setVar('can_login_here', $site->hasUrlOrganizationsId($this->getCurrentOrganizationId()));
        }
        return $this->_getVar('can_login_here');
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
     * Return true if the two factor can be set.
     *
     * @return boolean
     */
    public function canSaveTwoFactorKey()
    {
        return $this->definition->canSaveTwoFactorKey();
    }

    /**
     * Return true if the password can be set.
     *
     * @return boolean
     */
    public function canSetPassword()
    {
        return $this->isActive() && $this->definition->canSetPassword();
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if (! (($this->db instanceof \Zend_Db_Adapter_Abstract) && ($this->session instanceof \Zend_Session_Namespace))) {
            return false;
        }

        // Checks if this is the current user
        if (! $this->_vars instanceof \Zend_Session_Namespace) {
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
                // When this is the case, use the \Zend_Session_Namespace object with the current set values
                // This way changes to this user object are reflected in the CurrentUser object and vice versa.
                $this->setAsCurrentUser();
            }
        }

        // Change a numeric role id to it's string value
        $this->_getRole('user_role');
        if ($this->_hasVar('current_user_role')) {
            $this->_getRole('current_user_role');
        }

        return (boolean) $this->acl && $this->userLoader;
    }

    /**
     * Clear the two factor authentication key
     *
     * @return $this
     */
    public function clearTwoFactorKey()
    {
        $this->_setVar('user_two_factor_key', null);

        $this->definition->setTwoFactorKey($this, null);

        return $this;
    }

    /**
     * Disable mask usage (call before any applyGroupToData() or applyGroupToModel()
     * calls: doesn't work retroactively
     *
     * @return $this
     */
    public function disableMask()
    {
        $group = $this->getGroup();

        if ($group instanceof Group) {
            $group->disableMask();
        }

        return $this;
    }

    /**
     * Enable mask usage (call before any applyGroupToData() or applyGroupToModel()
     * calls: doesn't work retroactively
     *
     * @return $this
     */
    public function enableMask()
    {
        $group = $this->getGroup();

        if ($group instanceof Group) {
            $group->enableMask();
        }

        return $this;
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
        // \MUtil\EchoOut\EchoOut::track($this->_getVar('__allowedOrgs'));

        return $this->_getVar('__allowedOrgs');
    }

    /**
     * Returns the current roles a user may set.
     *
     * NOTE! A user can set a role, unless it <em>requires a higher role level</em>.
     *
     * I.e. an admin is not allowed to set a super role as super inherits and expands admin. But it is
     * allowed to set the nologin and respondent roles that are not inherited by the admin as they are
     * in a different hierarchy.
     *
     * An exception is the role master as it is set by the system. You gotta be a master to set the master
     * role.
     *
     * @return array With identical keys and values roleId => roleId
     */
    public function getAllowedRoles()
    {
        $userRole = $this->getRole();
        if ($userRole === 'master') {
            $output = $this->acl->getRoles();
            return array_combine($output, $output);
        }

        $output = array($userRole => $userRole);
        foreach ($this->acl->getRoles() as $role) {
            if (! $this->acl->inheritsRole($role, $userRole, true)) {
                $output[$role] = $role;
            }
        }
        unset($output['master']);
        return $output;
    }

    /**
     * Retrieve an array of groups the user is allowed to assign: his own group and all groups
     * he/she inherits rights from
     *
     * @param boolean $current Return the current list or the original list when true
     * @return array
     */
    public function getAllowedStaffGroups($current = true)
    {
        // Always refresh because these values are otherwise not responsive to change
        $dbLookup = $this->util->getDbLookup();
        $groupId  = $this->getGroupId($current);
        $groups   = $dbLookup->getActiveStaffGroups();

        if ('master' === $this->getRole($current)) {
            $groups[-1] = $this->_('Project \'master\'');
            return $groups;
        }

        try {
            $setGroups     = $this->db->fetchOne(
                    "SELECT ggp_may_set_groups FROM gems__groups WHERE ggp_id_group = ?",
                    $groupId
                    );
            $groupsAllowed = is_array($setGroups) ? explode(',', $setGroups) : [];
        } catch (\Zend_Db_Exception $e) {
            // The database might not be updated
            $groupsAllowed = [];
        }

        $result = array();

        foreach ($groups as $id => $label) {
            if ((in_array($id, $groupsAllowed))) {
                $result[$id] = $groups[$id];
            }
        }
        natsort($result);

        return $result;
    }

    /**
     * Returns the original (not the current) organization used by this user.
     *
     * @return \Gems\User\Organization
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
     * @return \Gems\Form
     */
    public function getChangePasswordForm($args_array = null)
    {
        if (! $this->canSetPassword()) {
            return;
        }

        $args = \MUtil\Ra::args(func_get_args());
        if (isset($args['askCheck']) && $args['askCheck']) {
            $args['checkFields'] = $this->loadResetPasswordCheckFields();
        }

        return $this->userLoader->getChangePasswordForm($this, $args);
    }

    /**
     * Returns the organization that is currently used by this user.
     *
     * @return \Gems\User\Organization
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
        if ($this->isCurrentUser() && ((null === $orgId) || (\Gems\User\UserLoader::SYSTEM_NO_ORG === $orgId))) {
            $request = $this->getRequest();
            if ($request) {
                $orgId = \Gems\Cookies::getOrganization($this->getRequest());
            }
            if (! $orgId) {
                $orgId = 0;
            }
            $this->_setVar('user_organization_id', $orgId);
        }
        return $orgId;
    }

    /**
     * Get the propper Dear mr./mrs/ greeting of respondent
     * @return string
     */
    public function getDearGreeting(string $language = null)
    {
        $genderDears = $this->translatedUtil->getGenderDear($language);

        $gender = $this->_getVar('user_gender');
        if (isset($genderDears[$gender])) {
            $greeting = $genderDears[$gender] . ' ';
        } else {
            $greeting = '';
        }

        return $greeting . $this->getLastName();
    }
    
    /**
     * Return default new use group, if it exists
     *
     * @return string
     */
    public function getDefaultNewStaffGroup()
    {
        $group = $this->getGroup();

        if ($group) {
            return $group->getDefaultNewStaffGroup();
        }
    }

    /**
     * Return true if this user has a password.
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->_getVar('user_email');
    }

    /**
     * If this is an embedder, return the EmbedderUserData object
     *
     * @return \Gems\User\Embed\EmbeddedUserData
     */
    public function getEmbedderData()
    {
        if (! $this->isEmbedded()) {
            return null;
        }

        if ($this->_embedderData) {
            return $this->_embedderData;
        }

        $this->_embedderData = $this->loader->getEmbedDataObject($this->getUserId(), $this->db);

        return $this->_embedderData;
    }

    public function getFirstName(): ?string
    {
        return $this->_getVar('user_first_name');
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

        foreach ($sources as $source) {
            if ($from = $source->getFrom()) {
                return $from;
            }
        }

        if (isset($this->config['email']['site'])) {
            return $this->config['email']['site'];
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
                $name = substr($name, 0, 3) . str_repeat('*', max(5, strlen($name) - 2));
            }

            $this->_setVar('user_name', $name);

            // \MUtil\EchoOut\EchoOut::track($name);
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
        $greetings = $this->translatedUtil->getGenderGreeting($locale);

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
     * @return string Greeting
     */
    public function getGenderHello($locale = null)
    {
        $greetings = $this->translatedUtil->getGenderHello($locale);

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
                if ($name) {
                    $name = substr($name, 0, 3) . str_repeat('*', strlen($name) - 2);
                    $greeting[] = $name;
                }
            }
            array_filter($greeting);

            $this->_setVar('user_greeting', implode(' ', $greeting));
        }

        return $this->_getVar('user_greeting');
    }

    /**
     * Returns the group of this user.
     *
     * @return \Gems\User\Group
     */
    public function getGroup()
    {
        if (! $this->_group) {
            $groupId = $this->getGroupId();
            if ($groupId) {
                $this->_group = $this->userLoader->getGroup($groupId);
            }
        }

        return $this->_group;
    }

    /**
     * Returns the group number of this user.
     *
     * @param boolean $current Checks value for current role (when false for normal role);
     * @return int
     */
    public function getGroupId($current = true)
    {
        if ($current && $this->_hasVar('current_user_group')) {
            return $this->_getVar('current_user_group');
        }
        return $this->_getVar('user_group');
    }

    /**
     * Returns the user last name (prefix, last).
     *
     * @return string
     */
    public function getLastName()
    {
        if (! $this->_getVar('last_name')) {
            $name = ltrim($this->_getVar('user_surname_prefix') . ' ') .
                $this->_getVar('user_last_name');

            if (! $name) {
                // Use obfuscated login name
                $name = $this->getLoginName();
                $name = substr($name, 0, 3) . str_repeat('*', max(5, strlen($name) - 2));
            }

            $this->_setVar('last_name', $name);

            // \MUtil\EchoOut\EchoOut::track($name);
        }

        return $this->_getVar('last_name');
    }

    /**
     * The locale set for this user.
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

        // $result['bcc']            = $projResults['project_bcc'];
        $result['dear']           = $this->getDearGreeting();
        $result['email']          = $this->getEmailAddress();
        $result['first_name']     = $this->_getVar('user_first_name');
        $result['from']           = $this->getFrom();
        $result['full_name']      = trim($this->getGenderHello($locale) . ' ' . $this->getFullName());
        $result['greeting']       = $this->getGreeting($locale);
        $result['last_name']      = ltrim($this->_getVar('user_surname_prefix') . ' ') . $this->_getVar('user_last_name');
        $result['login_url']      = $orgResults['organization_login_url'];
        $result['name']           = $this->getFullName();
        $result['login_name']     = $this->getLoginName();

        $result = $result + $orgResults;

        $result['reset_ask']      = $orgResults['organization_login_url'] . '/index/resetpassword';
        $result['reset_in_hours'] = $this->definition->getResetKeyDurationInHours();
        $result['reply_to']       = $result['from'];
        $result['to']             = $result['email'];

        return $result;
    }

    /**
     * Get the HOTP count
     */
    public function getOtpCount()
    {
        return $this->_getVar('user_otp_count');
    }

    /**
     * Get the HOTP requested time
     */
    public function getOtpRequested()
    {
        return \MUtil\Model::getDateTimeInterface($this->_getVar('user_otp_requested'));
    }

    /**
     * Return the number of days since last change of password
     *
     * @return int
     */
    public function getPasswordAge()
    {
        $date = Model::getDateTimeInterface($this->_getVar('user_password_last_changed'));
        if ($date instanceof \DateTimeInterface) {
            return abs($date->diff(new DateTimeImmutable())->days);
        } else {
            return 0;
        }
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
     * Return a password reset key
     *
     * @return int hours valid
     */
    public function getPasswordResetKeyDuration(): int
    {
        return $this->definition->getResetKeyDurationInHours();
    }

    /**
     * Return the (unfiltered) phonenumber if the user has one
     *
     * @return string|null
     */
    public function getPhonenumber()
    {
        return $this->_getVar('user_phonenumber');
    }

    /**
     * Return the Request object
     *
     * @return \Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        if (! $this->request) {
            $this->request = \Zend_Controller_Front::getInstance()->getRequest();
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
        $result['reset_key'] = $this->getPasswordResetKey();
        $result['reset_url'] = $this->getBaseOrganization()->getLoginUrl() . '/index/resetpassword/key/' . $result['reset_key'];
        $result['reset_in_hours'] = $this->definition->getResetKeyDurationInHours();

        return $result + $this->getMailFields($locale);
    }

    /**
     * Get an array of OrgId => Org Name for all allowed organizations that can have
     * respondents for the current logged in user
     *
     * @return array
     */
    public function getRespondentOrganizations()
    {

        if (! $this->_hasVar('__allowedRespOrgs')) {
            $availableOrganizations = $this->util->getDbLookup()->getOrganizationsWithRespondents();
            $allowedOrganizations   = $this->getAllowedOrganizations();

            $this->_setVar('__allowedRespOrgs', array_intersect($availableOrganizations, $allowedOrganizations));
        }
        // \MUtil\EchoOut\EchoOut::track($this->_getVar('__allowedOrgs'));

        return $this->_getVar('__allowedRespOrgs');
    }

    /**
     * Get an array of OrgId's for filtering on all allowed organizations that can have
     * respondents for the current logged in user
     *
     * @return array
     */
    public function getRespondentOrgFilter()
    {
        return array_keys($this->getRespondentOrganizations());
    }

    /**
     * Get a where statement containing orgId's for combi field where statements on all
     * allowed organizations that can have respondents for the current logged in user
     *
     * @param string $fieldName Field name separator
     * @param string $sep Optional different value seperator
     * @return string
     */
    public function getRespondentOrgWhere($fieldName, $sep = '|')
    {
        return "(INSTR($fieldName, '$sep" .
                implode("$sep') > 0 OR INSTR($fieldName, '$sep", $this->getRespondentOrgFilter()) .
                "$sep') > 0)";
    }

    /**
     * Returns the current user role.
     *
     * @param boolean $current Checks value for current role (when false for normal role);
     * @return string
     */
    public function getRole($current = true)
    {
        if ($current && $this->_hasVar('current_user_role')) {
            return $this->_getRole('current_user_role');
        }
        return $this->_getRole('user_role');
    }

    /**
     * Returns the current user roles.
     *
     * @return array With identical keys and values roleId => roleId
     */
    public function getRoles()
    {
        return $this->acl->getRoleAndParents($this->getRole());
    }

    /**
     * Return the secret key for embedded login
     *
     * @return array
     */
    public function getSecretKey()
    {
        if ($this->isEmbedded()) {
            if (! $this->_hasVar('secretKey')) {
                if (! $this->_hasVar('gsus_secret_key')) {
                    $this->refreshEmbeddingData();
                }
                $key = $this->_getVar('gsus_secret_key');
                $this->_setVar('secretKey', $key ? $this->project->decrypt($key) : null);
            }
            return $this->_getVar('secretKey', null);
        }
    }

    /**
     * @return int or null
     */
    public function getSessionOrganizionId()
    {
        return $this->_getVar('current_user_orgId', null);
    }

    /**
     * @return string or null
     */
    public function getSessionPatientNr()
    {
        return $this->_getVar('current_user_patNr', null);
    }

    public function getSurnamePrefix(): ?string
    {
        return $this->_getVar('user_surname_prefix');
    }

    /**
     * get the parameters where the survey should return to
     *
     * @return array
     */
    public function getSurveyReturn()
    {
        return $this->_getVar('surveyReturn', array());
    }

    /**
     *
     * @return TwoFactorAuthenticatorInterface
     */
    public function getTwoFactorAuthenticator()
    {
        if (! $this->_authenticator instanceof TwoFactorAuthenticatorInterface) {
            if ($this->_hasVar('user_two_factor_key')) {
                $authClass = \MUtil\StringUtil\StringUtil::beforeChars(
                        $this->_getVar('user_two_factor_key'),
                        TwoFactorAuthenticatorInterface::SEPERATOR
                        );

            } else {
                $authClass = $this->defaultAuthenticatorClass;
            }

            $this->_authenticator = $this->userLoader->getTwoFactorAuthenticator($authClass);
            if ($this->_authenticator instanceof \Gems\User\TwoFactor\UserOtpInterface) {
                $this->_authenticator->setUserId($this->getUserLoginId());
                $this->_authenticator->setUserOtpCount($this->getOtpCount());
                $this->_authenticator->setUserOtpRequested($this->getOtpRequested());
            }
        }

        return $this->_authenticator;
    }

    /**
     *
     * @return string
     */
    public function getTwoFactorKey()
    {
        if ($this->_hasVar('user_two_factor_key')) {
            list($class, $key) = explode(
                    TwoFactorAuthenticatorInterface::SEPERATOR,
                    $this->_getVar('user_two_factor_key'),
                    2
                    );
        } else {
            $key = null;
        }
        return $key;
    }

    /**
     *
     * @return string
     */
    public function getUserDefinitionClass()
    {
        return $this->_getVar('__user_definition');
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
        return (int) $this->_getVar('user_id');
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
     * @param \Gems\Menu $menu
     * @param \Zend_Controller_Request_Abstract $request
     * @return \Gems\Menu\SubMenuItem
     */
    public function gotoStartPage(\Gems\Menu $menu, \Zend_Controller_Request_Abstract $request)
    {
        if (false && $this->isPasswordResetRequired()) {
            // Set menu OFF
            // This code may be obsolete from 1.8.4
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
                if (!$menuItem->has('controller')) {
                    //This is a container, try to find first active child
                    $item = $menuItem;
                    foreach ($item->sortByOrder()->getChildren() as $menuItem) {
                        if ($menuItem->isAllowed() && $menuItem->has('controller')) {
                            break;
                        }
                        $menuItem = null;
                    }
                }

                if ($menuItem) {
                    $redirector = \Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                    $redirector->gotoRoute($menuItem->toRouteUrl($request), null, true);
                }
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
        return $this->_hasVar('user_email') && $this->_getVar('user_email');
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
     * @param boolean $current Checks value for current role (when false for normal role);
     * @return bool
     */
    public function hasPrivilege($privilege, $current = true)
    {
        if (! $this->acl) {
            return true;
        }
        $role = $this->getRole($current);

        return $this->acl->isAllowed($role, null, $privilege);
    }

    /**
     * Return true if this user has this role
     *
     * @param string $role
     * @return boolean
     */
    public function hasRole($role)
    {
        $roles = $this->getRoles();

        return (isset($roles[$role]));
    }

    /**
     *
     * @return boolean
     */
    public function hasTwoFactor()
    {
        return (boolean) $this->_getVar('user_two_factor_key');
    }

    /**
     * True when the reset key is within it's timeframe and OK for the current organization
     *
     * @return boolean
     */
    public function hasValidResetKey()
    {
        return (boolean) $this->isActive() && $this->_getVar('user_resetkey_valid');
    }

    /**
     * Return true if this user has a role that is accessible by the current user,
     * i.e. is the current user allowed to change this specific user
     *
     * @return boolean
     */
    public function inAllowedGroup()
    {
        if ($this->isCurrentUser() || (! $this->isStaff())) {
            // Always allow editing of non-staff user
            // for the time being
            return true;
        }

        $group  = $this->getGroupId();
        $groups = $this->util->getDbLookup()->getActiveStaffGroups();
        if (! isset($groups[$group])) {
            // Allow editing when the group does not exist or is no longer active.
            return true;
        }

        $allowedGroups = $this->userLoader->getCurrentUser()->getAllowedStaffGroups();
        if ($allowedGroups) {
            return (boolean) isset($allowedGroups[$group]);
        } else {
            return false;
        }
    }

    /**
     * @param boolean $checkCurrentOrganization Normally we check if the user is active ON THIS SITE, but not in the admin panel
     * @return boolean True when a user can log in.
     */
    public function isActive($checkCurrentOrganization = true)
    {
        if ($this->_getVar('user_active')) {
            if ($checkCurrentOrganization) {
                return $this->canLoginHere();
            }
            return true;
        }
        return false;
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

        return isset($orgs[$organizationId]) || (\Gems\User\UserLoader::SYSTEM_NO_ORG == $organizationId);
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
        return $this->_vars instanceof \Zend_Session_Namespace;
    }

    /**
     * Return true if this user is an embedded user that can defer to other logins.
     *
     * @return boolean
     */
    public function isEmbedded()
    {
        return (boolean) $this->_getVar('user_embedded');
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is invisible
     */
    public function isFieldInvisible($fieldName)
    {
        $group = $this->getGroup();
        if ($group) {
            return $group->isFieldInvisible($fieldName);
        }
        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function isFieldMaskedPartial($fieldName)
    {
        $group = $this->getGroup();
        if ($group) {
            return $group->isFieldMaskedPartial($fieldName);
        }
        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is wholly masked (or invisible)
     */
    public function isFieldMaskedWhole($fieldName)
    {
        $group = $this->getGroup();
        if ($group) {
            return $group->isFieldMaskedWhole($fieldName);
        }
        return false;
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
     * @return boolean True when we're (functionally) working in a frame, e.g. for an embedded user
     */
    public function isSessionFramed()
    {
        return $this->_getVar('current_user_framed', false);
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
     * Can this user be authorized using two factor authentication?
     *
     * @return boolean
     */
    public function isTwoFactorEnabled()
    {
        return (boolean) $this->_getVar('user_enable_2factor') && $this->hasTwoFactor();
    }

    /**
     * Should this user be authorized using two factor authentication?
     *
     * @param string $ipAddress
     * @return boolean
     */
    public function isTwoFactorRequired($ipAddress)
    {
        return $this->definition->isTwoFactorRequired($ipAddress, $this->isTwoFactorEnabled(), $this->getGroup());
    }

    /**
     * Load the callables | results needed to authenticate/authorize this user
     *
     * A callable will be called, then an adapter authorizes and if the end result
     * is boolean, string or array it is converted into a \Laminas\Authenticate\Result.
     *
     * @param string $password
     * @param boolean $testPassword Set to false to test on the non-password checks only
     * @return array Of Callable|Laminas\Authentication\Adapter\AdapterInterface|Laminas\Authenticate\Result|boolean|string|array
     */
    protected function loadAuthorizers($password, $testPassword = true)
    {
        if ($this->isBlockable()) {
            $auths['block'] = array($this, 'authorizeBlock');
        }

        // organization ip restriction
        $auths['orgip'] = array($this, 'authorizeOrgIp');

        // group ip restriction
        $auths['ip'] = array($this, 'authorizeIp');

        if ($testPassword) {
            if ($this->isActive()) {
                $auths['pwd'] = $this->definition->getAuthAdapter($this, $password);
            } else {
                $auths['pwd'] = false;
            }
        }

        return $auths;
    }

    /**
     * Returns an array of elements for check fields during password reset and/or
     * 'label name' => 'required value' pairs. For asking extra questions before allowing
     * a password change.
     *
     * Default is asking for the username but you can e.g. ask for someones birthday.
     *
     * @return array Of 'label name' => 'required values' or \Zend_Form_Element elements
     */
    protected function loadResetPasswordCheckFields()
    {
        // CHECK ON SOMEONES BIRTHDAY
        // Birthdays are usually not defined for staff but they do exist for respondents
        if ($value = $this->_getVar('user_birthday')) {
            $label    = $this->_('Your birthday');

            $birthdayElem = new \Gems\JQuery\Form\Element\DatePicker('birthday');
            $birthdayElem->setLabel($label)
                    ->setOptions(\MUtil\Model\Bridge\FormBridge::getFixedOptions('date'))
                    ->setRequired(true)
                    ->setStorageFormat('yyyy-MM-dd');

            if ($format = $birthdayElem->getDateFormat()) {
                $valueFormatted = Model::reformatDate($value, $birthdayElem->getStorageFormat(), $format);
            } else {
                $valueFormatted = $value;
            }

            $validator = new \Zend_Validate_Identical($valueFormatted);
            $validator->setMessage(sprintf($this->_('%s is not correct.'), $label), \Zend_Validate_Identical::NOT_SAME);
            $birthdayElem->addValidator($validator);

            return array($label => $birthdayElem);
        }
        // CHECK ON SOMEONES ZIP
        // Zips are usually not defined for staff but they do exist for respondents
        if ($value = $this->_getVar('user_zip')) {
            $label    = $this->_('Your zipcode');

            $zipElem = new Text('zipcode');
            $zipElem->setLabel($label)
                    ->setRequired(true);

            $validator = new \Zend_Validate_Identical($value);
            $validator->setMessage(sprintf($this->_('%s is not correct.'), $label), \Zend_Validate_Identical::NOT_SAME);
            $zipElem->addValidator($validator);

            return array($label => $zipElem);
        }
        return array($this->_('Username') => $this->getLoginName());
    }

    /**
     *
     * @param string $defName Optional
     * @return \Gems\User\User (continuation pattern)
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

        $this->_getRole('user_role');

        return $this;
    }

    /**
     * Allowes a refresh of the existing list of organizations
     * for this user.
     *
     * @return \Gems\User\User (continuation pattern)
     */
    public function refreshAllowedOrganizations()
    {
        // Privilege overrules organizational settings
        if ($this->hasPrivilege('pr.organization-switch')) {
            $orgs = $this->util->getDbLookup()->getOrganizations();
        } else {
            $org = $this->getBaseOrganization();

            $orgs = array($org->getId() => $org->getName()) +
                    $org->getAllowedOrganizations();
        }
        // \MUtil\EchoOut\EchoOut::track($orgs);

        $this->_setVar('__allowedOrgs', $orgs);

        // Clean this cache
        $this->_unsetVar('__allowedRespOrgs');

        return $this;
    }

    /**
     * Load and set the embedded user data. triggered only when
     * embedded data is requested
     *
     * @return void
     */
    protected function refreshEmbeddingData()
    {
        if (! $this->isEmbedded()) {
            return;
        }

        $data = $this->db->fetchRow(
                "SELECT * FROM gems__systemuser_setup WHERE gsus_id_user = ?",
                $this->getUserId()
                );

        if ($data) {
            unset($data['gsus_id_user'], $data['gsus_changed'], $data['gsus_changed_by'],
                    $data['gsus_created'], $data['gsus_created_by']);
        } else {
            // Load defaults
            $data = [
                'gsus_secret_key'           => null,
                'gsus_create_user'          => 0,
                'gsus_authentication'       => null,
                'gsus_deferred_user_loader' => null,
                'gsus_deferred_user_group'  => null,
                'gsus_redirect'             => null,
                'gsus_deferred_user_layout' => null,
                ];
        }

        foreach ($data as $key => $value) {
            // Using the full field name to prevent any future clash with a new or user specific field
            $this->_setVar($key, $value);
        }
    }

    /**
     * Check for password weakness.
     *
     * @param string $password Or null when you want a report on all the rules for this password.
     * @param boolean $skipAge When setting a new password, we should not check for age
     * @return mixed String or array of strings containing warning messages or nothing
     */
    public function reportPasswordWeakness($password = null, $skipAge = false)
    {
        if ($this->canSetPassword()) {
            $checker = $this->userLoader->getPasswordChecker();

            $codes[] = $this->getCurrentOrganization()->getCode();
            $codes[] = $this->getRoles();
            $codes[] = $this->_getVar('__user_definition');
            if ($this->isStaff()) {
                $codes[] = 'staff';
            }

            return $checker->reportPasswordWeakness($this, $password, \MUtil\Ra::flatten($codes), $skipAge);
        }
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
    public function sendMail($subjectTemplate, $bodyTemplate, $useResetFields = false, $locale = null)
    {
        if ($useResetFields && (! $this->canResetPassword())) {
            return $this->_('Trying to send a password reset to a user that cannot be reset.');
        }

        $mail = $this->loader->getMail();
        $mail->setTemplateStyle($this->getBaseOrganization()->getStyle());
        $mail->setFrom($this->getFrom());

        $email = $this->communicationRepository->getNewEmail();
        $email->addTo(new \Symfony\Component\Mime\Address($this->getEmailAddress(), $this->getFullName()));
        if (isset($config['email']['bcc'])) {
            $email->addBcc($config['email']['bcc']);
        }

        if ($useResetFields) {
            $fields = $this->communicationRepository->getUserPasswordMailFields($this, $locale);
        } else {
            $fields = $this->communicationRepository->getUserMailFields($this, $locale);
        }

        $email->htmlTemplate($this->communicationRepository->getTemplate($this->getBaseOrganization()), $bodyTemplate, $fields);

        $mailer = $this->communicationRepository->getMailer($this->getFrom());

        try {
            $mailer->send($email);
            return null;

        } catch (\Exception $e) {
            return array(
                $this->_('Unable to send e-mail.'),
                $e->getMessage());
        }
    }

    /**
     * Set this user as the current user.
     *
     * This means that the data about this user will be stored in a session.
     *
     * @param boolean $signalLoader Do not set, except from UserLoader
     * @param boolean $resetSessionId Should the session be reset?
     * @return \Gems\User\User (continuation pattern)
     */
    public function setAsCurrentUser($signalLoader = true, $resetSessionId = true)
    {
        // Get the current variables
        $oldStore = $this->_getVariableStore();

        // When $oldStore is a \Zend_Session_Namespace, then this user is already the current user.
        if (! $this->isCurrentUser()) {
            $this->userLoader->unsetCurrentUser();

            if ($resetSessionId) {
                \Zend_Session::regenerateId();
            }

            $this->_vars = $this->session;

            foreach ($oldStore as $name => $value) {
                $this->_vars->__set($name, $value);
            }

            // Perform the interface switch
            $this->switchLocale();

            if ($signalLoader) {
                $this->userLoader->setCurrentUser($this);
            }
        }

        $org = $this->getCurrentOrganization()->setAsCurrentOrganization();

        return $this;
    }

    /**
     * Set the currently selected organization for this user
     *
     * @param mixed $organization \Gems\User\Organization or an organization id.
     * @return \Gems\User\User (continuation pattern)
     */
    public function setCurrentOrganization($organization)
    {
        if (!($organization instanceof \Gems\User\Organization)) {
            $organization = $this->userLoader->getOrganization($organization);
        }

        $organizationId    = $organization->getId();
        $oldOrganizationId = $this->getCurrentOrganizationId();

        if ($organizationId != $oldOrganizationId) {
            $this->_setVar('user_organization_id', $organizationId);

            if ($this->isCurrentUser()) {
                $this->getCurrentOrganization()->setAsCurrentOrganization();

                if ($organization->canHaveRespondents()) {
                    $usedOrganizationId = $organizationId;
                } else {
                    $usedOrganizationId = null;
                }

                // Now update the requestcache to change the oldOrgId to the new orgId
                // Don't do it when the oldOrgId doesn't match
                $keysArray = array_flip($this->possibleOrgIds);
                $requestCache = $this->session->requestCache;
                if ($requestCache) {
                    foreach ($requestCache as $key => &$elements) {
                        $elements = $this->_changeIds($elements, $oldOrganizationId, $usedOrganizationId, $keysArray);
                    }
                    $this->session->requestCache = $requestCache;
                }

                // $searchSession &= $_SESSION['ModelSnippetActionAbstract_getSearchData'];
                $searchSession = new \Zend_Session_Namespace('ModelSnippetActionAbstract_getSearchData');
                foreach ($searchSession as $id => $data) {
                    $searchSession->$id = $this->_changeIds($data, $oldOrganizationId, $usedOrganizationId, $keysArray);
                }
            }
        }

        return $this;
    }

    /**
     * Set (for the whole session) the group of the current user.
     *
     * Different from switching, used for embedded login
     *
     * @param int $groupId
     * @return self
     */
    public function setGroupSession($groupId)
    {
        $this->_group = null;

        if ($groupId == $this->_getVar('user_group')) {
            $this->_unsetVar('current_user_group');
            $this->_unsetVar('current_user_role');
        } else {
            $group = $this->userLoader->getGroup($groupId);

            $this->_setVar('current_user_group', $groupId);
            $this->_setVar('current_user_role',  $group->getRole());
        }

        return $this;
    }

    /**
     * (Temporarily) the group of the current user.
     *
     * @param int $groupId
     * @return self
     */
    public function setGroupTemp($groupId)
    {
        $this->_group = null;

        if ($groupId == $this->_getVar('user_group')) {
            $this->_unsetVar('current_user_group');
            $this->_unsetVar('current_user_role');
        } else {
            $groups = $this->getAllowedStaffGroups(false);

            $group = $this->userLoader->getGroup($groupId);

            if (isset($groups[$groupId])) {
                $this->_setVar('current_user_group', $groupId);
                $this->_setVar('current_user_role',  $group->getRole());
            } elseif ($group->isActive()) {
                throw new \Gems\Exception($this->_('No access to group'), 403, null, sprintf(
                        $this->_('You are not allowed to switch to the %s group.'),
                        $group->getName()
                        ));
            } else {
                throw new \Gems\Exception($this->_('No access to group'), 403, null, sprintf(
                        $this->_('You cannot switch to an inactive or non-existing group.')
                        ));
            }
        }
        return $this;
    }

    /**
     * Set the locale for this user..
     *
     * @param string $locale
     * @return \Gems\User\User (continuation pattern)
     */
    public function setLocale($locale)
    {
        $this->_setVar('user_locale', (string) $locale);
        return $this;
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param string $password
     * @return \Gems\User\User (continuation pattern)
     */
    public function setPassword($password)
    {
        $this->definition->setPassword($this, $password);
        $this->setPasswordResetRequired(false);
        $this->refresh();   // force refresh
        return $this;
    }

    /**
     *
     * @param boolean $reset
     * @return \Gems\User\User  (continuation pattern)
     */
    public function setPasswordResetRequired($reset = true)
    {
        $this->_setVar('user_password_reset', (boolean) $reset);
        return $this;
    }

    /**
     * Set the Request object
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return \Gems\User\User
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     *
     * @param string $option One of the EmbedLoader->listCrumbOptions() options
     * @return $this
     */
    public function setSessionCrumbs($option)
    {
        $this->_setVar('current_user_crumbs', $option);

        return $this;
    }

    /**
     *
     * @param boolean $option When true we're working in a frame
     * @return $this
     */
    public function setSessionFramed($option = true)
    {
        $this->_setVar('current_user_framed', $option);

        return $this;
    }

    /**
     *
     * @param string $layout Name of a .[html layout file
     * @return $this
     */
    public function setSessionMvcLayout($layout)
    {
        $this->_setVar('current_user_layout', $layout);

        return $this;
    }

    /**
     * @param $patientNr
     * @param $orgId
     * @return $this
     */
    public function setSessionPatientNr($patientNr, $orgId)
    {
        $this->_setVar('current_user_patNr', $patientNr);
        $this->_setVar('current_user_orgId', $orgId);

        return $this;
    }
    
    /**
     *
     * @param string $style One of the escort->getStyle styles
     * @return $this
     */
    public function setSessionStyle($style)
    {
        $this->_setVar('current_user_style', $style);

        return $this;
    }

    /**
     * Set the parameters where the survey should return to
     *
     * @param mixed $return \Zend_Controller_Request_Abstract, array of something that can be turned into one.
     * @return \Gems\User\User
     */
    public function setSurveyReturn($return = null)
    {
        if (null === $return) {
            $this->_unsetVar('surveyReturn');
            return $this;
        }

        if ($return instanceof \Zend_Controller_Request_Abstract) {
            $return = $return->getParams();
        } elseif (! is_array($return)) {
            $return = \MUtil\Ra::to($return);
        }
        if (array_key_exists('action', $return) && 'autofilter' == $return['action']) {
            $return['action'] = 'index';
        }

        $return = array_filter($return);
        // \MUtil\EchoOut\EchoOut::track($return);

        $this->_setVar('surveyReturn', $return);

        return $this;
    }

    /**
     *
     * @param string TwoFactorAuthenticatorInterface $authenticator
     * @param string $newKey
     * @param boolean $enabled
     * @return $this
     */
    public function setTwoFactorKey(TwoFactorAuthenticatorInterface $authenticator, $newKey, $enabled = null)
    {
        // Make sure the authclass is part of the data
        $authClass = get_class($authenticator);
        $authFind  = '\\User\\TwoFactor\\';
        $pos = strrpos($authClass, $authFind);
        if ($pos) {
            $authClass = substr($authClass, $pos + strlen($authFind));
        }

        $newValue = $authClass . TwoFactorAuthenticatorInterface::SEPERATOR . $newKey;

        $this->_setVar('user_two_factor_key', $newValue);
        if (null !== $enabled) {
            $this->_setVar('user_enable_2factor', $enabled ? 1 : 0);
        }

        $this->definition->setTwoFactorKey($this, $newValue, $enabled);

        return $this;
    }

    /**
     * Switch to new locale
     *
     * @param string $locale Current if omitted
     * @return boolean true if cookie was set
     */
    public function switchLocale($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
            if (null === $locale) {
                /* $site = $this->util->getSites()->getSiteForCurrentUrl();
                if ($site) {
                    $locale = $site->getLocale();
                } */
            }
        } 
        if ($this->getLocale() != $locale) {
            $this->setLocale($locale);
        }

        if ($locale !== null) {
            $this->locale->setCurrentLanguage($locale);
            $this->translate->setLocale($locale);
            return \Gems\Cookies::setLocale($locale, $this->basepath->getBasePath());
        }

        return false;
    }

    /**
     * Unsets this user as the current user.
     *
     * This means that the data about this user will no longer be stored in a session.
     *
     * @param boolean $signalLoader Do not set, except from UserLoader
     * @return \Gems\User\User (continuation pattern)
     */
    public function unsetAsCurrentUser($signalLoader = true)
    {
        // When $oldStore is a \Zend_Session_Namespace, then this user is already the current user.
        if ($this->isCurrentUser()) {
            // Get the current variables
            $oldStore = $this->_vars;

            $this->_vars = new \ArrayObject();
            $this->_vars->setFlags(\ArrayObject::STD_PROP_LIST);

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
