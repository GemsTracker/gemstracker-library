<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentTranslator.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Model_Translator_RespondentTranslator extends Gems_Model_Translator_StraightTranslator
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     *
     * @var array
     */
    protected $orgTranslations;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->orgTranslations = $this->db->fetchPairs('
            SELECT gor_provider_id, gor_id_organization
                FROM gems__organizations
                WHERE gor_provider_id IS NOT NULL
                ORDER BY gor_provider_id');

        $this->orgTranslations = $this->orgTranslations + $this->db->fetchPairs('
            SELECT gor_code, gor_id_organization
                FROM gems__organizations
                WHERE gor_code IS NOT NULL
                ORDER BY gor_id_organization');
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->db instanceof Zend_Db_Adapter_Abstract) && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Prepare for the import.
     *
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function startImport()
    {
        if (! $this->_targetModel instanceof MUtil_Model_ModelAbstract) {
            throw new MUtil_Model_ModelException("Trying to start the import without target model.");
        }

        $this->_targetModel->set('grs_gender', 'extraValueKeys', array('V' => 'F'));

        return parent::startImport();
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        if (! $row) {
            return false;
        }

        // Get the real organization from the provider_id or code if it exists
        if (isset($row['gr2o_id_organization'], $this->orgTranslations[$row['gr2o_id_organization']])) {
            $row['gr2o_id_organization'] = $this->orgTranslations[$row['gr2o_id_organization']];
        }

        if (! isset($row['grs_id_user'])) {

            $id = false;

            if (isset($row['gr2o_patient_nr'], $row['gr2o_id_organization'])) {
                $sql = 'SELECT gr2o_id_user
                        FROM gems__respondent2org
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?';

                $id = $this->db->fetchOne($sql, array($row['gr2o_patient_nr'], $row['gr2o_id_organization']));
            }

            if ((!$id) &&
                    isset($row['grs_ssn']) &&
                    $this->_targetModel instanceof Gems_Model_RespondentModel &&
                    $this->_targetModel->hashSsn !== Gems_Model_RespondentModel::SSN_HIDE) {

                if (Gems_Model_RespondentModel::SSN_HASH === $this->_targetModel->hashSsn) {
                    $search = $this->_targetModel->saveSSN($row['grs_ssn']);
                } else {
                    $search = $row['grs_ssn'];
                }

                $sql = 'SELECT grs_id_user FROM gems__respondents WHERE grs_ssn = ?';

                $id = $this->db->fetchOne($sql, $search);

                // Check for change in patient ID
                if ($id &&
                        isset($row['gr2o_id_organization']) &&
                        $this->_targetModel instanceof MUtil_Model_DatabaseModelAbstract) {

                    $sql = 'SELECT gr2o_patient_nr
                            FROM gems__respondent2org
                            WHERE gr2o_id_user = ? AND gr2o_id_organization = ?';

                    $patientId = $this->db->fetchOne($sql, array($id, $row['gr2o_id_organization']));

                    if ($patientId) {
                        $copyId       = $this->_targetModel->getKeyCopyName('gr2o_patient_nr');
                        $row[$copyId] = $patientId;
                    }
                }
            }

            if ($id) {
                $row['grs_id_user']  = $id;
                $row['gr2o_id_user'] = $id;
            }
        }

        if (!isset($row['grs_email'])) {
            $row['calc_email'] = 1;
        }

        return $row;
    }
}
