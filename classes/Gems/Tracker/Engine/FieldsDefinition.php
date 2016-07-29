<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Tracker\Engine;

use Gems\Tracker\Field\FieldInterface;
use Gems\Tracker\Model\Dependency\FieldDataDependency;
use Gems\Tracker\Model\FieldDataModel;

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class FieldsDefinition extends \MUtil_Translate_TranslateableAbstract
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
     * Date type
     */
    const TYPE_DATETIME = 'datetime';

    /**
     * The storage model for field data
     *
     * @var array
     */
    protected $_dataModel = array();

    /**
     * Can be an empty array.
     *
     * @var array of \Gems\Tracker\Field\FieldInterface objects
     */
    protected $_fields = false;

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
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * True when the fields have changed during the last call to processBeforeSave
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * True when there exist fields
     *
     * @var boolean
     */
    public $exists = false;

    /**
     *
     * @var int Maximum length of the track info field
     */
    protected $maxTrackInfoChars = 250;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Construct the defintion for this gems__tracks track id.
     *
     * @param int $trackId The track id from gems__tracks
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
        if (! is_array($this->_fields)) {
            // Check for cases where the track id is zero, but there is a field for track 0 in the db
            if ($this->_trackId) {
                $model  = $this->getMaintenanceModel();
                $fields = $model->load(array('gtf_id_track' => $this->_trackId), array('gtf_id_order' => SORT_ASC));
            } else {
                $fields = false;
            }

            $this->_fields      = array();
            $this->_trackFields = array();
            if (is_array($fields)) {
                $this->exists = true;

                foreach ($fields as $field) {
                    $key = self::makeKey($field['sub'], $field['gtf_id_field']);

                    $class = 'Field\\' . ucfirst($field['gtf_field_type']) . 'Field';
                    $this->_fields[$key] = $this->tracker->createTrackClass($class, $this->_trackId, $key, $field);

                    $this->_trackFields[$key] = $field;
                }
                // \MUtil_Echo::track($this->_trackFields);
            } else {
                $this->exists       = false;
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
     * @param array $data The field values
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo(array $data)
    {
        if (! $this->exists) {
            return null;
        }

        $output = array();

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                if ($field->toTrackInfo()) {
                    $inVal  = isset($data[$key]) ? $data[$key] : null;
                    $outVal = $field->calculateFieldInfo($inVal, $data);

                    if ($outVal && $field->isLabelInTrackInfo()) {
                        $label = $field->getLabel();
                        if ($label) {
                            $output[] = $label;
                        }
                    }

                    if (is_array($outVal)) {
                        $output = array_merge($output, array_filter($outVal));
                    } elseif ($outVal || ($outVal == 0)) {
                        $output[] = $outVal;
                    }
                }
            }
        }

        return substr(trim(implode(' ', $output)), 0, $this->maxTrackInfoChars);
    }

    /**
     * Get model dependency that changes model settings for each row when loaded
     *
     * @return \MUtil\Model\Dependency\DependencyInterface or null
     */
    public function getDataModelDependency()
    {
        if (! $this->exists) {
            return null;
        }

        $dependency = new FieldDataDependency();

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                $dependsOn = $field->getDataModelDependsOn();

                if ($field->hasDataModelDependencies()) {
                    $dependency->addField($field);
                }
            }
        }

        if ($dependency->getFieldCount()) {
            return $dependency;
        } else {
            return null;
        }
    }

    /**
     * Get a big array with model settings for fields in a track
     *
     * @return array fieldname => array(settings)
     */
    public function getDataModelSettings()
    {
        if (! $this->exists) {
            return array();
        }

        $fieldSettings = array();

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                $fieldSettings[$key] = $field->getDataModelSettings();
            }
        }

        return $fieldSettings;
    }

    /**
     * Get the storage model for field values
     *
     * @return \Gems\Tracker\Model\FieldDataModel
     */
    public function getDataStorageModel()
    {
        if (! $this->_dataModel instanceof FieldDataModel) {
            $this->_dataModel = $this->tracker->createTrackClass('Model\\FieldDataModel');
        }

        return $this->_dataModel;
    }

    /**
     * Get a specific field
     *
     * @param string $key
     * @return \Gems\Tracker\Field\FieldInterface
     */
    public function getField($key)
    {
        if (isset($this->_fields[$key])) {
            return $this->_fields[$key];
        }
    }

    /**
     * Get a specific field by field order
     *
     * @param int $order
     * @return \Gems\Tracker\Field\FieldInterface
     */
    public function getFieldByOrder($order)
    {
        foreach ($this->_fields as $field) {
            if ($field instanceof FieldInterface) {
                if ($field->getOrder() == $order) {
                    return $field;
                }
            }
        }

        return null;
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
     * @param string|array $fieldType One or more field types
     * @return array name => code
     */
    public function getFieldLabelsOfType($fieldType)
    {
        $output = array();

        if (is_array($fieldType)) {
            foreach ($this->_trackFields as $key => $field) {
                if (in_array($field['gtf_field_type'], $fieldType)) {
                    $output[$key] = $field['gtf_field_name'];
                }
            }
        } else {
            foreach ($this->_trackFields as $key => $field) {
                if ($fieldType == $field['gtf_field_type']) {
                    $output[$key] = $field['gtf_field_name'];
                }
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
        if (! $this->_fields) {
            return array();
        }

        // Set the default values to empty as we currently do not store default values for fields
        $output = array_fill_keys(array_keys($this->_fields), null);

        if (! $respTrackId) {
            return $output;
        }

        $model = $this->getDataStorageModel();
        $rows  = $model->load(array('gr2t2f_id_respondent_track' => $respTrackId));

        if ($rows) {
            foreach ($rows as $row) {
                $key   = self::makeKey($row['sub'], $row['gr2t2f_id_field']);

                if (isset($this->_fields[$key]) && ($this->_fields[$key] instanceof FieldInterface)) {
                    $value = $this->_fields[$key]->onFieldDataLoad($row['gr2t2f_value'], $output, $respTrackId);
                } else {
                    $value = $row['gr2t2f_value']; // Should not occur
                }

                $output[$key] = $value;
            }
        }
        // \MUtil_Echo::track($output);

        return $output;
    }

    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \Gems\Tracker\Model\FieldMaintenanceModel
     */
    public function getMaintenanceModel($detailed = false, $action = 'index')
    {
        if (isset($this->_maintenanceModels[$action])) {
            return $this->_maintenanceModels[$action];
        }

        $model = $this->tracker->createTrackClass('Model\\FieldMaintenanceModel');

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $model->applyEditSettings();

                if ('create' === $action) {
                    $model->set('gtf_id_track', 'default', $this->_trackId);

                    // Set the default round order

                    // Load last row
                    $row = $model->loadFirst(
                            array('gtf_id_track' => $this->_trackId),
                            array('gtf_id_order' => SORT_DESC),
                            false // Make sure this does not trigger type dependency
                            );

                    if ($row && isset($row['gtf_id_order'])) {
                        $newOrder = $row['gtf_id_order'] + 10;
                        $model->set('gtf_id_order', 'default', $newOrder);
                    }
                }
            } else {
                $model->applyDetailSettings();
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
     * Processes the values and and changes them as required
     *
     * @param array $fieldData The field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return array The processed data
     */
    public function processBeforeSave(array $fieldData, array $trackData)
    {
        $this->changed = false;

        if (! $this->exists) {
            return null;
        }

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                $field->calculationStart($trackData);
            }
        }

        $output = array();

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                $inVal  = isset($fieldData[$key]) ? $fieldData[$key] : null;
                $outVal = $field->calculateFieldValue($inVal, $fieldData, $trackData);

                if (is_array($outVal) || is_array($inVal)) {
                   if (is_array($outVal) && is_array($inVal)) {
                        $changedNow = ($outVal != $inVal);
                    } else {
                        $changedNow = true;
                    }
                } else {
                    $changedNow = ((string) $inVal !== (string) $outVal);
                }
                $this->changed = $this->changed || $changedNow;

                $fieldData[$key] = $outVal; // Make sure the new value is available to the next field
                $output[$key]    = $outVal;
            }
        }

        return $output;
    }

    /**
     * Saves the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id
     * @param array $fieldData The values to save
     * @return int The number of changed fields
     */
    public function saveFields($respTrackId, array $fieldData)
    {
        $saves = array();

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                if (array_key_exists($key, $fieldData)) {
                    $inVal = $fieldData[$key];
                } else {
                    // There is no value do not save
                    continue;
                }

                $saveVal = $field->onFieldDataSave($inVal, $fieldData);

                $saves[] = array(
                    'sub'                        => $field->getFieldSub(),
                    'gr2t2f_id_respondent_track' => $respTrackId,
                    'gr2t2f_id_field'            => $field->getFieldId(),
                    'gr2t2f_value'               => $saveVal,
                );
            }
        }

        $model = $this->getDataStorageModel();
        $model->saveAll($saves);

        return $model->getChanged();
    }

    /**
     * Split an external field key in component parts
     *
     * @param string $sub
     * @param int $fieldId
     * @return array 'sub' => suIdb, 'gtf_id_field; => fieldId
     */
    public static function splitKey($key)
    {
        if (strpos($key, self::FIELD_KEY_SEPARATOR) === false) {
            return null;
        }
        list($sub, $fieldId) = explode(self::FIELD_KEY_SEPARATOR, $key, 2);

        return array('sub' => $sub, 'gtf_id_field' => $fieldId);
    }
}
