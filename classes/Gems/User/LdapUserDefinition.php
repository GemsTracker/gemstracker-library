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

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\Ldap as LdapAdapter;

/**
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 03-Jul-2018 18:13:38
 */
class LdapUserDefinition extends \Gems_User_StaffUserDefinition
{
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
     * We never need a rehash
     *
     * @param \Gems_User_User $user
     * @param type $password
     * @return boolean
     */
    public function checkRehash(\Gems_User_User $user, $password)
    {
        return false;
    }

    /**
     * Returns an initialized Zend\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems_User_User $user
     * @param string $password
     * @return Zend\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(\Gems_User_User $user, $password)
    {
        $config = $this->project->getLdapSettings();

        $adapter = new LdapAdapter();

        // \MUtil_Echo::track($config);
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
     * @param \Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems_User_User $user)
    {
        return null;
    }

    /**
     * Copied from \Gems_User_StaffUserDefinition but left out the password link
     *
     * @param type $login_name
     * @param type $organization
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
                    'user_enable_2factor' => 'gul_enable_2factor'
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
     * @param \Gems_User_User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems_User_User $user)
    {
       return true;
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
