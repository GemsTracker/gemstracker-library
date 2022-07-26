<?php

namespace Gems\Tracker\Field;

use Gems\Util\Translated;

class BooleanField extends FieldAbstract
{
    /**
     * @var Translated
     */
    protected $translatedUtil;

    public static $keyValues = [
        1,
        0
    ];

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings)
    {
        $empty = [];
        $multi = explode(parent::FIELD_SEP, $this->_fieldDefinition['gtf_field_values']);
        if (empty($this->_fieldDefinition['gtf_field_values']) || empty($multi)) {
            $multi = $this->translatedUtil->getYesNo();
        }

        if ($this->_fieldDefinition['gtf_required'] !== 1) {
            $empty = $this->translatedUtil->getEmptyDropdownArray();
        }

        $settings['elementClass'] = 'Radio';
        $settings['separator'] = ' ';
        $settings['multiOptions'] = $empty + array_combine(self::$keyValues, array_slice($multi, 0, 2));
        if ($this->_fieldDefinition['gtf_field_default'] !== null) {
            $settings['default'] = (int)$this->_fieldDefinition['gtf_field_default'];
        }
    }
}
