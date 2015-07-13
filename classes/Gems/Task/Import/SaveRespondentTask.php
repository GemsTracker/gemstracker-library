<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Task\Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SaveRespondentTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Import
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 13-jul-2015 18:01:44
 */
class Gems_Task_Import_SaveRespondentTask extends \MUtil_Task_Import_SaveToModel
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param array $row Row to save
     */
    public function execute($row = null)
    {
        if ($row) {
            if ((! isset($row['grs_id_user'])) &&
                    isset($row['grs_ssn']) &&
                    $this->targetModel instanceof \Gems_Model_RespondentModel &&
                    $this->targetModel->hashSsn !== \Gems_Model_RespondentModel::SSN_HIDE) {

                if (\Gems_Model_RespondentModel::SSN_HASH === $this->targetModel->hashSsn) {
                    $search = $this->targetModel->saveSSN($row['grs_ssn']);
                } else {
                    $search = $row['grs_ssn'];
                }

                $sql = 'SELECT grs_id_user FROM gems__respondents WHERE grs_ssn = ?';
                $id  = $this->db->fetchOne($sql, $search);

                // Check for change in patient ID
                if ($id) {
                    if (isset($row['gr2o_id_organization']) &&
                            $this->targetModel instanceof \MUtil_Model_DatabaseModelAbstract) {

                        $sql = 'SELECT gr2o_patient_nr
                                FROM gems__respondent2org
                                WHERE gr2o_id_user = ? AND gr2o_id_organization = ?';

                        $patientId = $this->db->fetchOne($sql, array($id, $row['gr2o_id_organization']));

                        if ($patientId) {
                            // Change the patient number
                            $copyId       = $this->targetModel->getKeyCopyName('gr2o_patient_nr');
                            $row[$copyId] = $patientId;
                        }
                    }

                    $row['grs_id_user']  = $id;
                    $row['gr2o_id_user'] = $id;
                }
            }

            parent::execute($row);
        }
    }
}
