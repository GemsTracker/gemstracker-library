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

use Laminas\Db\Sql\Select;

/**
 * A standard, database stored and authenticate respondent user as of version 1.5.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class RespondentUserDefinition extends DbUserDefinitionAbstract
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

        // 'user_group'       => 'gsf_id_primary_group', 'user_logout'      => 'gsf_logout_on_survey',
        $select = $this->resultFetcher->getSelect('gems__user_logins');
        $select->columns([
                    'user_login_id'       => 'gul_id_user',
                    'user_two_factor_key' => 'gul_two_factor_key',
                    'user_otp_count'      =>'gul_otp_count',
                    'user_otp_requested'  =>'gul_otp_requested',
                    'user_active'         => 'gul_can_login',
                    'user_session_key'    => 'gul_session_key',
        ])
                ->join('gems__respondent2org', 'gul_login = gr2o_patient_nr AND gul_id_organization = gr2o_id_organization', [
                    'user_login'       => 'gr2o_patient_nr',
                    'user_base_org_id' => 'gr2o_id_organization',
                    'user_email'       => 'gr2o_email',
                ])
               ->join('gems__respondents', 'gr2o_id_user = grs_id_user', [
                    'user_id'             => 'grs_id_user',
                    'user_first_name'     => 'grs_first_name',
                    'user_surname_prefix' => 'grs_surname_prefix',
                    'user_last_name'      => 'grs_last_name',
                    'user_gender'         => 'grs_gender',
                    'user_locale'         => 'grs_iso_lang',
                    'user_birthday'       => 'grs_birthday',
                    'user_zip'            => 'grs_zipcode',
                    'user_phonenumber'    => $this->getMobilePhoneField(),
               ])
               ->join('gems__organizations', 'gr2o_id_organization = gor_id_organization', [
                    'user_group' => 'gor_respondent_group',
               ])
                ->join('gems__groups', 'gor_respondent_group = ggp_id_group', [
                    'user_role'              => 'ggp_role',
                    'user_allowed_ip_ranges' => 'ggp_allowed_ip_ranges',
                ])
               ->join('gems__user_passwords', 'gul_id_user = gup_id_user', [
                   'user_password_reset' => 'gup_reset_required',
                   'user_resetkey_valid' => new \Zend_Db_Expr($resetExpr),
                    'user_password_last_changed' => 'gup_last_pwd_change',
               ], Select::JOIN_LEFT)
               ->join('gems__reception_codes', 'gr2o_reception_code = grc_id_reception_code', [], Select::JOIN_LEFT)
                ->where([
                    'ggp_group_active' => 1,
                    'grc_success' => 1,
                    'gul_can_login' => 1,
                    'gul_user_class' => 'RespondentUser',
                    'gul_login' => $loginName,
                    'gul_id_organization' => $organizationId,
                ])
               ->limit(1);

        return $select;
    }

    protected function getMobilePhoneField()
    {
        return 'grs_phone_1';
    }

    /**
     * Returns true when users using this definition are staff members.
     *
     * Used only when the definition does not return a user_staff field.
     *
     * @return boolean
     */
    public function isStaff(): bool
    {
        return false;
    }
}
