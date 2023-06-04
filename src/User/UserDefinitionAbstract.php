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

use Gems\User\Group;

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
abstract class UserDefinitionAbstract extends \MUtil\Registry\TargetAbstract implements \Gems\User\UserDefinitionInterface
{
    /**
     * The time period in hours a reset key is valid for this definition.
     *
     * @var int
     */
    protected $hoursResetKeyIsValid = 0;

    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems\User\User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(\Gems\User\User $user = null)
    {
        return false;
    }

    /**
     * Return true if the two factor can be set.
     *
     * @return boolean
     */
    public function canSaveTwoFactorKey()
    {
        return false;
    }

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems\User\User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(\Gems\User\User $user = null)
    {
        return false;
    }

    /**
     * Return a password reset key
     *
     * @param \Gems\User\User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems\User\User $user)
    {
        throw new \Gems\Exception\Coding(sprintf('A password reset key cannot be issued for %s users.', get_class($this)));
    }

    /**
     * Returns the number of hours a reset key remains valud
     *
     * @return int
     */
    public function getResetKeyDurationInHours()
    {
        return $this->hoursResetKeyIsValid;
    }

    /**
     * Return true if the user has a password.
     *
     * @param \Gems\User\User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems\User\User $user)
    {
        return false;
    }

    /**
     * Returns true when users using this definition are staff members.
     *
     * Used only when the definition does not return a user_staff field.
     *
     * @return boolean
     */
    public function isStaff()
    {
        return true;
    }

    /**
     * Should this user be authorized using two factor authentication?
     *
     * @param string $ipAddress
     * @param boolean $hasKey
     * @param Group $group
     * @return boolean
     */
    public function isTwoFactorRequired($ipAddress, $hasKey, Group $group = null)
    {
        if ($group) {
            return $group->isTwoFactorRequired($ipAddress, $hasKey);
        }
        return false;
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param \Gems\User\User $user The user whose password to change
     * @param string $password
     * @return \Gems\User\UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(\Gems\User\User $user, $password)
    {
        throw new \Gems\Exception\Coding(sprintf('The password cannot be set for %s users.', get_class($this)));
        return $this;
    }

    /**
     *
     * @param \Gems\User\User $user The user whose password to change
     * @param string $newKey
     * @return $this
     */
    public function setTwoFactorKey(\Gems\User\User $user, $newKey)
    {
        throw new \Gems\Exception\Coding(sprintf('A Two Factor key cannot be set for %s users.', get_class($this)));
        return $this;
    }

    /**
     * @param User $user The user whose session key to set
     * @param string $newKey
     * @return $this
     */
    public function setSessionKey(\Gems\User\User $user, string $newKey): static
    {
        throw new \Gems\Exception\Coding(sprintf('A Session key cannot be set for %s users.', get_class($this)));
    }
}
