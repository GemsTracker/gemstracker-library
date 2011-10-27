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
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 *
 *
-- PATCH: New user login structure
INSERT INTO gems__users (gsu_id_user, gsu_login, gsu_id_organization, gsu_user_class, gsu_active,
                gsu_password, gsu_failed_logins, gsu_last_failed, gsu_reset_key, gsu_reset_requested, gsu_reset_required,
                gsu_changed, gsu_changed_by, gsu_created, gsu_created_by)
    SELECT grs_id_user, gr2o_patient_nr, gr2o_id_organization, 'RespondentUser', CASE WHEN gr2o_reception_code = 'OK' THEN 1 ELSE 0 END,
                NULL, 0, NULL, NULL, NULL, 0,
                gr2o_changed, gr2o_changed_by, gr2o_created, gr2o_created_by
        FROM gems__respondents INNER JOIN gems__respondent2org ON grs_id_user = gr2o_id_user
            INNER JOIN gems__organizations ON gr2o_id_organization = gor_id_organization
        WHERE gor_name = 'HCU / Xpert Clinic';

 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_User_RespondentUser extends Gems_User_DatabaseUserAbstract
{
    /**
     * Creates the initial feed SQL select statement
     *
     * @return Zend_Db_Select
     */
    public function getSqlSelect()
    {
        $this->db->select();
    }

}
