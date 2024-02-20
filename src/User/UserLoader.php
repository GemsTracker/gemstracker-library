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

use Gems\Cache\HelperAdapter;
use Gems\Db\ResultFetcher;
use Gems\Exception\AuthenticationException;
use Gems\Hydrator\NamingStrategy\PrefixedUnderscoreNamingStrategy;
use Gems\User\Embed\EmbeddedUserData;
use Laminas\Db\Exception\RuntimeException;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Select;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\Hydrator\Strategy\BooleanStrategy;
use Laminas\Hydrator\Strategy\DateTimeFormatterStrategy;
use Laminas\Hydrator\Strategy\DateTimeImmutableFormatterStrategy;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

/**
 * Loads users.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class UserLoader
{
    /**
     * The org ID for no organization
     */
    public const SYSTEM_NO_ORG  = -1;

    public const CONSOLE_USER_ID = 2;

    /**
     * The user id used for the project user
     */
    public const SYSTEM_USER_ID = 1;

    public const UNKNOWN_USER_ID = 0;

    /**
     * User class constants
     */
    public const USER_CONSOLE    = 'ConsoleUser';
    public const USER_NOLOGIN    = 'NoLogin';
    public const USER_LDAP       = 'LdapUser';
    public const USER_PROJECT    = 'ProjectUser';
    public const USER_RADIUS     = 'RadiusUser';
    public const USER_RESPONDENT = 'RespondentUser';
    public const USER_STAFF      = 'StaffUser';

    /**
     * When true a user is allowed to login to a different organization than the
     * one that provides an account. See GetUserClassSelect for the possible options
     * but be aware that duplicate accounts could lead to problems. To avoid
     * problems you can always use the organization switch AFTER login.
     *
     * @var boolean
     */
    public bool $allowLoginOnOtherOrganization = false;

    /**
     * When true a user is allowed to login without specifying an organization
     * See GetUserClassSelect for the possible options
     * but be aware that duplicate accounts could lead to problems. To avoid
     * problems you can always use the organization switch AFTER login.
     *
     * @var boolean
     */
    public bool $allowLoginOnWithoutOrganization = false;

    /**
     * When true Respondent members can use their e-mail address as login name
     * @var boolean
     */
    public bool $allowRespondentEmailLogin = false;

    /**
     * When true Staff members can use their e-mail address as login name
     * @var boolean
     */
    public bool $allowStaffEmailLogin = false;

    protected User|null $currentUser;

    protected array $_embedderData = [];

    /**
     * @var UserDefinitionInterface[]
     */
    protected array $userDefinitions = [];

    protected HydratorInterface|null $userHydrator = null;

    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
        protected readonly TranslatorInterface $translator,
        protected readonly ProjectOverloader $projectOverloader,
        protected readonly PasswordChecker $passwordChecker,
        protected readonly HelperAdapter $cache,
    )
    {
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
    public function createUser(string $login_name, int $organization, string $userClassName, int $userId): User
    {
        $now = new Expression('CURRENT_TIMESTAMP');

        $values['gul_user_class'] = $userClassName;
        $values['gul_can_login']  = 1;
        $values['gul_changed']    = $now;
        $values['gul_changed_by'] = $userId;

        $select = $this->resultFetcher->getSelect('gems__user_logins');
        $select->columns(['gul_id_user'])
            ->where([
                'gul_login' => $login_name,
                'gul_id_organization' => $organization,
            ])
            ->limit(1);

        // Update class definition if it already exists
        if ($login_id = $this->resultFetcher->fetchOne($select)) {
            $where = [
                'gul_login' => $login_name,
                'gul_id_organization' => $organization,
            ];

            $this->resultFetcher->updateTable('gems__user_logins', $values, $where);
        } else {
            $values['gul_login']           = $login_name;
            $values['gul_id_organization'] = $organization;
            $values['gul_created']         = $now;
            $values['gul_created_by']      = $userId;

            $this->resultFetcher->insertIntoTable('gems__user_logins', $values);
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
    public function ensureDefaultUserValues(array $values, UserDefinitionInterface $definition, string|null $defName = null): array
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
            $values['user_definition'] = $defName;
        }

        return $values;
    }

    /**
     * Get userclass / description array of available UserDefinitions for respondents
     *
     * @return array
     */
    public function getAvailableRespondentDefinitions(): array
    {
        $definitions = [
            self::USER_RESPONDENT => $this->translator->_('Db storage')
        ];

        return $definitions;
    }

    /**
     * Get userclass / description array of available UserDefinitions for staff
     *
     * @return array
     */
    public function getAvailableStaffDefinitions(): array
    {
        $output = [
            self::USER_STAFF  => $this->translator->_('Db storage'),
            self::USER_RADIUS => $this->translator->_('Radius storage'),
        ];

        if (isset($this->config['ldap'])) {
            $output[self::USER_LDAP] = $this->translator->_('LDAP');
        }
        asort($output);

        return $output;
    }

    public function getConsoleUser(): User
    {
        return $this->loadUser(self::USER_CONSOLE, 70, 'console');
    }
    
    public function getCurrentUser() : User|null
    {
        return $this->currentUser;
    }

    /**
     * If user is an embedder, return the EmbedderUserData object
     *
     * @return EmbeddedUserData|null
     */
    public function getEmbedderData(User $user): EmbeddedUserData|null
    {
        if (! $user->isEmbedded()) {
            return null;
        }
        $userId = $user->getUserId();

        if (isset($this->_embedderData[$userId])) {
            return $this->_embedderData[$userId];
        }

        $this->_embedderData[$userId] = $this->projectOverloader->create('User\\Embed\\EmbeddedUserData', $userId);

        return $this->_embedderData[$userId];
    }

    /**
     * Returns a group object, initiated from the database or from
     * Group::$_noGroup when the database does not yet exist.
     *
     * @param int $groupId Group id
     * @return \Gems\User\Group
     */
    public function getGroup(int $groupId): Group
    {
        static $groups = [];

        if (! isset($groups[$groupId])) {
            $groups[$groupId] = $this->projectOverloader->create('User\\Group', $groupId);
        }

        return $groups[$groupId];
    }

    /**
     *
     * @return array  id => label
     */
    public function getGroupTwoFactorNotSetOptions(): array
    {
        return [
            Group::NO_TWO_FACTOR_INSIDE_ONLY   => $this->translator->_('Allowed only in optional IP Range'),
            // Group::NO_TWO_FACTOR_SETUP_INSIDE  => $this->translator->_('Only in optional, setup required'),
            // Group::NO_TWO_FACTOR_SETUP_OUTSIDE => $this->translator->_('Allowed in allowed, setup required'),
            Group::NO_TWO_FACTOR_ALLOWED       => $this->translator->_('Allowed in "allowed from" IP Range'),
        ];
    }

    /**
     *
     * @return array  id => label
     */
    public function getGroupTwoFactorSetOptions(): array
    {
        return [
            Group::TWO_FACTOR_SET_REQUIRED      => $this->translator->_('Always required - even in optional IP Range'),
            Group::TWO_FACTOR_SET_OUTSIDE_ONLY  => $this->translator->_('Required - except in optional IP Range'),
            Group::TWO_FACTOR_SET_DISABLED      => $this->translator->_('Disabled - never ask'),
        ];
    }

    /**
     * @return string[] default array for when no organizations have been created
     */
    public static function getNotOrganizationArray(): array
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
    public function getOrganization(int|null $organizationId = null): Organization
    {
        static $organizations = array();

        if (null === $organizationId) {
            $user = $this->getCurrentUser();

            $organizationId = $user->getCurrentOrganizationId();
        }

        if (! isset($organizations[$organizationId])) {
            $organizations[$organizationId] = $this->projectOverloader->create('User\\Organization', $organizationId, $this->getAvailableStaffDefinitions());
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
     * Get password weakness checker.
     *
     * @return \Gems\User\PasswordChecker
     */
    public function getPasswordChecker(): PasswordChecker
    {
        return $this->passwordChecker;
    }

    /**
     * Returns a user object, that may be empty if no user exist.
     *
     * @param string $loginName
     * @param int $currentOrganizationId
     * @return \Gems\User\User But ! ->isActive when the user does not exist
     */
    public function getUser(string $loginName, int $currentOrganizationId): User
    {
        $user = $this->getUserClass($loginName, $currentOrganizationId);

        if ($this->allowLoginOnWithoutOrganization && (! $currentOrganizationId)) {
            $user->setCurrentOrganizationId($user->getBaseOrganizationId());
        } else {
            if (! $currentOrganizationId) {
                $currentOrganizationId = self::SYSTEM_NO_ORG;
            }
            // Check: can the user log in as this organization, if not load non-existing user
            if (! $user->isAllowedOrganization($currentOrganizationId)) {
                throw new AuthenticationException('disallowed organization');
            }

            $user->setCurrentOrganizationId($currentOrganizationId);
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
    public function getUserByResetKey(string $resetKey): User|null
    {
        if ((null == $resetKey) || (0 == strlen(trim($resetKey)))) {
            return null;
        }

        $select = $this->resultFetcher->getSelect('gems__user_passwords')
            ->join('gems__user_logins', 'gup_id_user = gul_id_user', ['gul_user_class', 'gul_id_organization', 'gul_login'], Select::JOIN_LEFT)
            ->where([
                'gup_reset_key' => $resetKey,
            ]);

        if ($row = $this->resultFetcher->fetchRow($select)) {
            // \MUtil\EchoOut\EchoOut::track($row);
            return $this->loadUser($row['gul_user_class'], $row['gul_id_organization'], $row['gul_login']);
        }

        return null;
    }

    /**
     * Get a staff user using the $staff_id
     *
     * @param int $staffId
     * @return \Gems\User\User But ! ->isActive when the user does not exist
     */
    public function getUserByStaffId(int $staffId): User|null
    {
        $data = $this->resultFetcher->fetchRow("SELECT gsf_login, gsf_id_organization FROM gems__staff WHERE gsf_id_user = ?", [$staffId]);

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
     * @param string $loginName
     * @param int $organizationId
     * @return \Gems\User\User But ! ->isActive when the user does not exist
     */
    protected function getUserClass(string $loginName, int $organizationId): User
    {
        //First check for project user, as this one can run without a db
        if ((null !== $loginName) && $this->isProjectUser($loginName)) {
            return $this->loadUser(self::USER_PROJECT, $organizationId, $loginName);
        }


        if (null == $loginName) {
            throw new AuthenticationException('empty login name');
        }

        if (!$this->allowLoginOnWithoutOrganization) {
            if ((null == $organizationId) || (! intval($organizationId))) {
                throw new AuthenticationException('invalid organization');
            }
        }

        try {
            $select = $this->getUserClassSelect($loginName, $organizationId);

            if ($row = $this->resultFetcher->fetchRow($select)) {
                if ($row['tolerance'] == 1 || $this->allowLoginOnOtherOrganization === true) {
                    // \MUtil\EchoOut\EchoOut::track($row);
                    return $this->loadUser($row['gul_user_class'], $row['gul_id_organization'], $row['gul_login']);
                }
            }

        } catch (RuntimeException $e) {
            // Intentional fall through
            // \MUtil\EchoOut\EchoOut::track($e->getMessage());
        }

        throw new AuthenticationException('no login');
    }

    /**
     * Returns a select statement to find a corresponding user.
     *
     * @param string $loginName
     * @param int $organizationId
     * @return Select
     */
    protected function getUserClassSelect(string $loginName, int $organizationId): Select
    {
        /**
         * tolerance field:
         * 1 - login and organization match
         * 2 - login found in an organization with access to the requested organization
         * 3 - login found in another organization without rights to the requested organiation
         *     (could be allowed due to privilege with rights to ALL organizations)
         */
        $select = $this->resultFetcher->getSelect('gems__user_logins');
        $columns = [
            'gul_user_class',
            'gul_id_organization',
            'gul_login',
        ];
        $select->where([
            'gul_can_login' => 1,
        ]);

        if ($this->allowLoginOnWithoutOrganization && !$organizationId) {
            //$select->columns(new \Zend_Db_Expr('1 AS tolerance'));
            $columns['tolerance'] = new Expression('1');
            $select->columns($columns);
        } else {
            $columns['tolerance'] = new Expression("CASE
                            WHEN gor_id_organization = gul_id_organization THEN 1
                            WHEN gor_accessible_by LIKE CONCAT('%:', gul_id_organization, ':%') THEN 2
                            ELSE 3
                        END");
            $select->join('gems__organizations', 'gul_id_organization = gor_id_organization', [])
                ->columns($columns)
                ->where([
                    'gor_active' => 1,
                    'gor_id_organization' => $organizationId,
                ])
                ->order('tolerance');
        }
        //$wheres[] = 'gul_login = ?', $login_name);
        $isEmail  = str_contains($loginName, '@');

        $where = new Predicate();
        $where->nest()
            ->equalTo('gul_login', $loginName);

        if ($isEmail && $this->allowStaffEmailLogin) {
            $rows = $this->resultFetcher->fetchAll(
                    "SELECT gsf_login, gsf_id_organization FROM gems__staff WHERE gsf_email = ?",
                    [$loginName]
                    );
            if ($rows) {
                foreach ($rows as $row) {
                    $where->nest()
                        ->equalTo('gul_login', $row['gsf_login'])
                        ->and
                        ->equalTo('gul_id_organization', $row['gsf_id_organization'])
                        ->unnest();
                }
            }
        }
        if ($isEmail && $this->allowRespondentEmailLogin) {
            $rows = $this->resultFetcher->fetchAll(
                    "SELECT gr2o_patient_nr, gr2o_id_organization FROM gems__respondent2org  "
                    . "INNER JOIN gems__respondents WHERE gr2o_id_user = grs_id_user AND gr2o_email = ?",
                    [$loginName]
                    );
            if ($rows) {
                foreach ($rows as $row) {
                    $where->nest()
                        ->equalTo('gul_login', $row['gr2o_patient_nr'])
                        ->and
                        ->equalTo('gul_id_organization', $row['gr2o_id_organization'])
                        ->unnest();
                }
            }
        }
        // Add search fields
        $select->where($where);

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
        if (!isset($this->userDefinitions[$userClassName])) {
            $this->userDefinitions[$userClassName] = $this->projectOverloader->create('User\\' . $userClassName . 'Definition');
        }

        return $this->userDefinitions[$userClassName];
    }

    protected function getUserHydrator(User $user): HydratorInterface
    {
        if ($this->userHydrator !== null) {
            return $this->userHydrator;
        }

        $cacheKey = HelperAdapter::cleanupForCacheId($user::class . 'Hydrator');
        if ($this->cache->hasItem($cacheKey)) {
            $this->userHydrator = $this->cache->getCacheItem($cacheKey);
            return $this->userHydrator;
        }

        $hydrator = new ReflectionHydrator();
        $hydrator->setNamingStrategy(new PrefixedUnderscoreNamingStrategy('user_'));

        $reflection = new \ReflectionClass($user);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $name = $type->getName();
                switch ($name) {
                    case 'bool':
                        $hydrator->addStrategy($property->getName(), new BooleanStrategy(1, 0));
                        break;
                    case 'DateTimeInterface':
                    case 'DateTimeImmutable':
                        $hydrator->addStrategy(
                            $property->getName(),
                            new DateTimeImmutableFormatterStrategy(
                                new DateTimeFormatterStrategy('Y-m-d H:i:s')
                            )
                        );
                        break;
                    default:
                        break;
                }
            }
        }
        $this->cache->setCacheItem($cacheKey, $hydrator);
        $this->userHydrator = $hydrator;
        return $this->userHydrator;
    }

    /**
     * Check: is this user the super user defined
     * in project.ini?
     *
     * @param string $loginName
     * @return bool
     */
    protected function isProjectUser(string $loginName): bool
    {
        return false;//$this->project->getSuperAdminName() == $login_name;
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

        $user = $this->projectOverloader->create('User\\User', $definition);
        $hydrator = $this->getUserHydrator($user);
        $hydrator->hydrate($values, $user);

        return $user;
    }

    public function setLegacyCurrentUser(User $currentUser): void
    {
        $this->currentUser = $currentUser;
    }
    
    /**
     * Check for password weakness.
     *
     * @param User $user The user for e.g. name checks
     * @param string $password Or null when you want a report on all the rules for this password.
     * @return string[] String or array of strings containing warning messages
     */
    public function reportPasswordWeakness(User $user, string|null$password = null): array
    {
        return [];
    }
}
