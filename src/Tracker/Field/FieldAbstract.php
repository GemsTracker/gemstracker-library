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
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:40:28
 */
abstract class FieldAbstract implements FieldInterface
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
    protected array|null $_dependsOn = null;

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected array|null $_effecteds = null;

    /**
     *
     * @param int $trackId gems__tracks id for this field
     * @param string $fieldKey The field key
     * @param array $fieldDefinition Field definition array
     */
    public function __construct(
        protected int $trackId,
        protected string $fieldKey,
        protected array $fieldDefinition,
        protected TranslatorInterface $translator,
        protected Translated $translatedUtil,
    )
    {
    }

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    abstract protected function addModelSettings(array &$settings): void;


    /**
     * Calculation the field info display for this type
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo(mixed $currentValue, array $fieldData): mixed
    {
        return $currentValue;
    }

    /**
     * Calculate the field value using the current values
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other known field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return mixed the new value
     */
    public function calculateFieldValue(mixed $currentValue, array $fieldData, array $trackData): mixed
    {
        if (null === $currentValue && isset($this->fieldDefinition['gtf_field_default'])) {
            return $this->fieldDefinition['gtf_field_default'];
        }

        return $currentValue;
    }

    /**
     * Signal the start of a new calculation round (for all fields)
     *
     * @param array $trackData The currently available track data (track id may be empty)
     * @return self
     */
    public function calculationStart(array $trackData): self
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
    public function getCalculationFields(array $fieldData): array
    {
        $output = [];

        // Perform automatic calculation
        if (isset($this->fieldDefinition['gtf_calculate_using'])) {
            $sources = explode(self::FIELD_SEP, $this->fieldDefinition['gtf_calculate_using']);

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
    public function getCode(): string|null
    {
        return $this->fieldDefinition['gtf_field_code'];
    }

    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @return array Null or an array of respondent track fields
     */
    public function getDataModelDependsOn(): array|null
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
    public function getDataModelDependencyChanges(array $context, bool $new): array|null
    {
        return null;
    }

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @return array Null or an array of model settings that change for this field
     */
    public function getDataModelEffecteds(): array|null
    {
        return $this->_effecteds;
    }

    /**
     *
     * @return array Of settings to add to a model using these fields
     */
    public function getDataModelSettings(): array
    {
        $output['label']       = $this->getLabel();
        $output['required']    = $this->fieldDefinition['gtf_required'];
        $output['description'] = $this->fieldDefinition['gtf_field_description'];
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
    public function getFieldId(): int
    {
        return (int)$this->fieldDefinition['gtf_id_field'];
    }

    /**
     *
     * @return string The track field key as used by the union model
     */
    public function getFieldKey(): string
    {
        return $this->fieldKey;
    }

    /**
     *
     * @return string The field type
     */
    public function getFieldType(): string
    {
        return $this->fieldDefinition['gtf_field_type'];
    }

    /**
     *
     * @return string The track field sub (model) value
     */
    public function getFieldSub(): string
    {
        return $this->fieldDefinition['sub'];
    }

    /**
     *
     * @return string The field label
     */
    public function getLabel(): string
    {
        return $this->fieldDefinition['gtf_field_name'];
    }

    /**
     *
     * @return string The track field key for the manual setting
     */
    public function getManualKey(): string
    {
        return $this->getFieldKey() . FieldsDefinition::FIELD_KEY_SEPARATOR . 'manual';
    }

    /**
     *
     * @return array Of settings to add to a model if this is a manual check field
     */
    public function getManualModelSettings(): array
    {
        if ($this->hasManualSetOption()) {
            return [
                'label'          => sprintf($this->translator->_('Set %s'), strtolower($this->getLabel())),
                'description'    => $this->translator->_('Manually set fields will never be (re)calculated.'),
                'elementClass'   => 'OnOffEdit',
                'onOffEditFor'   => $this->getFieldKey(),
                'onOffEditValue' => 1,
                'multiOptions'   => $this->translatedUtil->getDateCalculationOptions(),
                'separator'      => ' ',
                ];
        }
        return [];
    }

    /**
     *
     * @return int The field order
     */
    public function getOrder(): int
    {
        return $this->fieldDefinition['gtf_id_order'];
    }

    /**
     *
     * @return boolean True when this field can be calculated
     */
    public function hasCalculation(): bool
    {
        return (boolean) $this->fieldDefinition['gtf_calculate_using'];
    }

    /**
     *
     * @return boolean When this field has dependencies
     */
    public function hasDataModelDependencies(): bool
    {
        return $this->_dependsOn && $this->_effecteds;
    }

    /**
     *
     * @return boolean When this field can be calculated, but also set manually
     */
    public function hasManualSetOption(): bool
    {
        return (! $this->isReadOnly()) && $this->hasCalculation();
    }

    /**
     * Should the label be included in the track information?
     *
     * @return boolean
     */
    public function isLabelInTrackInfo(): bool
    {
        return (bool)$this->fieldDefinition['gtf_track_info_label'];
    }

    /**
     *
     * @return boolean True when this field is read only
     */
    public function isReadOnly(): bool
    {
        return (bool)$this->fieldDefinition['gtf_readonly'];
    }

    /**
     * Calculation the field value when loading from a respondent track
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataLoad(mixed $currentValue, array $fieldData): mixed
    {
        return $currentValue;
    }

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave(mixed $currentValue, array $fieldData): mixed
    {
        return $currentValue;
    }

    /**
     * Should this field be added to the track info
     *
     * @return boolean
     */
    public function toTrackInfo(): bool
    {
        return $this->fieldDefinition['gtf_to_track_info'];
    }
}
