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
 * @version    $Id$
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
     * Appointment type
     */
    const TYPE_APPOINTMENT = 'appointment';

    /**
     * Date type
     */
    const TYPE_DATE = 'date';

    /**
     * The storage model for field data
     *
     * @var array
     */
    protected $_dataModel = array();

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
     * @var Gems_Tracker
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
                    $key = self::makeKey($field['sub'], $field['gtf_id_field']);
                    $this->_trackFields[$key] = $field;
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
     * Calculate the content for the track info field using the other fields
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

        $appointments  = null;
        $fieldSettings = array();
        $model         = $this->getMaintenanceModel(false, 'index', array('gtf_id_track' => $this->_trackId));

        foreach ($this->_trackFields as $name => $field) {
            $editField = $edit;

            $fieldSettings[$name] = array(
                'label'       => $field['gtf_field_name'],
                'required'    => $field['gtf_required'],
                'description' => $field['gtf_field_description'],
                );

            if ($field['gtf_readonly']) {
                $fieldSettings[$name]['elementClass'] = 'Exhibitor';
                $editField = false;
            }

            $typeFunction = 'getSettingsFor' . ucfirst($field['gtf_field_type']);
            if (method_exists($model, $typeFunction)) {
                $extra = $model->$typeFunction(
                        $field['gtf_field_values'],
                        $respondentId,
                        $organizationId,
                        $patientNr,
                        $editField
                        );
            } else {
                $extra = $model->getSettingsForType(
                        $field['gtf_field_type'],
                        $field['gtf_field_values'],
                        $respondentId,
                        $organizationId,
                        $patientNr,
                        $editField
                        );
            }
            $fieldSettings[$name] = $extra + $fieldSettings[$name];
        }

        return $fieldSettings;
    }

    /**
     * Get the storage model for field values
     *
     * @return Gems_Tracker_Model_FieldDataModel
     */
    public function getDataStorageModel()
    {
        if (! $this->_dataModel instanceof Gems_Tracker_Model_FieldDataModel) {
            $this->_dataModel = $this->tracker->createTrackClass('Model_FieldDataModel');
        }

        return $this->_dataModel;
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
        if (! $this->_trackFields) {
            return array();
        }

        // Set the default values to empty as we currently do not store default values for fields
        $output = array_fill_keys(array_keys($this->_trackFields), null);

        if (! $respTrackId) {
            return $output;
        }

        $model = $this->getDataStorageModel();
        $rows  = $model->load(array('gr2t2f_id_respondent_track' => $respTrackId));

        if ($rows) {
            foreach ($rows as $row) {
                $key = self::makeKey($row['sub'], $row['gr2t2f_id_field']);

                $value = $row['gr2t2f_value'];

                if (isset($this->_trackFields[$key], $this->_trackFields[$key]['gtf_field_type'])) {
                    $typeFunction = 'calculateOnLoad' . ucfirst($this->_trackFields[$key]['gtf_field_type']);
                    if (method_exists($model, $typeFunction)) {
                        $value = $model->$typeFunction($value, $output, $respTrackId);
                    }
                }

                $output[$key] = $value;
            }
        }
        // MUtil_Echo::track($output);

        return $output;
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
     * Make the external field key
     *
     * @param string $sub
     * @param int $fieldId
     * @return string
     */
    public static function makeKey($sub, $fieldId)
    {
        return $sub . self::FIELD_KEY_SEPARATOR . $fieldId;
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
        $data  = array_intersect_key($data, $this->_trackFields);
        $model = $this->getDataStorageModel();
        $saves = array();

        // MUtil_Echo::track($data);
        foreach ($data as $key => $value) {
            if (isset($this->_trackFields[$key])) {
                $field = $this->_trackFields[$key];

                $calcUsing    = array();
                $typeFunction = 'calculateOnSave' . ucfirst($field['gtf_field_type']);
                if (method_exists($model, $typeFunction)) {
                    // Perform automatic calculation
                    if (isset($field['gtf_calculate_using'])) {
                        $sources = explode(
                                Gems_Tracker_Model_FieldMaintenanceModel::FIELD_SEP,
                                $field['gtf_calculate_using']
                                );

                        foreach ($sources as $source) {
                            if (isset($data[$source]) && $data[$source]) {
                                $calcUsing[$source] = $data[$source];
                            } else {
                                $calcUsing[$source] = null;
                            }
                        }
                    }
                    $value = $model->$typeFunction($value, $calcUsing, $data, $respTrackId);
                }

                $saves[] = array(
                    'sub'                        => $field['sub'],
                    'gr2t2f_id_respondent_track' => $respTrackId,
                    'gr2t2f_id_field'            => $field['gtf_id_field'],
                    'gr2t2f_value'               => $value,
                );
            }
        }
        $model->saveAll($saves);

        return $model->getChanged();
    }
}
