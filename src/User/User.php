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
use DateTimeInterface;
use Gems\AuthTfa\Method\OtpMethodInterface;
use Gems\Db\ResultFetcher;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Util\IpAddress;
use Gems\Util\Translated;
use Laminas\Db\Exception\RuntimeException;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Exception\InvalidArgumentException;

/**
 * User object that mimmicks the old $this->session behaviour
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class User
{
    protected int $id;
    protected string $login;

    protected bool $active;

    protected string $definition;
    protected int $baseOrgId;
    protected string|null $email = null;
    protected string|null $firstName = null;
    protected string|null $surnamePrefix = null;
    protected string|null $lastName = null;

    protected string|null $gender = null;
    protected int $group;
    protected string $role;

    protected int|null $currentGroup = null;
    protected int|null $currentOrganizationId = null;
    protected string|null $currentRole = null;
    protected bool $currentlyFramed = false;

    /**
     * List of active organizations that can be accessed by the current
     * logged in user.
     */
    protected array|null $allowedOrganizations = null;
    /**
     * List of active organizations that have or can have respondents for
     * the current logged in user.
     */
    protected array|null $allowedRespondentOrganizations = null;
    /**
     * List of active organizations to which new respondents can be added,
     * for the current logged in user.
     */
    protected array|null $newRespondentOrganizations = null;
    protected string|null $allowedIpRanges = null;
    protected string|null $phoneNumber = null;
    protected string $locale = 'en';
    protected bool $embedded = false;

    protected int $loginId;
    protected string|null $twoFactorKey = null;
    protected int $otpCount = 0;
    protected DateTimeInterface|null $otpRequested = null;
    protected string|null $sessionKey = null;

    protected bool $logout = false;

    protected bool $passwordResetRequired = false;
    protected bool $resetkeyValid = false;
    protected DateTimeInterface|null $passwordLastChanged;

    public function __construct(
        protected readonly UserDefinitionInterface $userDefinition,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly AccessRepository $accessRepository,
        protected readonly TrackDataRepository $trackDataRepository,
        protected readonly Acl $acl,
        protected readonly Translated $translatedUtil,
        protected readonly ResultFetcher $resultFetcher,
    ) {
    }

    /**
     * Return true if a password reset key can be created.
     *
     * @return bool
     */
    public function canResetPassword(): bool
    {
        return $this->isActive() && $this->userDefinition->canResetPassword($this);
    }

    public function canSaveTwoFactorKey()
    {
        return $this->userDefinition->canSaveTwoFactorKey();
    }

    /**
     * Return true if the password can be set.
     *
     * @return bool
     */
    public function canSetPassword(): bool
    {
        return $this->isActive() && $this->userDefinition->canSetPassword($this);
    }

    /**
     * Clear the multi-factor authentication key
     *
     * @return self
     */
    public function clearTwoFactorKey(): self
    {
        $this->twoFactorKey = null;
        $this->userDefinition->setTwoFactorKey($this, null);
        return $this;
    }

    public function getAllowedIPRanges(): string|null
    {
        return $this->allowedIpRanges;
    }

    public function getAllowedOrganizations(): array
    {
        if ($this->allowedOrganizations === null) {
            $this->refreshAllowedOrganizations();
        }
        return $this->allowedOrganizations;
    }

    /**
     * Return an array of organizationIds for all allowed organizations for
     * the current logged in user.
     *
     * @return array<int, int>
     */
    public function getAllowedOrganizationIds(): array
    {
        return array_keys($this->getAllowedOrganizations());
    }

    /**
     * Retrieve an array of groups the user is allowed to assign: his own group and all groups
     * he/she inherits rights from
     *
     * @param bool $current Return the current list or the original list when true
     * @return array
     */
    public function getAllowedStaffGroups(bool $current = true): array
    {
        // Always refresh because these values are otherwise not responsive to change
        $groupId  = $this->getGroupId($current);
        $groups   = $this->accessRepository->getActiveStaffGroups();
        $groupsAllowed = [];

        try {
            $setGroups     = $this->resultFetcher->fetchOne(
                "SELECT ggp_may_set_groups FROM gems__groups WHERE ggp_id_group = ?",
                [$groupId]
            );
            if ($setGroups) {
                $groupsAllowed = explode(',', $setGroups);
            }
        } catch (RuntimeException) {
            // The database might not be updated
            //$groupsAllowed = [];
        }

        $result = [];

        foreach ($groups as $id => $label) {
            if ((in_array($id, $groupsAllowed))) {
                $result[$id] = $groups[$id];
            }
        }
        natsort($result);

        return $result;
    }

    public function getBaseOrganization(): Organization
    {
        return $this->organizationRepository->getOrganization($this->baseOrgId);
    }

    public function getBaseOrganizationId(): int
    {
        return $this->baseOrgId;
    }

    public function getCurrentOrganization(): Organization
    {
        return $this->organizationRepository->getOrganization($this->getCurrentOrganizationId());
    }

    public function getCurrentOrganizationId(): int
    {
        if ($this->currentOrganizationId !== null) {
            return $this->currentOrganizationId;
        }
        return $this->baseOrgId;
    }

    /**
     * Get the propper Dear mr./mrs/ greeting of respondent
     * @return string
     */
    public function getDearGreeting(string|null $language = null): string
    {
        $genderDears = $this->translatedUtil->getGenderDear($language);

        $gender = $this->getGender();

        if (isset($genderDears[$gender])) {
            $greeting = $genderDears[$gender] . ' ';
        } else {
            $greeting = '';
        }

        return $greeting . $this->getLastName();
    }

    public function getDefaultNewStaffGroup(): string
    {
        $group = $this->getGroup();
        return $group->getDefaultNewStaffGroup();
    }

    public function getEmailAddress(): string|null
    {
        return $this->email;
    }

    public function getFirstName(): string|null
    {
        return $this->firstName;
    }

    /**
     * Returns the full users name (first, prefix, last).
     *
     * @return string
     */
    public function getFullName(bool $includeFirstName = true): string
    {
        $nameParts = [];
        if ($includeFirstName) {
            $nameParts[] = $this->firstName;
        }
        $nameParts[] = $this->surnamePrefix;
        $nameParts[] = $this->lastName;

        $name = join(' ', array_filter($nameParts));

        if ($name) {
            return $name;
        }

        return $this->getObfuscatedLoginName();
    }

    public function getGender(): string
    {
        return $this->gender ?? 'U';
    }

    /**
     * Returns the gender for use as part of a sentence, e.g. Dear Mr/Mrs
     *
     * In practice: starts lowercase
     *
     * @param string|null $locale
     * @return string
     */
    protected function getGenderGreeting(string|null $locale = null): string
    {
        $greetings = $this->translatedUtil->getGenderGreeting($locale);

        $gender = $this->getGender();

        if (isset($greetings[$gender])) {
            return $greetings[$gender];
        }
        return '';
    }

    /**
     * Returns the gender for use in stand-alone name display
     *
     * In practice: starts uppercase
     *
     * @param string $locale
     * @return string Greeting
     */
    public function getGenderHello(string $locale = null): string|null
    {
        $greetings = $this->translatedUtil->getGenderHello($locale);

        $gender = $this->getGender();

        if (isset($greetings[$gender])) {
            return $greetings[$gender];
        }
        return null;
    }

    /**
     * Returns a standard greeting for the current user.
     *
     * @param string $locale
     * @return string
     */
    public function getGreeting(string|null $locale = null): string
    {
        $greeting[] = $this->getGenderGreeting($locale);
        $greeting[] = $this->getFullName(false);
        array_filter($greeting);

        return join(' ', $greeting);
    }

    public function getGroup(bool $current = true): Group
    {
        $groupId = $this->getGroupId($current);
        return $this->accessRepository->getGroup($groupId);
    }

    public function getGroupId(bool $current = true): int
    {
        if ($current && $this->currentGroup !== null) {
            return $this->currentGroup;
        }
        return $this->group;
    }


    public function getLastName(): string
    {
        return $this->getFullName(false);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getLoginName(): string
    {
        return $this->login;
    }

    protected function getObfuscatedLoginName(): string
    {
        $name = $this->getLoginName();
        return substr($name, 0, 3) . str_repeat('*', max(5, strlen($name) - 2));
    }

    public function getOtpCount(): int
    {
        return $this->otpCount;
    }

    /**
     * Get the HOTP requested time
     */
    public function getOtpRequested(): DateTimeInterface|null
    {
        return $this->otpRequested;
    }

    /**
     * Return the number of days since last change of password
     *
     * @return int
     */
    public function getPasswordAge(): int
    {
        if ($this->passwordLastChanged instanceof \DateTimeInterface) {
            return abs($this->passwordLastChanged->diff(new DateTimeImmutable())->days);
        }
        return 0;
    }

    /**
     * Throw an exception if the organization ID is not an allowed organization.
     *
     * @param int|string $organizationId
     * @return void If the user has access to the organization
     * @throws \Gems\Exception If the user does not have access to the organization
     */
    public function assertAccessToOrganizationId(int|string $organizationId): void
    {
        $orgs = $this->getAllowedOrganizations();
        if (isset($orgs[$organizationId])) {
            return;
        }
        throw new \Gems\Exception('Inaccessible or unknown organization', 403);
    }

    /**
     * Throw an exception if the user doesn't have access to the track.
     *
     * @param int|string $trackId
     * @return void If the user has access to the track
     * @throws \Gems\Exception If the user does not have access to the track
     */
    public function assertAccessToTrackId(int|string $trackId): void
    {
        $organizationIds = $this->getAllowedOrganizationIds();
        // This is cached in the TrackDataRepository.
        $tracks = $this->trackDataRepository->getActiveTracksForOrgs($organizationIds);
        if (isset($tracks[$trackId])) {
            return;
        }
        throw new \Gems\Exception('Inaccessible or unknown track', 403);
    }

    /**
     * @return string[] An array of code names that identify which sets of password rules are applicable for this user
     */
    public function getPasswordCheckerCodes(): array
    {
        $codes = ['default'];
        $codes[] = $this->getCurrentOrganization()->getCode();
        $codes[] = $this->role;
        $codes[] = $this->currentRole;
        $codes[] = $this->definition;
        if ($this->isStaff()) {
            $codes[] = 'staff';
        }
        return array_values(array_filter($codes));
    }

    /**
     * Get the password history length from the config.
     *
     * @return int|null The password history length, or null if not configured.
     */
    public function getPasswordHistoryLength(): int|null
    {
        if (!isset($this->config['password']) || !is_array($this->config['password'])) {
            return null;
        }

        $historyLength = 0;
        $found = false;
        $codes = $this->getPasswordCheckerCodes();
        foreach ($codes as $code) {
            if (!isset($this->config['password'][$code]['historyLength'])) {
                continue;
            }
            if (!is_int($this->config['password'][$code]['historyLength'])) {
                continue;
            }
            if ($this->config['password'][$code]['historyLength'] > $historyLength) {
                $historyLength = $this->config['password'][$code]['historyLength'];
                $found = true;
            }
        }

        if ($found) {
            return $historyLength;
        }

        return null;
    }

    /**
     * Return a password reset key
     *
     * @return string|null
     */
    public function getPasswordResetKey(): string|null
    {
        return $this->userDefinition->getPasswordResetKey($this);
    }

    /**
     * Return a password reset key
     *
     * @return int hours valid
     */
    public function getPasswordResetKeyDuration(): int
    {
        return $this->userDefinition->getResetKeyDurationInHours();
    }

    /**
     * Return the (unfiltered) phonenumber if the user has one
     *
     * @return string|null
     */
    public function getPhonenumber(): string|null
    {
        return $this->phoneNumber;
    }

    /**
     * Get an array of OrgId => Org Name for all allowed organizations that can have
     * respondents for the current logged in user
     *
     * @return array
     */
    public function getRespondentOrganizations(): array
    {
        if ($this->allowedRespondentOrganizations === null) {
            $respondentOrganizations = $this->organizationRepository->getOrganizationsWithRespondents();
            $allowedOrganizations = $this->getAllowedOrganizations();

            $this->allowedRespondentOrganizations = array_intersect($respondentOrganizations, $allowedOrganizations);
        }
        return $this->allowedRespondentOrganizations;
    }

    /**
     * Get an array of OrgId => Org Name for all allowed organizations to which
     * new respondents can be added.
     *
     * @return array
     */
    public function getNewRespondentOrganizations(): array
    {
        if ($this->newRespondentOrganizations === null) {
            $respondentOrganizations = $this->organizationRepository->getOrganizationsOpenToRespondents();
            $allowedOrganizations = $this->getAllowedOrganizations();

            $this->newRespondentOrganizations = array_intersect($respondentOrganizations, $allowedOrganizations);
        }
        return $this->newRespondentOrganizations;
    }

    /**
     * Get an array of OrgId's for filtering on all allowed organizations that can have
     * respondents for the current logged in user
     *
     * @return array
     */
    public function getRespondentOrgFilter(): array
    {
        return array_keys($this->getRespondentOrganizations());
    }

    public function getRole(bool $current = true): string
    {
        if ($current && $this->currentRole) {
            return $this->currentRole;
        }
        return $this->role;
    }

    /**
     * Get the current session key for the user. The session key is stored in the session variable
     * auth_session_key and is only used to ensure a user can only log in on one device at the same time.
     */
    public function getSessionKey(): string|null
    {
        return $this->sessionKey;
    }

    /**
     *
     * @return string|null
     */
    public function getTwoFactorKeyForAdapter(string $adapter): ?string
    {
        $adapters = [
            'Hotp' => ['MailHotp', 'SmsHotp'],
            'Totp' => ['AuthenticatorTotp'],
        ];

        if ($this->hasTwoFactorConfigured()) {
            [$method, $key] = explode(
                OtpMethodInterface::SEPERATOR,
                $this->twoFactorKey,
                2
            );

            if (in_array($method, $adapters[$adapter], true)) {
                return $key;
            }
        }

        return null;
    }

    public function getTfaMethodClass(): string
    {
        if ($this->hasTwoFactorConfigured()) {
            [$authClass] = explode(
                OtpMethodInterface::SEPERATOR,
                $this->twoFactorKey,
                2
            );
        } else {
            throw new \Exception('No TFA configured');
        }

        return match($authClass) {
            'AuthenticatorTotp' => 'AuthenticatorTotp',
            'MailHotp' => 'MailHotp',
            'SmsHotp' => 'SmsHotp',
            default => throw new \Exception('Invalid auth class value "' . $authClass . '"'),
        };
    }

    public function getTfaMethodDescription(): string
    {
        if (! $this->hasTwoFactorConfigured()) {
            return 'None';
        }
        return match($this->getTfaMethodClass()) {
            'SmsHotp' => 'SMS',
            'AuthenticatorTotp' => 'Authenticator App',
            'MailHotp' => 'Mail',
            default => 'None',
        };
    }

    public function getUserDefinitionClass(): string
    {
        return $this->definition;
    }

    public function getUserId(): int
    {
        return $this->id;
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
    public function getUserLoginId(): int
    {
        return $this->loginId ?? 0;
    }

    public function hasEmailAddress(): bool
    {
        return $this->email !== null;
    }

    public function hasPassword(): bool
    {
        return $this->userDefinition->hasPassword($this);
    }

    public function hasPrivilege(string $privilege, bool $current = true): bool
    {
        if (!$this->acl) {
            return true;
        }

        $role = $this->getRole($current);

        try {
            return $this->acl->isAllowed($role, $privilege);
        } catch(InvalidArgumentException) {
        }
        return false;
    }

    public function hasTwoFactorConfigured(): bool
    {
        return $this->twoFactorKey !== null;
    }

    public function hasValidResetKey(): bool
    {
        return $this->isActive() && $this->resetkeyValid;
    }

    public function incrementOtpCount(): void
    {
        $newCount = $this->getOtpCount() + 1;
        $this->otpCount = $newCount;

        $now = new \DateTimeImmutable();

        $values = [
            'gul_otp_count' => $newCount,
            'gul_otp_requested' => $now->format('Y-m-d H:i:s'),
        ];

        $this->resultFetcher->updateTable('gems__user_logins', $values, ['gul_id_user' => $this->getUserLoginId()]);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isAllowedIpForLogin(?string $ipAddress): bool
    {
        if (empty($ipAddress)) {
            return false;
        }

        // Check group list
        if (!IpAddress::isAllowed($ipAddress, $this->getAllowedIPRanges() ?? '')) {
            return false;
        }

        // Check base organization list
        if (!IpAddress::isAllowed($ipAddress, $this->getBaseOrganization()->getAllowedIpRanges() ?? '')) {
            return false;
        }

        return true;
    }

    /**
     * Is this organization in the list of currently allowed organizations?
     *
     * @param int $organizationId
     * @return bool
     */
    public function isAllowedOrganization(int $organizationId): bool
    {
        $orgs = $this->getAllowedOrganizations();

        return isset($orgs[$organizationId]) || (UserLoader::SYSTEM_NO_ORG == $organizationId);
    }

    /**
     * Return true if this user is an embedded user that can defer to other logins.
     */
    public function isEmbedded(): bool
    {
        return $this->embedded;
    }

    /**
     * True when this user requires a logout after answering a survey
     *
     * @return bool
     */
    public function isLogoutOnSurvey(): bool
    {
        return $this->logout;
    }

    /**
     * True when this user must enter a new password.
     *
     * @return bool
     */
    public function isPasswordResetRequired(): bool
    {
        return $this->passwordResetRequired;
    }

    /**
     * @return bool True when we're (functionally) working in a frame, e.g. for an embedded user
     */
    public function isSessionFramed()
    {
        return $this->currentlyFramed;
    }

    /**
     * Returns true when this user is a staff member.
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->userDefinition->isStaff();
    }

    /**
     * Can this user be authorized using multi-factor authentication?
     *
     * @return bool
     */
    public function isTwoFactorEnabled(): bool
    {
        return $this->hasTwoFactorConfigured();
    }

    /**
     * Should this user be authorized using multi-factor authentication?
     *
     * @param string $ipAddress
     * @return boolean
     */
    public function isTwoFactorRequired(string $ipAddress): bool
    {
        return $this->userDefinition->isTwoFactorRequired($ipAddress, $this->isTwoFactorEnabled(), $this->getGroup());
    }

    /**
     * Allows a refresh of the existing list of organizations
     * for this user.
     *
     * @return self (continuation pattern)
     */
    public function refreshAllowedOrganizations(): self
    {
        // Privilege overrules organizational settings
        if ($this->hasPrivilege('pr.organization-switch')) {
            $organizations = $this->organizationRepository->getOrganizations();
        } else {
            $baseOrganization = $this->getBaseOrganization();

            $organizations = [$baseOrganization->getId() => $baseOrganization->getName()] +
                $baseOrganization->getAllowedOrganizations();
        }

        $this->allowedOrganizations = $organizations;

        // clear allowed respondent organizations
        //$this->_unsetVar('__allowedRespOrgs');

        return $this;
    }

    public function setCurrentOrganizationId(int $organizationId): void
    {
        $this->currentOrganizationId = $organizationId;
    }

    public function setGroupSession(int $groupId): void
    {
        $setCurrentRole = $this->currentGroup !== $groupId;
        $this->currentGroup = $groupId;

        if ($setCurrentRole) {
            $group = $this->getGroup();
            $this->currentRole = $group->getRole();
        }
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param string $password
     * @return self (continuation pattern)
     */
    public function setPassword(string $password)
    {
        $this->userDefinition->setPassword($this, $password);
        $this->setPasswordResetRequired(false);
        $this->userDefinition->updatePasswordHistory($this, $password);
        return $this;
    }

    /**
     *
     * @param bool $reset
     * @return \Gems\User\User  (continuation pattern)
     */
    public function setPasswordResetRequired(bool $reset = true): self
    {
        $this->passwordResetRequired = $reset;
        return $this;
    }

    /**
     *
     * @param bool $isFramed When true we're working in a frame
     * @return self
     */
    public function setSessionFramed(bool $isFramed = true): self
    {
        $this->currentlyFramed = $isFramed;

        return $this;
    }

    /**
     * Set the current session key for the user.
     * @see getSessionKey()
     */
    public function setSessionKey(string $sessionKey): void
    {
        $this->sessionKey = $sessionKey;

        $this->userDefinition->setSessionKey($this, $sessionKey);
    }

    public function setTwoFactorKey(string $className, string $secret): void
    {
        $newValue = $className . OtpMethodInterface::SEPERATOR . $secret;

        $this->twoFactorKey = $newValue;
        $this->userDefinition->setTwoFactorKey($this, $newValue);
    }
}
