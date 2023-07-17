<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:11:42
 */
interface FieldInterface
{
    /**
     * Calculation the field info display for this type
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo(mixed $currentValue, array $fieldData): mixed;

    /**
     * Calculate the field value using the current values
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other known field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return mixed the new value
     */
    public function calculateFieldValue(mixed $currentValue, array $fieldData, array $trackData): mixed;

    /**
     * Signal the start of a new calculation round (for all fields)
     *
     * @param array $trackData The currently available track data (track id may be empty)
     * @return self
     */
    public function calculationStart(array $trackData): self;

    /**
     *
     * @return string The field code
     */
    public function getCode(): string;

    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @return array Null or an array of respondent track fields
     */
    public function getDataModelDependsOn(): array|null;

    /**
     * Returns the changes to the model for this field that must be made in an array consisting of
     *
     * <code>
     *  array(setting1 => $value1, setting2 => $value2, ...),
     * </code>
     *
     * By using [] array notation in the setting array key you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param bool $new True when the item is a new record not yet saved
     * @return array (setting => value)
     */
    public function getDataModelDependencyChanges(array $context, bool $new): ?array;

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @return array Null or an array of model settings that change for this field
     */
    public function getDataModelEffecteds(): array|null;

    /**
     *
     * @return array Of settings to add to a model using these fields
     */
    public function getDataModelSettings(): array;

    /**
     *
     * @return int The track field id
     */
    public function getFieldId(): int;

    /**
     *
     * @return string The track field key as used by the union model
     */
    public function getFieldKey(): string;

    /**
     *
     * @return int The field order
     */
    public function getOrder(): int;

    /**
     *
     * @return string The track field sub (model) value
     */
    public function getFieldSub(): string;

    /**
     *
     * @return string The field type
     */
    public function getFieldType(): string;

    /**
     *
     * @return string The field label
     */
    public function getLabel(): string;

    /**
     *
     * @return string The track field key for the manual setting
     */
    public function getManualKey(): string;

    /**
     *
     * @return array Of settings to add to a model if this is a manual check field
     */
    public function getManualModelSettings(): array;

    /**
     * Setting function for activity select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param bool $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     * /
    public function getRespondentTrackSettings($values, $respondentId, $organizationId, $patientNr = null, $edit = true);

    /**
     * Setting function for activity select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param bool $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     * /
    public function getTrackMaintenanceSettings($values, $respondentId, $organizationId, $patientNr = null, $edit = true);
    // */

    /**
     *
     * @return bool When this field can be calculated, but also set manually
     */
    public function hasManualSetOption(): bool;

    /**
     * Should the label be included in the track information?
     *
     * @return bool
     */
    public function isLabelInTrackInfo(): bool;

    /**
     *
     * @return bool True when this field is read only
     */
    public function isReadOnly(): bool;
        
    /**
     *
     * @return bool When this field has dependencies
     */
    public function hasDataModelDependencies(): bool;

    /**
     * Calculate the field value using the current values
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataLoad(mixed $currentValue, array $fieldData): mixed;

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave(mixed $currentValue, array $fieldData): mixed;

    /**
     * Should this field be added to the track info
     *
     * @return bool
     */
    public function toTrackInfo(): bool;
}
