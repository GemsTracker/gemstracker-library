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
use Gems\Util\Translated;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:42:24
 */
class SelectField extends FieldAbstract
{
    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings): void
    {
        $empty = [];
        if (!$this->fieldDefinition['gtf_required'] || $this->fieldDefinition['gtf_field_default'] === null) {
            $empty = $this->translatedUtil->getEmptyDropdownArray();
        }

        $settings['elementClass'] = 'Select';
        $settings['multiOptions'] = $empty + $this->getMultiOptions();
    }

    /**
     * @inheritDoc
     */
    public function calculateFieldInfo($currentValue, array $fieldData): mixed
    {
        $options = $this->getMultiOptions();

        if (isset($options[$currentValue])) {
            return $options[$currentValue];
        }
        return $currentValue;
    }

    protected function getMultiOptions()
    {
        $multiKeys = [];
        if (!empty($this->fieldDefinition['gtf_field_value_keys'])) {
            $multiKeys = explode(parent::FIELD_SEP, $this->fieldDefinition['gtf_field_value_keys']);
        }
        $multi = [];
        if (!empty($this->fieldDefinition['gtf_field_values'])) {
            $multi = explode(parent::FIELD_SEP, $this->fieldDefinition['gtf_field_values']);
        }

        if ($multiKeys) {
            return ValuesMaintenanceDependency::combineKeyValues($multiKeys, $multi);
        }
        return ValuesMaintenanceDependency::combineKeyValues($multi, $multi);
    }
}
