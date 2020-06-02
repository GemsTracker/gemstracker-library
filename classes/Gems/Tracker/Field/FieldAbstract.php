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

use Gems\Tracker\Engine\FieldsDefinition;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:40:28
 */
abstract class FieldAbstract extends \MUtil_Translate_TranslateableAbstract implements FieldInterface
{
    /**
     * Option separator for fields
     */
    const FIELD_SEP = '|';

    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected $_dependsOn;

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected $_effecteds;

    /**
     *
     * @var array  Field definition array
     */
    protected $_fieldDefinition;

    /**
     *
     * @var string
     */
    protected $_fieldKey;

    /**
     *
     * @var int gems__tracks id for this field
     */
    protected $_trackId;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @param int $trackId gems__tracks id for this field
     * @param string $key The field key
     * @param array $fieldDefinition Field definition array
     */
    public function __construct($trackId, $key, array $fieldDefinition)
    {
        $this->_trackId         = $trackId;
        $this->_fieldKey        = $key;
        $this->_fieldDefinition = $fieldDefinition;
    }

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    abstract protected function addModelSettings(array &$settings);


    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo($currentValue, array $fieldData)
    {
        return $currentValue;
    }

    /**
     * Calculate the field value using the current values
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other known field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return mixed the new value
     */
    public function calculateFieldValue($currentValue, array $fieldData, array $trackData)
    {
        if (null === $currentValue && isset($this->_fieldDefinition['gtf_field_default'])) {
            return $this->_fieldDefinition['gtf_field_default'];
        }

        return $currentValue;
    }

    /**
     * Signal the start of a new calculation round (for all fields)
     *
     * @param array $trackData The currently available track data (track id may be empty)
     * @return \Gems\Tracker\Field\FieldAbstract
     */
    public function calculationStart(array $trackData)
    {
        return $this;
    }

    /**
     * Get the fields that should be used for calculation,
     * first field to use first.
     *
     * I.e. the last selected field in field maintenance
     * is the first field in the output array.
     *
     * @param array $fieldData The fields being saved
     * @return array [fieldKey => fieldValue]
     */
    public function getCalculationFields(array $fieldData)
    {
        $output = array();

        // Perform automatic calculation
        if (isset($this->_fieldDefinition['gtf_calculate_using'])) {
            $sources = explode(self::FIELD_SEP, $this->_fieldDefinition['gtf_calculate_using']);

            foreach ($sources as $source) {
                if (isset($fieldData[$source]) && $fieldData[$source]) {
                    $output[$source] = $fieldData[$source];
                } else {
                    $output[$source] = null;
                }
            }
        }
        return array_reverse($output, true);
    }

    /**
     *
     * @return string The field code
     */
    public function getCode()
    {
        return $this->_fieldDefinition['gtf_field_code'];
    }

    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @return array Null or an array of respondent track fields
     */
    public function getDataModelDependsOn()
    {
        return $this->_dependsOn;
    }

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
    public function getDataModelDependyChanges(array $context, $new)
    {
        return null;
    }

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @return array Null or an array of model settings that change for this field
     */
    public function getDataModelEffecteds()
    {
        return $this->_effecteds;
    }

    /**
     *
     * @return array Of settings to add to a model using these fields
     */
    public function getDataModelSettings()
    {
        $output['label']       = $this->getLabel();
        $output['required']    = $this->_fieldDefinition['gtf_required'];
        $output['description'] = $this->_fieldDefinition['gtf_field_description'];
        $output['noSort']      = true;

        $this->addModelSettings($output);

        if ($this->isReadOnly()) {
            $output['elementClass'] = 'Exhibitor';
        }

        return $output;
    }

    /**
     *
     * @return int The track field id
     */
    public function getFieldId()
    {
        return $this->_fieldDefinition['gtf_id_field'];
    }

    /**
     *
     * @return string The track field key as used by the union model
     */
    public function getFieldKey()
    {
        return $this->_fieldKey;
    }

    /**
     *
     * @return string The field type
     */
    public function getFieldType()
    {
        return $this->_fieldDefinition['gtf_field_type'];
    }

    /**
     *
     * @return string The track field sub (model) value
     */
    public function getFieldSub()
    {
        return $this->_fieldDefinition['sub'];
    }

    /**
     *
     * @return string The field label
     */
    public function getLabel()
    {
        return $this->_fieldDefinition['gtf_field_name'];
    }

    /**
     *
     * @return string The track field key for the manual setting
     */
    public function getManualKey()
    {
        return $this->getFieldKey() . FieldsDefinition::FIELD_KEY_SEPARATOR . 'manual';
    }

    /**
     *
     * @return array Of settings to add to a model if this is a manual check field
     */
    public function getManualModelSettings()
    {
        if ($this->hasManualSetOption()) {
            return [
                'label'        => sprintf($this->_('Set %s'), strtolower($this->getLabel())),
                'description'  => $this->_('Manually set fields will never be (re)calculated.'),
                'elementClass' => 'Radio',
                'multiOptions' => $this->util->getTranslated()->getDateCalculationOptions(),
                'separator'    => ' ',
                ];
        }
    }

    /**
     *
     * @return int The field order
     */
    public function getOrder()
    {
        return $this->_fieldDefinition['gtf_id_order'];
    }

    /**
     *
     * @return boolean True when this field can be calculated
     */
    public function hasCalculation()
    {
        return (boolean) $this->_fieldDefinition['gtf_calculate_using'];
    }

    /**
     *
     * @return boolean When this field has dependencies
     */
    public function hasDataModelDependencies()
    {
        return (boolean) $this->_dependsOn && $this->_effecteds;
    }

    /**
     *
     * @return boolean When this field can be calculated, but also set manually
     */
    public function hasManualSetOption()
    {
        return (! $this->isReadOnly()) && $this->hasCalculation();
    }

    /**
     * Should the label be included in the track information?
     *
     * @return boolean
     */
    public function isLabelInTrackInfo()
    {
        return $this->_fieldDefinition['gtf_track_info_label'];
    }

    /**
     *
     * @return boolean True when this field is read only
     */
    public function isReadOnly()
    {
        return $this->_fieldDefinition['gtf_readonly'];
    }

    /**
     * Calculation the field value when loading from a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataLoad($currentValue, array $fieldData)
    {
        return $currentValue;
    }

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave($currentValue, array $fieldData)
    {
        return $currentValue;
    }

    /**
     * Should this field be added to the track info
     *
     * @return boolean
     */
    public function toTrackInfo()
    {
        return $this->_fieldDefinition['gtf_to_track_info'];
    }
}
