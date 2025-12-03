<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User;

use Gems\Exception;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\Ldap as LdapAdapter;
use Laminas\Db\Sql\Select;

/**
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 03-Jul-2018 18:13:38
 */
class LdapUserDefinition extends StaffUserDefinition
{
    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems\User\User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(User|null $user = null): bool
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
    public function canSetPassword(User $user = null): bool
    {
        return false;
    }

    /**
     * We never need a rehash
     *
     * @param \Gems\User\User $user
     * @param string $password
     * @return boolean
     */
    public function checkRehash(User $user, string $password): bool
    {
        return false;
    }

    /**
     * Returns an initialized \Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems\User\User $user
     * @param string $password
     * @return \Laminas\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(User $user, string $password): AdapterInterface
    {
        $config = [];
        if (isset($this->config['ldap'])) {
            $config = $this->config['ldap'];
        }

        $adapter = new LdapAdapter();

        // \MUtil\EchoOut\EchoOut::track($config);
        foreach ($config as $server) {
            $adapter->setOptions([$server]);

            if (isset($server['accountDomainNameShort'])) {
                $userName = $server['accountDomainNameShort'] . '\\' . $user->getLoginName();
            } else {
                $userName = $user->getLoginName();
            }

            $adapter->setUsername($userName)
                    ->setPassword($password);
        }

        return $adapter;
    }

    /**
     * Return a password reset key, never reached as we can not reset the password
     *
     * @param \Gems\User\User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems\User\User $user): string
    {
        throw new Exception('Ldap adapter cannot create reset key');
    }

    /**
     * Copied from \Gems\User\StaffUserDefinition but left out the password link
     *
     * @param string $loginName
     * @param int $organizationId
     * @return Select
     */
    protected function getUserSelect(string $loginName, int $organizationId): Select
    {
        /**
         * Read the needed parameters from the different tables, lots of renames
         * for compatibility across implementations.
         */
        $select = $this->resultFetcher->getSelect('gems__user_logins');
        $select->columns([
                    'user_login_id'       => 'gul_id_user',
                    'user_two_factor_key' => 'gul_two_factor_key',
                    'user_otp_count'      =>'gul_otp_count',
                    'user_otp_requested'  =>'gul_otp_requested',
                    'user_session_key'    => 'gul_session_key',
                ])
                ->join('gems__staff', 'gul_login = gsf_login AND gul_id_organization = gsf_id_organization', [
                    'user_id'             => 'gsf_id_user',
                    'user_login'          => 'gsf_login',
                    'user_email'          => 'gsf_email',
                    'user_first_name'     => 'gsf_first_name',
                    'user_surname_prefix' => 'gsf_surname_prefix',
                    'user_last_name'      => 'gsf_last_name',
                    'user_gender'         => 'gsf_gender',
                    'user_group'          => 'gsf_id_primary_group',
                    'user_locale'         => 'gsf_iso_lang',
                    'user_logout'         => 'gsf_logout_on_survey',
                    'user_base_org_id'    => 'gsf_id_organization',
                    'user_embedded'       => 'gsf_is_embedded',
                    'user_phone_number'    => $this->getMobilePhoneField(),
                ])
               ->join('gems__groups', 'gsf_id_primary_group = ggp_id_group', [
                   'user_role'=>'ggp_role',
                   'user_allowed_ip_ranges' => 'ggp_allowed_ip_ranges',
               ])
               //->joinLeft('gems__user_passwords', 'gul_id_user = gup_id_user', array(
               //    'user_password_reset' => 'gup_reset_required',
               //    ))
               ->where([
                   'ggp_group_active' => 1,
                   'gsf_active' => 1,
                   'gul_can_login' => 1,
                   'gul_login' => $loginName,
                   'gul_id_organization' => $organizationId,
               ])
               ->limit(1);

        return $select;
    }

    /**
     * Return true if the user has a password.
     *
     * Seems to be only used on changing a password, so will probably never be reached
     *
     * @param User $user The user to check
     * @return bool
     */
    public function hasPassword(User $user): bool
    {
       return true;
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param User $user The user whose password to change
     * @param string $password
     * @return self (continuation pattern)
     */
    public function setPassword(User $user, string|null $password): self
    {
        throw new \Gems\Exception\Coding(sprintf('The password cannot be set for %s users.', get_class($this)));
    }

    /**
     * Update the password history, if allowed for this user type.
     *
     * @param User $user The user whose password history to change
     * @param string $password
     * @return self
     */
    public function updatePasswordHistory(User $user, string $password): self
    {
        throw new \Gems\Exception\Coding(sprintf('The password history cannot be updated for %s users.', get_class($this)));
    }
}
