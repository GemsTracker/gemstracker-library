<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * A standard, database stored and authenticate staff user as of version 1.5.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_RespondentUserDefinition extends Gems_User_DbUserDefinitionAbstract
{
    /**
     * A select used by subclasses to add fields to the select.
     *
     * @param string $login_name
     * @param int $organization
     * @return Zend_Db_Select
     */
    protected function getUserSelect($login_name, $organization)
    {
        // 'user_group'       => 'gsf_id_primary_group', 'user_logout'      => 'gsf_logout_on_survey',
        $select = new Zend_Db_Select($this->db);
        $select->from('gems__user_logins', array(
                    'user_login_id' => 'gul_id_user',
                    ))
                ->join('gems__respondent2org', 'gul_login = gr2o_patient_nr AND gul_id_organization = gr2o_id_organization', array(
                    'user_login'       => 'gr2o_patient_nr',
                    'user_base_org_id' => 'gr2o_id_organization',
                    ))
               ->join('gems__respondents', 'gr2o_id_user = grs_id_user', array(
                    'user_id'             => 'grs_id_user',
                    'user_email'          => 'grs_email',
                    'user_first_name'     => 'grs_first_name',
                    'user_surname_prefix' => 'grs_surname_prefix',
                    'user_last_name'      => 'grs_last_name',
                    'user_gender'         => 'grs_gender',
                    'user_locale'         => 'grs_iso_lang',
                    ))
               ->join('gems__organizations', 'gr2o_id_organization=gor_id_organization', array())
               ->join('gems__groups', 'gor_respondent_group=ggp_id_group', array(
                   'user_role'=>'ggp_role',
                   'user_allowed_ip_ranges' => 'ggp_allowed_ip_ranges',
                   ))
               ->joinLeft('gems__user_passwords', 'gul_id_user = gup_id_user', array(
                   'user_password_reset' => 'gup_reset_required',
                   ))
               ->joinLeft('gems__reception_codes', 'gr2o_reception_code = grc_id_reception_code', array())
               ->where('ggp_group_active = 1')
               ->where('grc_success = 1')
               ->where('gul_can_login = 1')
               ->where('gul_login = ?')
               ->where('gul_id_organization = ?')
               ->limit(1);

        return $select;
    }
}