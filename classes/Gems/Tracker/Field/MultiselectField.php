<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: MultiselectField.php $
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
     *
     * @var \Gems_Util
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
        $concatter = new \MUtil_Model_Type_ConcatenatedRow(parent::FIELD_SEP, ' ', false);
        $multi     = explode(parent::FIELD_SEP, $this->_fieldDefinition['gtf_field_values']);
        $settings  = $concatter->getSettings() + $settings;

        $settings['elementClass'] = 'MultiCheckbox';
        $settings['multiOptions'] = array_combine($multi, $multi);
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
            return implode(parent::FIELD_SEP, $currentValue);
        }

        return $currentValue;
    }
}
