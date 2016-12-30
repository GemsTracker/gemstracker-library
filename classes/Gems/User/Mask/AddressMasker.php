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
 * @since      Class available since version 1.8.2 Dec 25, 2016 5:54:58 PM
 */
class AddressMasker extends AnyMasker
{
    /**
     *
     * @param string $type Current field data type
     * @return callable Function to perform masking
     */
    public function getMaskFunction($type)
    {
        switch ($type) {
            case 'zip':
                if (($this->_choice == 'ZC') || ($this->_choice == 'ZI')) {
                    return [$this, 'maskZip'];
                }
                break;

            case 'city':
                if (($this->_choice == 'ZC') || ($this->_choice == 'CI')) {
                    // No mask
                    return null;
                }
                break;
            case 'country':
                if ($this->_choice != '*') {
                    // No mask
                    return null;
                }
                break;
        }
        return parent::getMaskFunction($type);
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
            '+' => $this->_('Show'),
            'CI' => $this->_('Show only city and country'),
            'ZC' => $this->_('Show only city, 4-digit of zipcode and country'),
            'ZI' => $this->_('Show only 4-digit of zipcode and country'),
            'CO' => $this->_('Show only country'),
            '*' => $this->_('Mask everything'),
        ];
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
        switch ($choice) {
            case 'CI':
            case 'ZC':
                return ('city' != $type && 'country' != $type);

            case 'ZI':
            case 'CO':
                return 'country' != $type;

            case '*':
                return true;

            default:
                return false;
        }
        return false;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is masked (or invisible)
     */
    public function isTypeMaskedWhole($type, $choice)
    {
        switch ($choice) {
            case 'CI':
                return ('city' != $type && 'country' != $type);

            case 'ZC':
                return ('city' != $type && 'zip' != $type && 'country' != $type);

            case 'ZI':
                return ('zip' != $type && 'country' != $type);

            case 'CO':
                return 'country' != $type;

            case '*':
                return true;

            default:
                return false;
        }
        return false;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is masked
     */
    public function isTypeMasked($type, $choice)
    {
        return '*' == $choice;
    }

    /**
     * Mask the value
     *
     * @param $value The original value
     * @return string
     */
    public function maskZip($value)
    {
        if ($value) {
            return substr($value, 0 , 4) . '**';
        }
    }

}
