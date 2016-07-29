<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldInterface.php $
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
     *
     * @param int $trackId gems__tracks id for this field
     * @param string $key The field key
     * @param array $fieldDefinition Field definition array
     */
    public function __construct($trackId, $key, array $fieldData);

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo($currentValue, array $fieldData);

    /**
     * Calculate the field value using the current values
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other known field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return mixed the new value
     */
    public function calculateFieldValue($currentValue, array $fieldData, array $trackData);

    /**
     * Signal the start of a new calculation round (for all fields)
     *
     * @param array $trackData The currently available track data (track id may be empty)
     * @return \Gems\Tracker\Field\FieldAbstract
     */
    public function calculationStart(array $trackData);

    /**
     * On save calculation function
     *
     * @param array $currentValue The current value
     * @param array $values The values for the checked calculate from fields
     * @param array $fieldData The other values being saved
     * @param int $respTrackId Optional gems respondent track id
     * @return mixed the new value
     * /
    public function calculateOnSave($currentValue, array $values, array $fieldData, $respTrackId = null);

    /**
     *
     * @return string The field code
     */
    public function getCode();

    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @return array Null or an array of respondent track fields
     */
    public function getDataModelDependsOn();

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
     * @param boolean $new True when the item is a new record not yet saved
     * @return array (setting => value)
     */
    public function getDataModelDependyChanges(array $context, $new);

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @return array Null or an array of model settings that change for this field
     */
    public function getDataModelEffecteds();

    /**
     *
     * @return array Of settings to add to a model using these fields
     */
    public function getDataModelSettings();

    /**
     *
     * @return int The track field id
     */
    public function getFieldId();

    /**
     *
     * @return string The track field key as used by the union model
     */
    public function getFieldKey();

    /**
     *
     * @return int The field order
     */
    public function getOrder();

    /**
     *
     * @return string The track field sub (model) value
     */
    public function getFieldSub();

    /**
     *
     * @return string The field type
     */
    public function getFieldType();
    
    /**
     *
     * @return string The field label
     */
    public function getLabel();

    /**
     * Setting function for activity select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
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
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     * /
    public function getTrackMaintenanceSettings($values, $respondentId, $organizationId, $patientNr = null, $edit = true);
    // */

    /**
     * Should the label be included in the track information?
     *
     * @return boolean
     */
    public function isLabelInTrackInfo();

    /**
     *
     * @return boolean When this field has dependencies
     */
    public function hasDataModelDependencies();

    /**
     * Calculate the field value using the current values
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataLoad($currentValue, array $fieldData);

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave($currentValue, array $fieldData);

    /**
     * Should this field be added to the track info
     *
     * @return boolean
     */
    public function toTrackInfo();
}
