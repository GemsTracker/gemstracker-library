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

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\Ldap as LdapAdapter;

/**
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 03-Jul-2018 18:13:38
 */
class LdapUserDefinition extends \Gems\User\StaffUserDefinition
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
    public function canResetPassword(\Gems\User\User $user = null)
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
     * We never need a rehash
     *
     * @param \Gems\User\User $user
     * @param string $password
     * @return boolean
     */
    public function checkRehash(\Gems\User\User $user, $password)
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
    public function getAuthAdapter(\Gems\User\User $user, $password)
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
    public function getPasswordResetKey(\Gems\User\User $user)
    {
        return null;
    }

    /**
     * Copied from \Gems\User\StaffUserDefinition but left out the password link
     *
     * @param string $login_name
     * @param string|int $organization
     * @return \Zend_Db_Select
     */
    protected function getUserSelect($login_name, $organization)
    {
        /**
         * Read the needed parameters from the different tables, lots of renames
         * for compatibility across implementations.
         */
        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__user_logins', array(
                    'user_login_id'       => 'gul_id_user',
                    'user_two_factor_key' => 'gul_two_factor_key',
                    ))
                ->join('gems__staff', 'gul_login = gsf_login AND gul_id_organization = gsf_id_organization', array(
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
                    ))
               ->join('gems__groups', 'gsf_id_primary_group = ggp_id_group', array(
                   'user_role'=>'ggp_role',
                   'user_allowed_ip_ranges' => 'ggp_allowed_ip_ranges',
                   ))
               //->joinLeft('gems__user_passwords', 'gul_id_user = gup_id_user', array(
               //    'user_password_reset' => 'gup_reset_required',
               //    ))
               ->where('ggp_group_active = 1')
               ->where('gsf_active = 1')
               ->where('gul_can_login = 1')
               ->where('gul_login = ?')
               ->where('gul_id_organization = ?')
               ->limit(1);

        return $select;
    }

    /**
     * Return true if the user has a password.
     *
     * Seems to be only used on changing a password, so will probably never be reached
     *
     * @param \Gems\User\User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems\User\User $user)
    {
       return true;
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
     * Update the password history, if allowed for this user type.
     *
     * @param \Gems\User\User $user The user whose password history to change
     * @param string $password
     * @return never
     */
    public function updatePasswordHistory(\Gems\User\User $user, string $password)
    {
        throw new \Gems\Exception\Coding(sprintf('The password history cannot be updated for %s users.', get_class($this)));
    }
}
