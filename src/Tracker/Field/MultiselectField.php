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

use Gems\Tracker\Model\Dependency\ValuesMaintenanceDependency;
use MUtil\Model\Type\ConcatenatedRow;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:42:43
 */
class MultiselectField extends FieldAbstract
{
    /**
     * @var string to use as display separator
     */
    protected string $displaySeparator = ' ';

    /**
     * @var bool When true the value is saved with padded seperators
     */
    protected bool $padSeperators = false;

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings): void
    {
        $concatter = new ConcatenatedRow(parent::FIELD_SEP, $this->displaySeparator, $this->padSeperators);
        //$multiKeys = explode(parent::FIELD_SEP, (string)$this->fieldDefinition['gtf_field_value_keys'] ?? '');
        //$multi     = explode(parent::FIELD_SEP, (string)$this->fieldDefinition['gtf_field_values'] ?? '');
        $settings  = $concatter->getSettings() + $settings;

        $settings['elementClass'] = 'MultiCheckbox';
        $settings['multiOptions'] = $this->getMultiOptions();
    }

    /**
     * Calculation the field info display for this type
     *
     * @param string|array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo($currentValue, array $fieldData): mixed
    {
        $options = $this->getMultiOptions();

        if (is_array($currentValue)){
            $values = $currentValue;
        } else {
            $values = explode(parent::FIELD_SEP, trim($currentValue, parent::FIELD_SEP));
        }
        $output = [];
        foreach ($values as $value) {
            if (isset($options[$value])) {
                $output[] = $options[$value];
            } else {
                $output[] = $value;
            }
        }
        if ($output) {
            return implode($this->displaySeparator, $output);
        }
        return null;
    }

    protected function getMultiOptions()
    {
        $multiKeys = explode(parent::FIELD_SEP, $this->fieldDefinition['gtf_field_value_keys'] ?? '');
        $multi     = explode(parent::FIELD_SEP, $this->fieldDefinition['gtf_field_values'] ?? '');

        return ValuesMaintenanceDependency::combineKeyValues($multiKeys, $multi);
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
        if (is_array($currentValue)) {
            if ($this->padSeperators) {
                return parent::FIELD_SEP . implode(parent::FIELD_SEP, $currentValue) . parent::FIELD_SEP;
            } else {
                return implode(parent::FIELD_SEP, $currentValue);
            }
        }

        return $currentValue;
    }
}
