<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Engine;

use Gems\Tracker\Field\FieldInterface;
use Gems\Tracker\Model\Dependency\FieldDataDependency;
use Gems\Tracker\Model\FieldDataModel;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Tracker\Model\LogFieldDataModel;
use MUtil\Model\Dependency\OffOnElementsDependency;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Dependency\DependencyInterface;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class FieldsDefinition
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
     * @var \Gems\Tracker\Model\FieldDataModel
     */
    protected ?FieldDataModel $_dataModel = null;

    /**
     * Array of Fieldobjects Can be an empty array.
     *
     * @var \Gems\Tracker\Field\FieldInterface[]
     */
    protected ?array $_fields = null;

    /**
     * Cache for appointment fields check
     *
     * @var boolean
     */
    private bool|null $_hasAppointmentFields = null;

    /**
     * @var LogFieldDataModel
     */
    protected ?LogFieldDataModel $_logModel = null;

    /**
     * Stores the models for each action
     *
     * @var array
     */
    protected array $_maintenanceModels = [];

    /**
     * Can be an empty array.
     *
     * @var array The gems__track_fields + gems__track_appointments data
     */
    protected array $_trackFields = [];

    /**
     * True when the fields have changed during the last call to processBeforeSave
     *
     * @var boolean
     */
    public bool $changed = false;

    /**
     * True when there exist fields
     *
     * @var boolean
     */
    public bool $exists = false;

    /**
     *
     * @var int Maximum length of the track info field
     */
    protected int $maxTrackInfoChars = 250;

    /**
     * Construct the defintion for this gems__tracks track id.
     *
     * @param int $trackId The track id from gems__tracks
     */
    public function __construct(
        protected readonly int $trackId,
        protected readonly ProjectOverloader $projectOverloader,
    )
    {
        $this->_ensureTrackFields();
    }

    /**
     * Loads the $this->_trackFields array, if not already there
     */
    protected function _ensureTrackFields()
    {
        if (! is_array($this->_fields)) {
            // Check for cases where the track id is zero, but there is a field for track 0 in the db
            if ($this->trackId) {
                $model  = $this->getMaintenanceModel();
                $fields = $model->load(['gtf_id_track' => $this->trackId], ['gtf_id_order' => SORT_ASC]);
            } else {
                $fields = false;
            }

            $this->_fields      = [];
            $this->_trackFields = [];
            if (is_array($fields)) {
                $this->exists = true;

                foreach ($fields as $field) {
                    $key = self::makeKey($field['sub'], $field['gtf_id_field']);

                    $class = 'Tracker\\Field\\' . ucfirst($field['gtf_field_type']) . 'Field';
                    $this->_fields[$key] = $this->projectOverloader->create($class, $this->trackId, $key, $field);

                    $this->_trackFields[$key] = $field;
                }
                // \MUtil\EchoOut\EchoOut::track($this->_trackFields);
            } else {
                $this->exists = false;
            }
        }
    }

    /**
     * Calculate the content for the track info field using the other fields
     *
     * @param array $data The field values
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo(array $data): ?string
    {
        if (! $this->exists) {
            return null;
        }

        $output = [];

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
     * @param MetaModelInterface $model
     * @return DependencyInterface[]|null
     */
    public function getDataModelDependencies(MetaModelInterface $model): ?array
    {
        if (! $this->exists) {
            return null;
        }

        $output     = [];
        $dependency = $this->projectOverloader->create(FieldDataDependency::class);

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                if ($field->hasManualSetOption()) {
                    $mKey = $field->getManualKey();
                    $output[] = new OffOnElementsDependency($mKey, $key, 'readonly', $model);
                }
                $dependsOn = $field->getDataModelDependsOn();

                if ($field->hasDataModelDependencies()) {
                    $dependency->addField($field);
                }
            }
        }

        if ($dependency->getFieldCount()) {
            $output[] = $dependency;
        }

        return $output;
    }

    /**
     * Get a big array with model settings for fields in a track
     *
     * @return array fieldname => array(settings)
     */
    public function getDataModelSettings(): array
    {
        if (! $this->exists) {
            return [];
        }

        $fieldSettings = [];

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                if ($field->hasManualSetOption()) {
                    $mkey = $field->getManualKey();
                    $fieldSettings[$mkey] = $field->getManualModelSettings();
                }

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
    public function getDataStorageModel(): FieldDataModel
    {
        if (! $this->_dataModel instanceof FieldDataModel) {
            /**
             * @var FieldDataModel $dataModel
             */
            $dataModel = $this->projectOverloader->create('Tracker\\Model\\FieldDataModel');
            $this->_dataModel = $dataModel;
        }

        return $this->_dataModel;
    }

    /**
     * Get a specific field
     *
     * @param string $key
     * @return \Gems\Tracker\Field\FieldInterface|null
     */
    public function getField(string $key): ?FieldInterface
    {
        if (isset($this->_fields[$key])) {
            return $this->_fields[$key];
        }
        return null;
    }

    /**
     * Get a specific field by field code
     *
     * @param string $code
     * @return \Gems\Tracker\Field\FieldInterface
     */
    public function getFieldByCode(string $code): ?FieldInterface
    {
        foreach ($this->_fields as $field) {
            if ($field instanceof FieldInterface) {
                if ($field->getCode() == $code) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Get a specific field by field order
     *
     * @param int $order
     * @return \Gems\Tracker\Field\FieldInterface
     */
    public function getFieldByOrder(int $order): ?FieldInterface
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
    public function getFieldCodes(): array
    {
        $output = [];

        foreach ($this->_trackFields as $key => $field) {
            $output[$key] = $field['gtf_field_code'];
        }

        return $output;
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / code
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldDefaults(): array
    {
        $output = [];

        foreach ($this->_trackFields as $key => $field) {
            if (array_key_exists('gtf_field_default', $field)) {
                $output[$key] = $field['gtf_field_default'];
            }
        }

        return $output;
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / field name
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldNames(): array
    {
        $fields = [];

        $this->_ensureTrackFields();

        foreach ($this->_trackFields as $key => $field) {
            $fields[$key] = $field['gtf_field_name'];
        }

        return $fields;
    }

    /**
     * Returns an array name => code of all the fields of the type specified
     *
     * @param string|array $fieldType One or more field types
     * @return array name => code
     */
    public function getFieldCodesOfType(array|string $fieldType): array
    {
        return $this->getFieldsOfType($fieldType, 'gtf_field_code');
    }

    /**
     * Returns an array name => label of all the fields of the type specified
     *
     * @param string|array $fieldType One or more field types
     * @return array name => code
     */
    public function getFieldLabelsOfType(array|string $fieldType): array
    {
        return $this->getFieldsOfType($fieldType, 'gtf_field_name');
    }

    /**
     * Returns the field data for the respondent track id.
     *
     * @return \Gems\Tracker\Field\FieldInterface[] of the existing fields for this track
     */
    public function getFields(): array
    {
        return $this->_fields;
    }
    
    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId \Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsDataFor(int $respTrackId): array
    {
        if (! $this->_fields) {
            return [];
        }

        // Set the default values to empty as we currently do not store default values for fields
        $output = array_fill_keys(array_keys($this->_fields), null);

        if (! $respTrackId) {
            return $output;
        }

        $model = $this->getDataStorageModel();
        $rows  = $model->load(['gr2t2f_id_respondent_track' => $respTrackId]);

        if ($rows) {
            foreach ($rows as $row) {
                $key   = self::makeKey($row['sub'], $row['gr2t2f_id_field']);

                if (isset($this->_fields[$key]) && ($this->_fields[$key] instanceof FieldInterface)) {
                    if ($this->_fields[$key]->hasManualSetOption()) {
                        $output[$this->_fields[$key]->getManualKey()] = $row['gr2t2f_value_manual'];
                    }

                    $value = $this->_fields[$key]->onFieldDataLoad($row['gr2t2f_value'], $output, $respTrackId);
                } else {
                    $value = $row['gr2t2f_value']; // Should not occur
                }

                $output[$key] = $value;
            }
        }

        return $output;
    }

    /**
     * Returns an array name => $element of all the fields of the type specified
     *
     * @param string|array $fieldType One or more field types
     * @return array name => $element
     */
    protected function getFieldsOfType(array|string $fieldType, string $element): array
    {
        $output     = [];
        $fieldArray = (array) $fieldType;

        foreach ($this->_trackFields as $key => $field) {
            if (in_array($field['gtf_field_type'], $fieldArray)) {
                $output[$key] = $field[$element];
            }
        }

        return $output;
    }

    /**
     * Get the storage model for field values
     *
     * @return LogFieldDataModel
     */
    public function getLogStorageModel(): LogFieldDataModel
    {
        if (! $this->_logModel instanceof LogFieldDataModel) {
            /**
             * @var LogFieldDataModel $logFieldDataModel
             */
            $logFieldDataModel = $this->projectOverloader->create('Tracker\\Model\\LogFieldDataModel');
            $this->_logModel = $logFieldDataModel;

        }

        return $this->_logModel;
    }
    
    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \Gems\Tracker\Model\FieldMaintenanceModel
     */
    public function getMaintenanceModel(bool $detailed = false, string $action = 'index'): FieldMaintenanceModel
    {
        if (isset($this->_maintenanceModels[$action])) {
            return $this->_maintenanceModels[$action];
        }

        /**
         * @var FieldMaintenanceModel $model
         */
        $model = $this->projectOverloader->create('Tracker\\Model\\FieldMaintenanceModel');

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $model->applyEditSettings();

                if ('create' === $action) {
                    $model->set('gtf_id_track', 'default', $this->trackId);

                    // Set the default round order

                    // Load last row
                    $row = $model->loadFirst(
                            ['gtf_id_track' => $this->trackId],
                            ['gtf_id_order' => SORT_DESC],
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
     * Returns the manual fields names for the fields that can be set manually
     *
     * @return array manual_field_name => null
     */
    public function getManualFields(): array
    {
        $output = [];
        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                if ($field->hasManualSetOption()) {
                    $output[$field->getManualKey()] = null;
                }
            }
        }

        return $output;
    }

    /**
     * True when this track contains appointment fields
     *
     * @return boolean
     */
    public function hasAppointmentFields(): bool
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
    public function isAppointment(string $fieldName): bool
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
    public static function makeKey(string $sub, int|null $fieldId = null): string
    {
        $key = $sub . self::FIELD_KEY_SEPARATOR;
        if ($fieldId !== null) {
            $key .= $fieldId;
        }
        return $key;
    }

    /**
     * Processes the values and and changes them as required
     *
     * @param array $fieldData The field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return array The processed data
     */
    public function processBeforeSave(array $fieldData, array $trackData): ?array
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

        $output = [];

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                if ($field->hasManualSetOption()) {
                    $mkey          = $field->getManualKey();
                    $manual        = isset($fieldData[$mkey ]) ? (boolean) $fieldData[$mkey] : false;
                    $output[$mkey] = $manual ? 1 : 0;
                } else {
                    $manual = false;
                }

                $inVal  = isset($fieldData[$key]) ? $fieldData[$key] : null;
                if ($manual) {
                    $outVal = $inVal;
                } else {
                    $outVal = $field->calculateFieldValue($inVal, $fieldData, $trackData);

                    if (is_array($outVal) || is_array($inVal)) {
                       if (is_array($outVal) && is_array($inVal)) {
                            $changedNow = ($outVal != $inVal);
                        } else {
                            $changedNow = true;
                        }
                    } elseif (($inVal instanceof \DateTimeInterface) || ($outVal instanceof \DateTimeInterface)) {
                        if (($inVal instanceof \DateTimeInterface) && ($outVal instanceof \DateTimeInterface)) {
                            $changedNow = $inVal->getTimestamp() != $outVal->getTimestamp();
                        } else {
                            $changedNow = true;
                        }
                    } else {
                        $changedNow = ((string) $inVal !== (string) $outVal);
                    }
                    $this->changed = $this->changed || $changedNow;
                }
                $fieldData[$key] = $outVal; // Make sure the new value is available to the next field
                $output[$key]    = $outVal;
            }
        }

        return $output;
    }

    /**
     * Saves the field data for the respondent track id.
     *
     * @param int $respTrackId \Gems respondent track id
     * @param array $fieldData The values to save, only the key is used, not the code
     * @return int The number of changed fields
     */
    public function saveFields(int $respTrackId, array $fieldData): int
    {
        $saves  = [];
        $logs   = [];
        
        $oldFieldData = $this->getFieldsDataFor($respTrackId);
        // \MUtil\EchoOut\EchoOut::track($fieldData, $oldFieldData);

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                $manual = $oldManual = false;
                if ($field->hasManualSetOption()) {
                    $mKey = $field->getManualKey();
                    if (array_key_exists($mKey, $fieldData)) {
                        $manual = (boolean) $fieldData[$mKey];
                    }
                    if (array_key_exists($mKey, $oldFieldData)) {
                        $oldManual = (boolean)$oldFieldData[$mKey];
                    }
                }

                if (array_key_exists($key, $fieldData)) {
                    $inVal = $fieldData[$key];
                } else {
                    // There is no value do not save
                    continue;
                }

                $saveVal = $field->onFieldDataSave($inVal, $fieldData);

                $saves[] = [
                    'sub'                        => $field->getFieldSub(),
                    'gr2t2f_id_respondent_track' => $respTrackId,
                    'gr2t2f_id_field'            => $field->getFieldId(),
                    'gr2t2f_value'               => $saveVal,
                    'gr2t2f_value_manual'        => $manual ? 1 : 0,
                ];
                
                // \MUtil\EchoOut\EchoOut::track(array_key_exists($key, $oldFieldData), $saveVal, $oldFieldData[$key], $manual, $oldManual);
                if ((! array_key_exists($key, $oldFieldData)) || ($saveVal != $oldFieldData[$key])  || ($manual != $oldManual)) {
                    $logs[] = [
                        'glrtf_id_respondent_track' => $respTrackId,
                        'glrtf_id_sub'              => $field->getFieldSub(),
                        'glrtf_id_field'            => $field->getFieldId(),
                        'glrtf_old_value'           => isset($oldFieldData[$key]) ? $field->onFieldDataSave($oldFieldData[$key], $oldFieldData) : null,
                        'glrtf_old_value_manual'    => $oldManual ? 1 : 0,
                        'glrtf_new_value'           => $saveVal,
                        'glrtf_new_value_manual'    => $manual ? 1 : 0,
                        ];
                }
            }
        }

        $model = $this->getDataStorageModel();
        $model->saveAll($saves);
        
        if ($logs) {
            // \MUtil\EchoOut\EchoOut::track($logs);
            $this->getLogStorageModel()->saveAll($logs);
        }

        return $model->getChanged();
    }

    public static function splitKey(string $key): ?array
    {
        if (strpos($key, self::FIELD_KEY_SEPARATOR) === false) {
            return null;
        }
        list($sub, $fieldId) = explode(self::FIELD_KEY_SEPARATOR, $key, 2);

        return ['sub' => $sub, 'gtf_id_field' => $fieldId];
    }
}