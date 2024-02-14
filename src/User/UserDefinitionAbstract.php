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

use Gems\Exception\Coding;

/**
 * Base class for all user definitions.
 *
 * Mainly to implement TargetAbstract.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class UserDefinitionAbstract implements UserDefinitionInterface
{
    /**
     * The time period in hours a reset key is valid for this definition.
     *
     * @var int
     */
    protected int $hoursResetKeyIsValid = 0;

    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param User|null $user Optional, the user whose password might change
     * @return bool
     */
    public function canResetPassword(User|null $user = null): bool
    {
        return false;
    }

    /**
     * Return true if the two factor can be set.
     *
     * @return bool
     */
    public function canSaveTwoFactorKey(): bool
    {
        return false;
    }

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param User|null $user Optional, the user whose password might change
     * @return bool
     */
    public function canSetPassword(User|null $user = null): bool
    {
        return false;
    }

    /**
     * Return a password reset key
     *
     * @param User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(User $user): string
    {
        throw new Coding(sprintf('A password reset key cannot be issued for %s users.', get_class($this)));
    }

    /**
     * Returns the number of hours a reset key remains valud
     *
     * @return int
     */
    public function getResetKeyDurationInHours(): int
    {
        return $this->hoursResetKeyIsValid;
    }

    /**
     * Return true if the user has a password.
     *
     * @param User $user The user to check
     * @return bool
     */
    public function hasPassword(User $user): bool
    {
        return false;
    }

    /**
     * Returns true when users using this definition are staff members.
     *
     * Used only when the definition does not return a user_staff field.
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return true;
    }

    /**
     * Should this user be authorized using multi-factor authentication?
     *
     * @param string $ipAddress
     * @param bool $hasKey
     * @param Group|null $group
     * @return bool
     */
    public function isTwoFactorRequired(string $ipAddress, bool $hasKey, Group $group = null): bool
    {
        if ($group) {
            return $group->isTwoFactorRequired($ipAddress, $hasKey);
        }
        return false;
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param User $user The user whose password to change
     * @param string $password
     * @return self (continuation pattern)
     */
    public function setPassword(User $user, string $password): self
    {
        throw new Coding(sprintf('The password cannot be set for %s users.', get_class($this)));
    }

    /**
     * Update the password history, if allowed for this user type.
     *
     * @param User $user The user whose password history to change
     * @param string $password
     * @return self (continuation pattern)
     */
    public function updatePasswordHistory(User $user, string $password): self
    {
        throw new Coding(sprintf('The password history cannot be updated for %s users.', get_class($this)));
    }

    /**
     *
     * @param User $user The user whose password to change
     * @param string $newKey
     * @return self
     */
    public function setTwoFactorKey(User $user, string $newKey): self
    {
        throw new Coding(sprintf('A Two Factor key cannot be set for %s users.', get_class($this)));
    }

    /**
     * @param User $user The user whose session key to set
     * @param string $newKey
     * @return self
     */
    public function setSessionKey(User $user, string $newKey): self
    {
        throw new Coding(sprintf('A Session key cannot be set for %s users.', get_class($this)));
    }
}
