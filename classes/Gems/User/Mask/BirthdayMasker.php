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
 * @since      Class available since version 1.8.2 Dec 25, 2016 5:56:06 PM
 */
class BirthdayMasker extends MaskerAbstract
{
    /**
     *
     * @param string $type Current field data type
     * @return array of values to set in using model or false when nothing needs to be set
     */
    public function getDataModelMask($type)
    {
        $output = parent::getDataModelMask($type);

        $output['dateFormat'] = $this->getDateMask($this->_choice);

        return $output;
    }

    /**
     *
     * @param string $choice Current choice
     * @return callable Function to perform masking
     */
    public function getDateMask($choice)
    {
        switch ($choice) {
            case 'D':
                return 'MMM YYYY';

            case 'M':
                return 'YYYY';

            case 'Y':
                return 'dd MMM';

            case '*':
                return '**-**-****';
        }
    }

    /**
     *
     * @param string $type Current field data type
     * @return callable Function to perform masking
     */
    public function getMaskFunction($type)
    {
        if ('hide' == $type) {
            return function () {
                return null;
            };
        }
        $dateMask = $this->getDateMask($this->_choice);

        return function ($value) use ($dateMask) {
            if ($value instanceof \Zend_Date) {
                return $value->toString($dateMask);
            }
        };
    }

    /**
     *
     * @return mixed default value
     */
    public function getSettingsDefault()
    {
        return '+';
    }

    /**
     *
     * @return array of multi option values for setting model
     */
    public function getSettingsMultiOptions()
    {
        return [
            '+' => $this->_('Show completely'),
            'D' => $this->_('Mask day of month'),
            'M' => $this->_('Mask day and month'),
            'Y' => $this->_('Mask year only'),
            '*' => $this->_('Mask completely'),
        ];
    }

    /**
     *
     * @param string $type Current field data type
     * @param mixed $value
     * @return mixed
     */
    public function mask($type, $value)
    {
        if ($value instanceof \Zend_Date) {
            $dateValue = $value;
        } else {
            $dateValue = \MUtil_Date::ifDate($value, array_keys(\MUtil_Date::$zendToPhpFormats));
        }
        if ($dateValue) {
            switch ($this->_choice) {
                case 'D':
                    return $dateValue->toString('MMM YYYY');

                case 'M':
                    return $dateValue->toString('YYYY');

                case 'Y':
                    return $dateValue->toString('DD MMM');

                case '*':
                    return null;
            }

        }

        return $value;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is partially masked
     */
    public function isTypeInvisible($type, $choice)
    {
        return ('+' != $choice) && ('hide' == $type);
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function isTypeMaskedPartial($type, $choice)
    {
        return '+' != $choice;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is masked (or invisible)
     */
    public function isTypeMaskedWhole($type, $choice)
    {
        return '*' == $choice;
    }
}
