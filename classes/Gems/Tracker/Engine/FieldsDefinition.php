<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldsDefintiion.php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Tracker_Engine_FieldsDefinition extends MUtil_Translate_TranslateableAbstract
{
    /**
     * Field key separator
     */
    const FIELD_KEY_SEPARATOR = '__';

    /**
     * Option seperator for fields
     */
    const FIELD_SEP = '|';

    /**
     * Appointment type
     */
    const TYPE_APPOINTMENT = 'appointment';

    /**
     * Date type
     */
    const TYPE_DATE = 'date';

    /**
     * Cache for appointment fields check
     *
     * @var boolean
     */
    private $_hasAppointmentFields = null;

    /**
     * Stores the models for each action
     *
     * @var array
     */
    protected $_maintenanceModels = array();

    /**
     * Can be an empty array.
     *
     * @var array The gems__track_fields + gems__track_appointments data
     */
    protected $_trackFields = false;

    /**
     *
     * @var int
     */
    protected $_trackId;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * True when there exist fields
     *
     * @var boolean
     */
    public $exists = false;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Gems_Tracker_Engine_FieldsDefinition
     */
    protected $tracker;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Construct the defintion for this gems__tracks track id.
     *
     * @param The track $trackId
     */
    public function __construct($trackId)
    {
        $this->_trackId = $trackId;
    }

    /**
     * Loads the $this->_trackFields array, if not already there
     */
    protected function _ensureTrackFields()
    {
        if (! is_array($this->_trackFields)) {
            $for    = array('gtf_id_track' => $this->_trackId);
            $model  = $this->getMaintenanceModel(false, 'index', $for);
            $fields = $model->load($for, array('gtf_id_order' => SORT_ASC));

            $this->_trackFields = array();
            if (is_array($fields)) {

                $this->exists = true;

                foreach ($fields as $field) {
                    $this->_trackFields[$field['sub'] . self::FIELD_KEY_SEPARATOR . $field['gtf_id_field']] = $field;
                }
                // MUtil_Echo::track($this->_trackFields);
            } else {
                $this->exists       = false;
                $this->_trackFields = array();
            }
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->_ensureTrackFields();
    }

    /**
     * Calculate the track info from the fields
     *
     * @param int $respTrackId Gems respondent track id or null when new
     * @param array $data The values to save
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo($respTrackId, array $data)
    {
        if (! $this->exists) {
            return null;
        }

        $results = array();
        foreach ($this->_trackFields as $key => $field) {
            if (isset($data[$key]) && (is_array($data[$key]) || strlen($data[$key]))) {
                if ("appointment" !== $field['gtf_field_type']) {
                    if (is_array($data[$key])) {
                        $results = array_merge($results, $data[$key]);
                    } else {
                        $results[] = $data[$key];
                    }
                }
            }
        }

        return trim(implode(' ', $results));
    }

    /**
     * Get a big array with model settings for fields in a track
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array fieldname => array(settings)
     */
    public function getDataEditModelSettings($respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        if (! $this->exists) {
            return array();
        }

        $fieldSettings = array();
        $appointments  = null;
        $empty         = $this->util->getTranslated()->getEmptyDropdownArray();

        foreach ($this->_trackFields as $name => $field) {

            $fieldSettings[$name] = array(
                'label'       => $field['gtf_field_name'],
                'required'    => $field['gtf_required'],
                'description' => $field['gtf_field_description'],
                );

            if ($field['gtf_readonly']) {
                $fieldSettings[$name]['elementClass'] = 'Exhibitor';

            } else {
                switch ($field['gtf_field_type']) {
                    case "multiselect":
                        $multi = explode(self::FIELD_SEP, $field['gtf_field_values']);
                        $multi = array_combine($multi, $multi);

                        $fieldSettings[$name]['elementClass']   = 'MultiCheckbox';
                        $fieldSettings[$name]['multiOptions']   = $multi;
                        $fieldSettings[$name]['formatFunction'] = array($this, 'formatMultiField');

                        break;

                    case "select":
                        $multi = explode(self::FIELD_SEP, $field['gtf_field_values']);
                        $multi = array_combine($multi, $multi);

                        $fieldSettings[$name]['elementClass'] = 'Select';
                        $fieldSettings[$name]['multiOptions'] = $empty + $multi;
                        break;

                    case "date":
                        $fieldSettings[$name]['elementClass']  = 'Date';
                        $fieldSettings[$name]['storageFormat'] = 'yyyy-MM-dd';
                        break;

                    case "appointment":
                        if (! $appointments) {
                            $agenda       = $this->loader->getAgenda();
                            $appointments = $agenda->getActiveAppointments($respondentId, $organizationId, $patientNr);
                            // MUtil_Echo::track($appointments);
                        }
                        $fieldSettings[$name]['elementClass'] = 'Select';
                        $fieldSettings[$name]['multiOptions'] = $empty + $appointments;
                        break;

                    default:
                        $fieldSettings[$name]['elementClass'] = 'Text';
                        $fieldSettings[$name]['size']         = 40;
                        break;
                }
            }
            if (isset($field['field_model_info'])) {
                // MUtil_Echo::track($name, array_keys($field['field_model_info']));
                $fieldSettings[$name] = MUtil_Lazy::rise($field['field_model_info']) + $fieldSettings[$name];
            }
        }

        return $fieldSettings;
    }

    public function getDataStorageModel()
    {

    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / code
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldCodes()
    {
        $fields = array();

        foreach ($this->_trackFields as $key => $field) {
            $fields[$key] = $field['gtf_field_code'];
        }

        return $fields;
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / field name
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldNames()
    {
        $fields = array();

        foreach ($this->_trackFields as $key => $field) {
            $fields[$key] = $field['gtf_field_name'];
        }

        return $fields;
    }

    /**
     * Returns an array name => code of all the fields of the type specified
     *
     * @param string $fieldType
     * @return array name => code
     */
    public function getFieldCodesOfType($fieldType)
    {
        $output = array();

        foreach ($this->_trackFields as $key => $field) {
            if ($fieldType == $field['gtf_field_type']) {
                $output[$key] = $field['gtf_field_code'];
            }
        }

        return $output;
    }

    /**
     * Returns an array name => label of all the fields of the type specified
     *
     * @param string $fieldType
     * @return array name => code
     */
    public function getFieldLabelsOfType($fieldType)
    {
        $output = array();

        foreach ($this->_trackFields as $key => $field) {
            if ($fieldType == $field['gtf_field_type']) {
                $output[$key] = $field['gtf_field_name'];
            }
        }

        return $output;
    }

    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsDataFor($respTrackId)
    {
        $defaults = array_fill_keys(array_keys($this->_trackFields), null);

        if (! $respTrackId) {
            // Return empty array as we do not store default values for fields
            return $defaults;
        }

        $sql     = "
            SELECT CONCAT(?, ?, gr2t2a_id_app_field) AS gr2t2f_id_field, gr2t2a_id_appointment AS gr2t2f_value
                FROM gems__respondent2track2appointment
                WHERE gr2t2a_id_respondent_track = ?
            UNION ALL
            SELECT CONCAT(?, ?, gr2t2f_id_field) AS gr2t2f_id_field, gr2t2f_value
                FROM gems__respondent2track2field
                WHERE gr2t2f_id_respondent_track = ?";

        $results = $this->db->fetchPairs($sql, array(
            Gems_Tracker_Model_FieldMaintenanceModel::APPOINTMENTS_NAME,
            self::FIELD_KEY_SEPARATOR,
            $respTrackId,
            Gems_Tracker_Model_FieldMaintenanceModel::FIELDS_NAME,
            self::FIELD_KEY_SEPARATOR,
            $respTrackId,
            ));

        // MUtil_Echo::track($respTrackId, $sql, $results);

        if ($results) {
            foreach ($results as $field => $result) {
                if (isset($this->_trackFields[$field])) {
                    switch ($this->_trackFields[$field]['gtf_field_type']) {
                        case 'multiselect':
                            $results[$field] = explode(self::FIELD_SEP, $result);
                            break;

                        case 'date':
                            if (empty($result)) {
                                $results[$field] = null;
                            } else {
                                $results[$field] = new MUtil_Date($result, Zend_Date::ISO_8601);
                            }

                        default:
                            break;
                    }
                }
            }
        }

        return $results + $defaults;
    }

    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @param array $data the current request data
     * @return Gems_Tracker_Model_FieldMaintenanceModel
     */
    public function getMaintenanceModel($detailed, $action, array $data)
    {
        if (isset($this->_maintenanceModels[$action])) {
            return $this->_maintenanceModels[$action];
        }

        $model = $this->tracker->createTrackClass('Model_FieldMaintenanceModel');

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $model->applyEditSettings($this->_trackId, $data);

                if ('create' === $action) {
                    $model->set('gtf_id_track', 'default', $this->_trackId);

                    // Set the default round order

                    // Load last row
                    $row = $model->loadFirst(
                            array('gtf_id_track' => $this->_trackId),
                            array('gtf_id_order' => SORT_DESC)
                            );

                    if ($row && isset($row['gtf_id_order'])) {
                        $new_order = $row['gtf_id_order'] + 10;
                        $model->set('gtf_id_order', 'default', $new_order);
                    }
                }
            } else {
                $model->applyDetailSettings($this->_trackId, $data);
            }

        } else {
            $model->applyBrowseSettings();
        }

        $this->_maintenanceModels[$action] = $model;

        return $model;
    }

    /**
     * True when this track contains appointment fields
     *
     * @return boolean
     */
    public function hasAppointmentFields()
    {
        if (null === $this->_hasAppointmentFields) {
            $this->_hasAppointmentFields = false;

            foreach ($this->_trackFields as $field) {
                if (self::TYPE_APPOINTMENT == $field['gtf_field_type']) {
                    $this->_hasAppointmentFields = true;
                    break;
                }
            }
        }

        return $this->_hasAppointmentFields;
    }

    /**
     * Is the field an appointment type
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isAppointment($fieldName)
    {
        return isset($this->_trackFields[$fieldName]) &&
            (self::TYPE_APPOINTMENT == $this->_trackFields[$fieldName]['gtf_field_type']);
    }

    /**
     * Saves the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id
     * @param array $data The values to save
     * @return int The number of changed fields
     */
    public function setFieldsData($respTrackId, array $data)
    {
        // Clean up any keys not in fields
        $data       = array_intersect_key($data, $this->_trackFields);
        $saveModels = array();

        // MUtil_Echo::track($data);

        foreach ($data as $key => $value) {
            // Perform generic pre-save transformations on the value
            if (is_array($value)) {
                $value = implode(self::FIELD_SEP, $value);
            }

            // Do the hard work for storing dates
            if (isset($this->_trackFields[$key]['gtf_field_type']) &&
                    ('date' == $this->_trackFields[$key]['gtf_field_type'])) {
                if (! empty($value)) {
                    $value = MUtil_Date::format(
                            $value,
                            'yyyy-MM-dd',
                            MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat')
                            );
                } else {
                    $value = null;
                }
            }

            $table  = 'gems__respondent2track2field';
            $prefix = 'gr2t2f';
            $row    = array(
                'gr2t2f_id_respondent_track' => $respTrackId,
                'gr2t2f_id_field'            => $this->_trackFields[$key]['gtf_id_field'],
                'gr2t2f_value'               => $value,
            );

            if (isset($this->_trackFields[$key]['field_save_info'])) {
                if (isset($this->_trackFields[$key]['field_save_info']['table'])) {
                    $table  = $this->_trackFields[$key]['field_save_info']['table'];
                }
                if (isset($this->_trackFields[$key]['field_save_info']['prefix'])) {
                    $prefix = $this->_trackFields[$key]['field_save_info']['prefix'];
                }
                if (isset($this->_trackFields[$key]['field_save_info']['saveMap'])) {
                    $map = $this->_trackFields[$key]['field_save_info']['saveMap'];
                    foreach ($row as $field => $value) {
                        if (isset($map[$field])) {
                            $row[$map[$field]] = $value;
                            unset($row[$field]);
                        }
                    }
                }
            }
            if (! isset($saveModels[$table])) {
                $saveModels[$table] = new MUtil_Model_TableModel($table);

                Gems_Model::setChangeFieldsByPrefix($saveModels[$table], $prefix);
            }
            $saveModels[$table]->save($row);
        }

        $changed = 0;
        foreach ($saveModels as $saveModel) {
            $changed = $changed + $saveModel->getChanged();
        }
        return $changed;
    }
}
