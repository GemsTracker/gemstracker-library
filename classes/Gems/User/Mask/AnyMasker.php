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
 * @since      Class available since version 1.8.2 Dec 25, 2016 5:53:13 PM
 */
class AnyMasker extends MaskerAbstract
{
    /**
     *
     * @param string $type Current field data type
     * @return callable Function to perform masking
     */
    public function getMaskFunction($type)
    {
        switch ($type) {
            case 'mask':
                return [$this, 'maskValue'];

            case 'hide':
                return [$this, 'hideValue'];

            case 'short':
                return [$this, 'shortMaskValue'];

            default:
                return [$this, 'maskValue'];
        }
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
            '*' => $this->_('Mask'),
        ];
    }

    /**
     * Mask the value
     *
     * @return string
     */
    public function hideValue()
    {
        return null;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is partially masked
     */
    public function isTypeInvisible($type, $choice)
    {
        return ('*' == $choice) && ('hide' == $type);
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function isTypeMaskedPartial($type, $choice)
    {
        return '*' == $choice;
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
     * @return string
     */
    public function maskValue()
    {
        return '******';
    }

    /**
     * Mask the value with a short mask
     *
     * @return string
     */
    public function shortMaskValue()
    {
        return '**';
    }
}
