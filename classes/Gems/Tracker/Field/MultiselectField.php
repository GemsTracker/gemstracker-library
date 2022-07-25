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
 * @since      Class available since version 1.6.5 4-mrt-2015 11:42:43
 */
class MultiselectField extends FieldAbstract
{
    /**
     * @var string to use as display separator
     */
    protected $displaySeparator = ' ';

    /**
     * @var bool When true the value is saved with padded seperators
     */
    protected $padSeperators = false;
    
    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings)
    {
        $concatter = new \MUtil\Model\Type\ConcatenatedRow(parent::FIELD_SEP, $this->displaySeparator, $this->padSeperators);
        $multiKeys = explode(parent::FIELD_SEP, $this->_fieldDefinition['gtf_field_value_keys']);
        $multi     = explode(parent::FIELD_SEP, $this->_fieldDefinition['gtf_field_values']);
        $settings  = $concatter->getSettings() + $settings;

        $settings['elementClass'] = 'MultiCheckbox';
        $settings['multiOptions'] = array_combine($multiKeys, $multi);
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
