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
 * @version    $Id$
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
class Gems_Model_Translator_AppointmentTranslator extends \Gems_Model_Translator_StraightTranslator
{
    /**
     *
     * @var \Gems_Agenda
     */
    protected $_agenda;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_loader
     */
    protected $loader;

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

        $this->_agenda = $this->loader->getAgenda();

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
        return ($this->db instanceof \Zend_Db_Adapter_Abstract) &&
            ($this->loader instanceof \Gems_Loader) &&
            parent::checkRegistryRequestsAnswers();
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getFieldsTranslations()
    {
        $this->_targetModel->setAlias('gas_name_attended_by', 'gap_id_attended_by');
        $this->_targetModel->setAlias('gas_name_referred_by', 'gap_id_referred_by');
        $this->_targetModel->setAlias('gaa_name', 'gap_id_activity');
        $this->_targetModel->setAlias('gapr_name', 'gap_id_procedure');
        $this->_targetModel->setAlias('glo_name', 'gap_id_location');

        return array(
            'gap_patient_nr'      => 'gr2o_patient_nr',
            'gap_organization_id' => 'gap_id_organization',
            'gap_id_in_source'    => 'gap_id_in_source',
            'gap_admission_time'  => 'gap_admission_time',
            'gap_discharge_time'  => 'gap_discharge_time',
            'gap_admission_code'  => 'gap_code',
            'gap_status_code'     => 'gap_status',
            'gap_attended_by'     => 'gas_name_attended_by',
            'gap_referred_by'     => 'gas_name_referred_by',
            'gap_activity'        => 'gaa_name',
            'gap_procedure'       => 'gapr_name',
            'gap_location'        => 'glo_name',
            'gap_subject'         => 'gap_subject',
            'gap_comment'         => 'gap_comment',

            // Autofill fields - without a source field but possibly set in this translator
            'gap_id_user',

        );
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

        // Set fixed values for import
        $row['gap_source']      = 'import';
        $row['gap_manual_edit'] = 0;

        // Get the real organization from the provider_id or code if it exists
        if (isset($row['gap_id_organization'], $this->orgTranslations[$row['gap_id_organization']])) {
            $row['gap_id_organization'] = $this->orgTranslations[$row['gap_id_organization']];
        }

        if (! isset($row['gap_id_user'])) {
            if (isset($row['gr2o_patient_nr'], $row['gap_id_organization'])) {

                $sql = 'SELECT gr2o_id_user
                        FROM gems__respondent2org
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?';

                $id = $this->db->fetchOne($sql, array($row['gr2o_patient_nr'], $row['gap_id_organization']));
                // \MUtil_Echo::track($id, $row['gr2o_patient_nr'], $row['gap_id_organization']);

                if ($id) {
                    $row['gap_id_user'] = $id;
                }
            }
            if (! isset($row['gap_id_user'])) {
                // No user no import if still not set
                return false;
            }
        }

        if (($row['gap_admission_time'] instanceof \MUtil_Date) && ($row['gap_discharge_time'] instanceof \MUtil_Date)) {
            if ($row['gap_discharge_time']->diffDays($row['gap_admission_time']) > 366) {
                if ($row['gap_discharge_time']->diffDays(new \MUtil_Date()) > 366) {
                    $row['gap_discharge_time'] = null;
                }
            }
        }

        $skip = false;
        if (isset($row['gas_name_attended_by'])) {
            $row['gap_id_attended_by'] = $this->_agenda->matchHealthcareStaff(
                    $row['gas_name_attended_by'],
                    $row['gap_id_organization']
                    );
            $skip = $skip || (false === $row['gap_id_attended_by']);
        }
        if (isset($row['gas_name_referred_by'])) {
            $row['gap_id_referred_by'] = $this->_agenda->matchHealthcareStaff(
                    $row['gas_name_referred_by'],
                    $row['gap_id_organization']
                    );
            $skip = $skip || (false === $row['gap_id_referred_by']);
        }
        if (isset($row['gaa_name'])) {
            $row['gap_id_activity'] = $this->_agenda->matchActivity(
                    $row['gaa_name'],
                    $row['gap_id_organization']
                    );
            $skip = $skip || (false === $row['gap_id_activity']);
        }
        if (isset($row['gapr_name'])) {
            $row['gap_id_procedure'] = $this->_agenda->matchProcedure(
                    $row['gapr_name'],
                    $row['gap_id_organization']
                    );
            $skip = $skip || (false === $row['gap_id_procedure']);
        }
        if (isset($row['glo_name'])) {
            $location = $this->_agenda->matchLocation(
                    $row['glo_name'],
                    $row['gap_id_organization']
                    );
            $row['gap_id_location'] = $location['glo_id_location'];
            $skip = $skip || $location['glo_filter'];
        }
        if ($skip) {
            return null;
        }
        // \MUtil_Echo::track($row);

        return $row;
    }
}
