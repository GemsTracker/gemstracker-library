<?php

namespace Gems\Tracker\Field;

class BooleanField extends FieldAbstract
{
    public static array $keyValues = [
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
    protected function addModelSettings(array &$settings): void
    {
        $empty = [];
        $multi = $this->fieldDefinition['gtf_field_values'] ? explode(parent::FIELD_SEP, $this->fieldDefinition['gtf_field_values']) : [];
        if (empty($this->fieldDefinition['gtf_field_values']) || empty($multi)) {
            $multi = $this->translatedUtil->getYesNo();
        }

        if ($this->fieldDefinition['gtf_required'] !== 1) {
            $empty = $this->translatedUtil->getEmptyDropdownArray();
        }

        $settings['elementClass'] = 'Radio';
        $settings['separator'] = ' ';
        $settings['multiOptions'] = $empty + array_combine(self::$keyValues, array_slice($multi, 0, 2));
        if ($this->fieldDefinition['gtf_field_default'] !== null) {
            $settings['default'] = (int)$this->fieldDefinition['gtf_field_default'];
        }
    }
}
