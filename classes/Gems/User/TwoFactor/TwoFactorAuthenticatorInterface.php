<?php

/**
 *
 * @package    Gems
 * @subpackage User\TwoFactor
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\TwoFactor;

/**
 *
 * @package    Gems
 * @subpackage User\TwoFactor
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 30-Jun-2018 17:01:57
 */
interface TwoFactorAuthenticatorInterface
{
    const SEPERATOR = '::';

    /**
     * Add the elements to the setup form
     *
     * @param \Zend_Form $form
     * @param \Gems_User_User $user The user to setup for
     * @param array $formData Current form data
     */
    public function addSetupFormElements(\Zend_Form $form, \Gems_User_User $user, array &$formData);

    /**
     * Create new secret.
     *
     * @return string
     */
    public function createSecret();

    /**
     * Check if the code is correct. This will accept codes starting from $discrepancy*30sec ago to $discrepancy*30sec from now.
     *
     * @param string   $secret
     * @param string   $code
     *
     * @return bool
     */
    public function verify($secret, $code);
}
