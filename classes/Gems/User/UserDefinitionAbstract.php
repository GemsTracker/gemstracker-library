<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

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
abstract class Gems_User_UserDefinitionAbstract extends \MUtil_Registry_TargetAbstract implements \Gems_User_UserDefinitionInterface
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
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(\Gems_User_User $user = null)
    {
        return false;
    }

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(\Gems_User_User $user = null)
    {
        return false;
    }

    /**
     * Return a password reset key
     *
     * @param \Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems_User_User $user)
    {
        throw new \Gems_Exception_Coding(sprintf('A password reset key cannot be issued for %s users.', get_class($this)));
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
     * Return true if the user has a password.
     *
     * @param \Gems_User_User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems_User_User $user)
    {
        return false;
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param \Gems_User_User $user The user whose password to change
     * @param string $password
     * @return \Gems_User_UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(\Gems_User_User $user, $password)
    {
        throw new \Gems_Exception_Coding(sprintf('The password cannot be set for %s users.', get_class($this)));
        return $this;
    }
}
