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

use Laminas\Authentication\Adapter\AdapterInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
interface UserDefinitionInterface
{
    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition when no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param User|null $user Optional, the user whose password might change
     * @return bool
     */
    public function canResetPassword(User|null $user = null): bool;

    /**
     * Return true if the two factor can be set.
     *
     * @return bool
     */
    public function canSaveTwoFactorKey(): bool;

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition when no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param User|null $user Optional, the user whose password might change
     * @return bool
     */
    public function canSetPassword(User|null $user = null): bool;

    /**
     * Returns an initialized \Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param User $user
     * @param string $password
     * @return AdapterInterface
     */
    public function getAuthAdapter(User $user, string $password): AdapterInterface;

    /**
     * Return a password reset key
     *
     * @param User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(User $user): string;

    /**
     * Returns the number of hours a reset key remains value
     *
     * @return int
     */
    public function getResetKeyDurationInHours(): int;

    /**
     * Returns the data for a user object. It may be empty if the user is unknown.
     *
     * @param string $loginName
     * @param int $organizationId
     * @return array Of data to fill the user with.
     */
    public function getUserData(string $loginName, int $organizationId): array;

    /**
     * Return true if the user has a password.
     *
     * @param User $user The user to check
     * @return bool
     */
    public function hasPassword(User $user): bool;

    /**
     * Returns true when users using this definition are staff members.
     *
     * Used only when the definition does not return a user_staff field.
     *
     * @return bool
     */
    public function isStaff(): bool;

    /**
     * Should this user be authorized using multi-factor authentication?
     *
     * @param string $ipAddress
     * @param bool $hasKey
     * @param Group|null $group
     * @return bool
     */
    public function isTwoFactorRequired(string $ipAddress, bool $hasKey, Group|null $group = null): bool;

    /**
     * Set the password, if allowed for this user type.
     *
     * @param User $user The user whose password to change
     * @param string $password
     * @return UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(User $user, string $password): self;

    /**
     * Update the password history, if allowed for this user type.
     *
     * @param User $user The user whose password history to change
     * @param string $password
     * @return UserDefinitionInterface (continuation pattern)
     */
    public function updatePasswordHistory(User $user, string $password): self;

    /**
     *
     * @param User $user The user whose password to change
     * @param string $newKey
     * @return $this
     */
    public function setTwoFactorKey(User $user, string $newKey): self;

    /**
     * @param User $user The user whose session key to set
     * @param string $newKey
     * @return $this
     */
    public function setSessionKey(User $user, string $newKey): self;
}
