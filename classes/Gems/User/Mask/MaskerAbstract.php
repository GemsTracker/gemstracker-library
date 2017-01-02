<?php

/**
 *
 * @package    Gems
 * @subpackage User\Mask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Mask;

/**
 *
 * @package    Gems
 * @subpackage User\Mask
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Dec 25, 2016 5:44:30 PM
 */
abstract class MaskerAbstract extends \MUtil_Translate_TranslateableAbstract implements MaskerInterface
{
    /**
     *
     * @var string The current choice
     */
    protected $_choice;

    /**
     *
     * @var array of [fieldname => class dependent setting]
     */
    protected $_maskFields;

    /**
     *
     * @param array $maskFields of [fieldname => class dependent setting]
     */
    public function __construct(array $maskFields)
    {
        $this->_choice     = $this->getSettingsDefault();
        $this->_maskFields = $maskFields;
    }

    /**
     *
     * @return string current choice
     */
    public function getChoice()
    {
        return $this->_choice;
    }

    /**
     *
     * @param string $type Current field data type
     * @return array of values to set in using model or false when nothing needs to be set
     */
    public function getDataModelMask($type)
    {
        $output['elementClass']   = 'Exhibitor';
        $output['readonly']       = 'readonly';
        $output['required']       = false;

        $function = $this->getMaskFunction($type);
        if ($function) {
            // $output['itemDisplay']    = $function;
            $output['formatFunction'] = $function;
        }

        return $output;
    }

    /**
     *
     * @param boolean $hideWhollyMasked When true the labels of wholly masked items are removed
     * @return array of fieldname => settings to set in using model or null when nothing needs to be set
     */
    public function getDataModelOptions($hideWhollyMasked)
    {
        $output = [];
        $types  = [];

        foreach ($this->_maskFields as $field => $type) {
            if (! isset($types[$type])) {
                if ($this->isTypeMaskedPartial($type, $this->_choice)) {
                    $types[$type] = $this->getDataModelMask($type);
                    if ($hideWhollyMasked && $this->isTypeMaskedWhole($type, $this->_choice)) {
                        $types[$type]['label']        = null;
                        $types[$type]['elementClass'] = 'None';
                    }
                } else {
                    $types[$type] = false;
                }
            }
            if ($types[$type]) {
                $output[$field] = $types[$type];
            }
        }

        return $output;
    }

    /**
     *
     * @return array of fieldnames of mask fields
     */
    public function getMaskFields()
    {
        return array_keys($this->_maskFields);
    }

    /**
     *
     * @param string $type Current field data type
     * @return callable Function to perform masking
     */
    abstract public function getMaskFunction($type);

    /**
     *
     * @return array of values of setting model
     */
    public function getSettingOptions()
    {
        return [
            'default'      => $this->getSettingsDefault(),
            'multiOptions' => $this->getSettingsMultiOptions(),
        ];
    }

    /**
     *
     * @return mixed default value
     */
    abstract public function getSettingsDefault();

    /**
     *
     * @return array of multi option values for setting model
     */
    abstract public function getSettingsMultiOptions();

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is invisible
     */
    public function isFieldInvisible($fieldName)
    {
        if (isset($this->_maskFields[$fieldName])) {
            return $this->isTypeInvisible($this->_maskFields[$fieldName], $this->_choice);
        }

        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function isFieldMaskedPartial($fieldName)
    {
        if (isset($this->_maskFields[$fieldName])) {
            return $this->isTypeMaskedPartial($this->_maskFields[$fieldName], $this->_choice);
        }

        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is wholly masked (or invisible)
     */
    public function isFieldMaskedWhole($fieldName)
    {
        if (isset($this->_maskFields[$fieldName])) {
            return $this->isTypeMaskedWhole($this->_maskFields[$fieldName], $this->_choice);
        }

        return false;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is partially masked
     */
    abstract public function isTypeInvisible($type, $choice);

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    abstract public function isTypeMaskedPartial($type, $choice);

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is masked (or invisible)
     */
    abstract public function isTypeMaskedWhole($type, $choice);

    /**
     *
     * @param string $type Current field data type
     * @param mixed $value
     * @return mixed
     */
    public function mask($type, $value)
    {
        $function = $this->getMaskFunction($type);

        if ($function) {
            return $function($value);
        }

        return $value;
    }

    /**
     *
     * @param array $row A row of data to mask
     * @return array A row with all data masked
     */
    public function maskRow(array &$row)
    {
        foreach ($this->_maskFields as $field => $type) {
            if (array_key_exists($field, $row) && $this->isTypeMaskedPartial($type, $this->_choice)) {
                $row[$field] = $this->mask($type, $row[$field]);
            }
        }
    }

    /**
     *
     * @param string $choice
     * @return this
     */
    public function setChoice($choice)
    {
        $this->_choice = $choice;

        return $this;
    }
}
