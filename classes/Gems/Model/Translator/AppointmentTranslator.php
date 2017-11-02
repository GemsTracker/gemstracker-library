<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
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
     * The name of the field to store the organization id in
     *
     * @var string
     */
    protected $orgIdField = 'gap_id_organization';

    /**
     *
     * @var \Gems_loader
     */
    protected $loader;

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
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->loader instanceof \Gems_Loader) &&
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
     * @param mixed $row array or \Traversable row
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

        if (! isset($row['gap_id_user'])) {
            if (isset($row['gr2o_patient_nr'], $row[$this->orgIdField])) {

                $sql = 'SELECT gr2o_id_user
                        FROM gems__respondent2org
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?';

                $id = $this->db->fetchOne($sql, array($row['gr2o_patient_nr'], $row[$this->orgIdField]));

                if ($id) {
                    $row['gap_id_user'] = $id;
                }
            }
            if (! isset($row['gap_id_user'])) {
                // No user no import if still not set
                return false;
            }
        }

        if (isset($row['gap_admission_time'], $row['gap_discharge_time']) &&
                ($row['gap_admission_time'] instanceof \MUtil_Date) &&
                ($row['gap_discharge_time'] instanceof \MUtil_Date)) {
            if ($row['gap_discharge_time']->diffDays($row['gap_admission_time']) > 366) {
                if ($row['gap_discharge_time']->diffDays() > 366) {
                    $row['gap_discharge_time'] = null;
                }
            }
        }

        $skip = false;
        if (isset($row['gas_name_attended_by'])) {
            $row['gap_id_attended_by'] = $this->_agenda->matchHealthcareStaff(
                    $row['gas_name_attended_by'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_attended_by']);
        }
        if (!$skip && isset($row['gas_name_referred_by'])) {
            $row['gap_id_referred_by'] = $this->_agenda->matchHealthcareStaff(
                    $row['gas_name_referred_by'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_referred_by']);
        }
        if (!$skip && isset($row['gaa_name'])) {
            $row['gap_id_activity'] = $this->_agenda->matchActivity(
                    $row['gaa_name'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_activity']);
        }
        if (!$skip && isset($row['gapr_name'])) {
            $row['gap_id_procedure'] = $this->_agenda->matchProcedure(
                    $row['gapr_name'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_procedure']);
        }
        if (!$skip && isset($row['glo_name'])) {
            $location = $this->_agenda->matchLocation(
                    $row['glo_name'],
                    $row[$this->orgIdField]
                    );
            $row['gap_id_location'] = is_null($location) ? null : $location['glo_id_location'];
            $skip = $skip || is_null($location) || $location['glo_filter'];
        }
        if ($skip) {
            return null;
        }
        // \MUtil_Echo::track($row);

        return $row;
    }

    /**
     * Prepare for the import.
     *
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function startImport()
    {
        if ($this->_targetModel instanceof \MUtil_Model_ModelAbstract) {
            // No multiOptions as a new items can be created during import
            $fields = array(
                'gap_id_attended_by', 'gap_id_referred_by', 'gap_id_activity',  'gap_id_procedure', 'gap_id_location',
                );
            foreach ($fields as $name) {
                $this->_targetModel->del($name, 'multiOptions');
            }
        }

        return parent::startImport();
    }
}
