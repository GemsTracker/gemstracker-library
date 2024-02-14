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

use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * A standard, database stored and authenticate staff user as of version 1.5.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class StaffUserDefinition extends DbUserDefinitionAbstract
{
    /**
     * A select used by subclasses to add fields to the select.
     *
     * @param string $loginName
     * @param int $organizationId
     * @return Select
     */
    protected function getUserSelect(string $loginName, int $organizationId): Select
    {
        if ((0 == ($this->hoursResetKeyIsValid % 24))) {
            $resetExpr = 'CASE WHEN ADDDATE(gup_reset_requested, ' .
                    intval($this->hoursResetKeyIsValid / 24) .
                    ') >= CURRENT_TIMESTAMP THEN 1 ELSE 0 END';
        } else {
            $resetExpr = 'CASE WHEN DATE_ADD(gup_reset_requested, INTERVAL ' .
                    $this->hoursResetKeyIsValid .
                    ' HOUR) >= CURRENT_TIMESTAMP THEN 1 ELSE 0 END';
        }

        /**
         * Read the needed parameters from the different tables, lots of renames
         * for compatibility across implementations.
         */
        $select = $this->resultFetcher->getSelect('gems__user_logins');
        $select->columns([
                    'user_login_id' => 'gul_id_user',
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
                    'user_phonenumber'    => $this->getMobilePhoneField(),
                ])
               ->join('gems__groups', 'gsf_id_primary_group = ggp_id_group', [
                   'user_role'=>'ggp_role',
                   'user_allowed_ip_ranges' => 'ggp_allowed_ip_ranges',
               ])
               ->join('gems__user_passwords', 'gul_id_user = gup_id_user', [
                   'user_password_reset' => 'gup_reset_required',
                   'user_resetkey_valid' => new Expression($resetExpr),
                   'user_password_last_changed' => 'gup_last_pwd_change',
               ], Select::JOIN_LEFT)
               ->where([
                   'ggp_group_active' => 1,
                   'gsf_active' => 1,
                   'gul_can_login' => 1,
                   'gul_user_class' => 'StaffUser',
                   'gul_login' => $loginName,
                   'gul_id_organization' => $organizationId,
               ])
               ->limit(1);

        // \MUtil\EchoOut\EchoOut::track($select->__toString());

        return $select;
    }

    protected function getMobilePhoneField()
    {
        return 'gsf_phone_1';
    }
}
