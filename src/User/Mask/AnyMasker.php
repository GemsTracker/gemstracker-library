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
     * @return callable|null Function to perform masking
     */
    public function getMaskFunction(string $type): callable|null
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
            '*' => $this->translator->_('Mask'),
        ];
    }

    /**
     * Mask the value
     *
     * @return null
     */
    public function hideValue()
    {
        return null;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is partially masked
     */
    public function isTypeInvisible(string $type, string $choice): bool
    {
        return ('*' == $choice) && ('hide' == $type);
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is partially (or wholly) masked (or invisible)
     */
    public function isTypeMaskedPartial(string $type, string $choice): bool
    {
        return '*' == $choice;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is masked (or invisible)
     */
    public function isTypeMaskedWhole(string $type, string $choice): bool
    {
        return '*' == $choice;
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
     * @return string
     */
    public function maskValue(): string
    {
        return '******';
    }

    /**
     * Mask the value with a short mask
     *
     * @return string
     */
    public function shortMaskValue(): string
    {
        return '**';
    }
}
