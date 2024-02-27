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
     * @return callable|null Function to perform masking
     */
    public function getMaskFunction(string $type): callable|null
    {
        switch ($type) {
            case 'zip':
                if (($this->choice == 'ZC') || ($this->choice == 'ZI')) {
                    return [$this, 'maskZip'];
                }
                break;

            case 'city':
                if (($this->choice == 'ZC') || ($this->choice == 'CI')) {
                    // No mask
                    return null;
                }
                break;
            case 'country':
                if ($this->choice != '*') {
                    // No mask
                    return null;
                }
                break;
        }
        return parent::getMaskFunction($type);
    }

    /**
     *
     * @return string default value
     */
    public function getSettingsDefault(): string
    {
        return '+';
    }

    /**
     *
     * @return array of multi option values for setting model
     */
    public function getSettingsMultiOptions(): array
    {
        return [
            '+' => $this->translator->_('Show'),
            'CI' => $this->translator->_('Show only city and country'),
            'ZC' => $this->translator->_('Show only city, 4-digit of zipcode and country'),
            'ZI' => $this->translator->_('Show only 4-digit of zipcode and country'),
            'CO' => $this->translator->_('Show only country'),
            '*' => $this->translator->_('Mask everything'),
        ];
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is partially masked
     */
    public function isTypeInvisible(string $type, string $choice): bool
    {
        return ('+' != $choice) && ('hide' == $type);
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is partially (or wholly) masked (or invisible)
     */
    public function isTypeMaskedPartial(string $type, string $choice): bool
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
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is masked (or invisible)
     */
    public function isTypeMaskedWhole(string $type, string $choice): bool
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
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is masked
     */
    public function isTypeMasked(string $type, string $choice): bool
    {
        return '*' == $choice;
    }

    /**
     * Mask the value
     *
     * @param string $value The original value
     * @return string|null
     */
    public function maskZip(string $value): string|null
    {
        if ($value) {
            return substr($value, 0 , 4) . '**';
        }
        return null;
    }

}
