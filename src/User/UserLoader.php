<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;

use Gems\Exception\AuthenticationException;
use Gems\User\Group;
use Laminas\ServiceManager\ServiceManager;

/**
 * Loads users.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class UserLoader extends \Gems\Loader\TargetLoaderAbstract
{
    /**
     * The org ID for no organization
     */
    const SYSTEM_NO_ORG  = -1;

    const CONSOLE_USER_ID = 2;

    /**
     * The user id used for the project user
     */
    const SYSTEM_USER_ID = 1;

    const UNKNOWN_USER_ID = 0;

    /**
     * User class constants
     */
    const USER_CONSOLE    = 'ConsoleUser';
    const USER_NOLOGIN    = 'NoLogin';
    const USER_LDAP       = 'LdapUser';
    const USER_PROJECT    = 'ProjectUser';
    const USER_RADIUS     = 'RadiusUser';
    const USER_RESPONDENT = 'RespondentUser';
    const USER_STAFF      = 'StaffUser';

    /**
     * When true a user is allowed to login to a different organization than the
     * one that provides an account. See GetUserClassSelect for the possible options
     * but be aware that duplicate accounts could lead to problems. To avoid
     * problems you can always use the organization switch AFTER login.
     *
     * @var boolean
     */
    public $allowLoginOnOtherOrganization = false;

    /**
     * When true a user is allowed to login without specifying an organization
     * See GetUserClassSelect for the possible options
     * but be aware that duplicate accounts could lead to problems. To avoid
     * problems you can always use the organization switch AFTER login.
     *
     * @var boolean
     */
    public $allowLoginOnWithoutOrganization = false;

    /**
     * When true Respondent members can use their e-mail address as login name
     * @var boolean
     */
    public $allowRespondentEmailLogin = false;

    /**
     * When true Staff members can use their e-mail address as login name
     * @var boolean
     */
    public $allowStaffEmailLogin = false;

    /**
     * Allows sub classes of \Gems\Loader\LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected ?string $cascade = 'User';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var User
     */
    protected $_currentLegacyUser;
    
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Zend_Session_Namespace
     */
    protected $session;

    /**
     * @var \Zend_Translate_Adapter
     */
    protected $translate;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        // Make sure \Gems\User\User gets userLoader variable.
        $extras['userLoader'] = $this;

        $this->addRegistryContainer($extras);

        return true;
    }

    /**
     * Returns a user object, that may be empty if no user exist.
     *
     * @param string $login_name
     * @param int $organization
     * @param string $userClassName
     * @param int $userId The person creating the user.
     * @return \Gems\User\User Newly created
     */
    public function createUser($login_name, $organization, $userClassName, $userId)
    {
        $now = new \MUtil\Db\Expr\CurrentTimestamp();;

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
            $where = implode(' ', $select->getPart(\Zend_Db_Select::WHERE));
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
     * @param \Gems\User\UserDefinitionInterface $definition
     * @param string $defName Optional
     * @return array
     */
    public function ensureDefaultUserValues(array $values, \Gems\User\UserDefinitionInterface $definition, $defName = null)
    {
        if (! isset($values['user_active'])) {
            $values['user_active'] = true;
        }
        if (! isset($values['user_staff'])) {
            $values['user_staff'] = $definition->isStaff();
        }
        if (! isset($values['user_resetkey_valid'])) {
            $values['user_resetkey_valid'] = false;
        }
        if (! isset($values['user_two_factor_key'])) {
            $values['user_two_factor_key'] = null;
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
        $output = array(
            self::USER_STAFF  => $this->translate->_('Db storage'),
            self::USER_RADIUS => $this->translate->_('Radius storage'),
        );

        if (isset($this->config['ldap'])) {
            $output[self::USER_LDAP] = $this->translate->_('LDAP');
        }
        asort($output);

        return $output;
    }

    public function getConsoleUser(): User
    {
        return $this->loadUser(self::USER_CONSOLE, 70, 'console');
    }
    
    public function getCurrentUser() : User
    {
        if (! $this->_currentLegacyUser) {
            $container = \MUtil\Model::getSource()->getContainer();
            if ($container instanceof ServiceManager) {
                $this->_currentLegacyUser = $container->get('LegacyCurrentUser');
            }
        }
        
        return $this->_currentLegacyUser;
    }

    /**
     * Returns a group object, initiated from the database or from
     * Group::$_noGroup when the database does not yet exist.
     *
     * @param int $groupId Group id
     * @return \Gems\User\Group
     */
    public function getGroup($groupId)
    {
        static $groups = array();

        if (! isset($groups[$groupId])) {
            $groups[$groupId] = $this->_loadClass('Group', true, array($groupId));
    }

        return $groups[$groupId];
    }

    /**
     *
     * @return array  id => label
     */
    public function getGroupTwoFactorNotSetOptions()
    {
        return [
            Group::NO_TWO_FACTOR_INSIDE_ONLY   => $this->translate->_('Allowed only in optional IP Range'),
            // Group::NO_TWO_FACTOR_SETUP_INSIDE  => $this->translate->_('Only in optional, setup required'),
            // Group::NO_TWO_FACTOR_SETUP_OUTSIDE => $this->translate->_('Allowed in allowed, setup required'),
            Group::NO_TWO_FACTOR_ALLOWED       => $this->translate->_('Allowed in "allowed from" IP Range'),
        ];
    }

    /**
     *
     * @return array  id => label
     */
    public function getGroupTwoFactorSetOptions()
    {
        return [
            Group::TWO_FACTOR_SET_REQUIRED      => $this->translate->_('Always required - even in optional IP Range'),
            Group::TWO_FACTOR_SET_OUTSIDE_ONLY  => $this->translate->_('Required - except in optional IP Range'),
            Group::TWO_FACTOR_SET_DISABLED      => $this->translate->_('Disabled - never ask'),
        ];
    }

    /**
     * @return string[] default array for when no organizations have been created
     */
    public static function getNotOrganizationArray()
    {
        return [self::SYSTEM_NO_ORG => 'create db first'];
    }

    /**
     * Returns an organization object, initiated from the database or from
     * self::$_noOrganization when the database does not yet exist.
     *
     * @param int $organizationId Optional, uses current user or url when empty
     * @return \Gems\User\Organization
     */
    public function getOrganization($organizationId = null)
    {
        static $organizations = array();

        if (null === $organizationId) {
            $user = $this->getCurrentUser();

            $organizationId = intval($user->getCurrentOrganizationId());
        }

        if (! isset($organizations[$organizationId])) {
            $organizations[$organizationId] = $this->_loadClass(
                    'Organization',
                    true,
                    array($organizationId, $this->getAvailableStaffDefinitions())
                    );
        }

        return $organizations[$organizationId];
    }

    /**
     * Returns the current organization according to the current site url.
     *
     * @return int An organization id or null
     * @deprecated since version 1.9.1
     */
    public function getOrganizationIdByUrl()
    {
        return null;
    }

    /**
     * Returns the current organization according to the current site url.
     *
     * @static array $urls An array of url => orgId values
     * @return array url => orgId
     * @deprecated since version 1.9.1
     */
    public function getOrganizationUrls()
    {
        return [];
//        static $urls;
//
//        if (! is_array($urls)) {
//            if ($this->cache) {
//                $cacheId = GEMS_PROJECT_NAME . '__' . strtr(get_class($this), '\\/', '__') . '__organizations_url';
//                $urls = $this->cache->load($cacheId);
//            } else {
//                $cacheId = false;
//            }
//
//            // When we don't use cache or cache reports 'false' for a miss or expiration
//            // then try to reload the data
//            if ($cacheId === false || $urls === false) {
//                $urls = array();
//                try {
//                    $data = $this->db->fetchPairs(
//                            "SELECT gor_id_organization, gor_url_base
//                                FROM gems__organizations
//                                WHERE gor_active=1 AND gor_url_base IS NOT NULL"
//                            );
//                } catch (\Zend_Db_Exception $zde) {
//                    // Table might not be filled
//                    $data = array();
//                }
//                foreach ($data as $orgId => $urlsBase) {
//                    foreach (explode(' ', $urlsBase) as $url) {
//                        if ($url) {
//                            $urls[$url] = $orgId;
//                        }
//                    }
//                }
//
//                if ($cacheId) {
//                    $this->cache->save($urls, $cacheId, array('organization', 'organizations'));
//                }
//            }
//            // \MUtil\EchoOut\EchoOut::track($urls);
//        }
//
//        return $urls;
    }

    /**
     * Get password weakness checker.
     *
     * @return \Gems\User\PasswordChecker
     */
    public function getPasswordChecker()
    {
        return $this->_getClass('passwordChecker');
    }

    /**
     * Returns a reset form for handling both the incoming request and the outgoing reset request
     *
     * @param mixed $args_array \MUtil\Ra::args array for LoginForm initiation.
     * @return \Gems\User\Form\ResetRequestForm
     */
    public function getResetRequestForm($args_array = null)
    {
        $args = \MUtil\Ra::args(func_get_args());

        return $this->_loadClass('Form\\ResetRequestForm', true, array($args));
    }

    /**
     * Returns a user object, that may be empty if no user exist.
     *
     * @param string $login_name
     * @param int $currentOrganization
     * @return \Gems\User\User But ! ->isActive when the user does not exist
     */
    public function getUser($login_name, $currentOrganization)
    {
        $user = $this->getUserClass($login_name, $currentOrganization);

        if ($this->allowLoginOnWithoutOrganization && (! $currentOrganization)) {
            $user->setCurrentOrganization($user->getBaseOrganizationId());
        } else {
            if (! $currentOrganization) {
                $currentOrganization = self::SYSTEM_NO_ORG;
            }
            // Check: can the user log in as this organization, if not load non-existing user
            if (! $user->isAllowedOrganization($currentOrganization)) {
                throw new AuthenticationException('disallowed organization');
            }

            $user->setCurrentOrganization($currentOrganization);
        }

        return $user;
    }

    public function getUserOrNull(string $loginName, int $organizationId): ?User
    {
        try {
            $user = $this->getUser($loginName, $organizationId);
            if ($user->getUserDefinitionClass() === UserLoader::USER_NOLOGIN) {
                throw new \Exception('Caught no-login user');
            }

            return $user;
        } catch (AuthenticationException) {
            return null;
        }
    }

    /**
     * Get the user having the reset key specified
     *
     * @param string $resetKey
     * @return \Gems\User\User|null
     */
    public function getUserByResetKey($resetKey)
    {
        if ((null == $resetKey) || (0 == strlen(trim($resetKey)))) {
            return null;
        }

        $select = $this->db->select();
        $select->from('gems__user_passwords', array())
                ->joinLeft('gems__user_logins', 'gup_id_user = gul_id_user', array("gul_user_class", 'gul_id_organization', 'gul_login'))
                ->where('gup_reset_key = ?', $resetKey);

        if ($row = $this->db->fetchRow($select, null, \Zend_Db::FETCH_NUM)) {
            // \MUtil\EchoOut\EchoOut::track($row);
            return $this->loadUser($row[0], $row[1], $row[2]);
        }

        return null;
    }

    /**
     * Get a staff user using the $staff_id
     *
     * @param int $staff_id
     * @return \Gems\User\User But ! ->isActive when the user does not exist
     */
    public function getUserByStaffId($staff_id)
    {
        $data = $this->db->fetchRow("SELECT gsf_login, gsf_id_organization FROM gems__staff WHERE gsf_id_user = ?", $staff_id);

        // \MUtil\EchoOut\EchoOut::track($data);
        if (false == $data) {
            throw new AuthenticationException('No staff found');
            // $data = array('gsf_login' => null, 'gsf_id_organization' => null);
        }

        return $this->getUser($data['gsf_login'], $data['gsf_id_organization']);
    }

    public function getUserOrNullByStaffId(int $staffId): ?User
    {
        try {
            $user = $this->getUserByStaffId($staffId);
            if ($user->getUserDefinitionClass() === UserLoader::USER_NOLOGIN) {
                throw new \Exception('Caught no-login user');
            }

            return $user;
        } catch (AuthenticationException) {
            return null;
        }
    }

    /**
     * Returns the name of the user definition class of this user.
     *
     * @param string $login_name
     * @param int $organization
     * @return \Gems\User\User But ! ->isActive when the user does not exist
     */
    protected function getUserClass($login_name, $organization)
    {
        //First check for project user, as this one can run without a db
        if ((null !== $login_name) && $this->isProjectUser($login_name)) {
            return $this->loadUser(self::USER_PROJECT, $organization, $login_name);
        }


        if (null == $login_name) {
            throw new AuthenticationException('empty login name');
        }

        if (!$this->allowLoginOnWithoutOrganization) {
            if ((null == $organization) || (! intval($organization))) {
                throw new AuthenticationException('invalid organization');
            }
        }

        try {
            $select = $this->getUserClassSelect($login_name, $organization);

            if ($row = $this->db->fetchRow($select, null, \Zend_Db::FETCH_NUM)) {
                if ($row[3] == 1 || $this->allowLoginOnOtherOrganization === true) {
                    // \MUtil\EchoOut\EchoOut::track($row);
                    return $this->loadUser($row[0], $row[1], $row[2]);
                }
            }

        } catch (\Zend_Db_Exception $e) {
            // Intentional fall through
            // \MUtil\EchoOut\EchoOut::track($e->getMessage());
        }

        throw new AuthenticationException('no login');
    }

    /**
     * Returns a select statement to find a corresponding user.
     *
     * @param string $login_name
     * @param int $organization
     * @return \Zend_Db_Select
     */
    protected function getUserClassSelect($login_name, $organization)
    {
        $select = $this->db->select();

        /**
         * tolerance field:
         * 1 - login and organization match
         * 2 - login found in an organization with access to the requested organization
         * 3 - login found in another organization without rights to the requested organiation
         *     (could be allowed due to privilege with rights to ALL organizations)
         */
        $select->from('gems__user_logins', array("gul_user_class", 'gul_id_organization', 'gul_login'))
                ->where('gul_can_login = 1');

        if ($this->allowLoginOnWithoutOrganization && !$organization) {
            $select->columns(new \Zend_Db_Expr('1 AS tolerance'));
        } else {
            $select->from('gems__organizations', array())
                ->columns(new \Zend_Db_Expr(
                        "CASE
                            WHEN gor_id_organization = gul_id_organization THEN 1
                            WHEN gor_accessible_by LIKE CONCAT('%:', gul_id_organization, ':%') THEN 2
                            ELSE 3
                        END AS tolerance"))
                ->where('gor_active = 1')
                ->where('gor_id_organization = ?', $organization)
                ->order('tolerance');
        }
        $wheres[] = $this->db->quoteInto('gul_login = ?', $login_name);
        $isEmail  = str_contains($login_name, '@');

        if ($isEmail && $this->allowStaffEmailLogin) {
            $rows = $this->db->fetchAll(
                    "SELECT gsf_login, gsf_id_organization FROM gems__staff WHERE gsf_email = ?",
                    $login_name
                    );
            if ($rows) {
                foreach ($rows as $row) {
                    $wheres[] = $this->db->quoteInto('gul_login = ? AND ', $row['gsf_login'])
                            . $this->db->quoteInto('gul_id_organization = ?', $row['gsf_id_organization']);
                }
            }
        }
        if ($isEmail && $this->allowRespondentEmailLogin) {
            $rows = $this->db->fetchAll(
                    "SELECT gr2o_patient_nr, gr2o_id_organization FROM gems__respondent2org  "
                    . "INNER JOIN gems__respondents WHERE gr2o_id_user = grs_id_user AND gr2o_email = ?",
                    $login_name
                    );
            if ($rows) {
                foreach ($rows as $row) {
                    $wheres[] = $this->db->quoteInto('gul_login = ? AND ', $row['gr2o_patient_nr'])
                            . $this->db->quoteInto('gul_id_organization = ?', $row['gr2o_id_organization']);
                }
            }
        }
        // Add search fields
        $select->where(new \Zend_Db_Expr('(' . implode(') OR (', $wheres) . ')'));
        // \MUtil\EchoOut\EchoOut::track($select->__toString());

        return $select;
    }

    /**
     * Retrieve a userdefinition, so we can check it's capabilities without
     * instantiating a user.
     *
     * @param string $userClassName
     * @return \Gems\User\UserDefinitionInterface
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
     * @return \Gems\User\User But ! ->isActive when the user does not exist
     */
    protected function loadUser($defName, $userOrganization, $userName)
    {
        $definition = $this->getUserDefinition($defName);

        $values = $definition->getUserData($userName, $userOrganization);
        // \MUtil\EchoOut\EchoOut::track($defName, $userName, $userOrganization, $values);

        $values = $this->ensureDefaultUserValues($values, $definition, $defName);
        // \MUtil\EchoOut\EchoOut::track($values, $userName, $userOrganization, $defName);

        return $this->_loadClass('User', true, array($values, $definition));
    }

    public function setLegacyCurrentUser(User $_currentLegacyUser)
    {
        $this->_currentLegacyUser = $_currentLegacyUser;
    }
    
    /**
     * Check for password weakness.
     *
     * @param \Gems\User\User $user The user for e.g. name checks
     * @param string $password Or null when you want a report on all the rules for this password.
     * @return mixed String or array of strings containing warning messages
     */
    public function reportPasswordWeakness(\Gems\User\User $user, $password = null)
    {
        return $user->reportPasswordWeakness($password);
    }
}