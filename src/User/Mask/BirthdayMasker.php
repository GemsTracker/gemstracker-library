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

use DateTimeImmutable;
use DateTimeInterface;

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
    protected array $possibleStorageFormats = [
        'Y-m-d',
        'Y-m-d H:i:s',
    ];

    /**
     *
     * @param string $type Current field data type
     * @return array of values to set in using model or false when nothing needs to be set
     */
    public function getDataModelMask(string $type, bool $maskOnLoad): array
    {
        $output = parent::getDataModelMask($type, $maskOnLoad);

        $output['dateFormat'] = $this->getDateMask($this->choice);

        return $output;
    }

    /**
     *
     * @param string $choice Current choice
     * @return string|null Function to perform masking
     */
    public function getDateMask(string $choice): string|null
    {
        switch ($choice) {
            case 'D':
                return 'm Y';

            case 'M':
                return 'Y';

            case 'Y':
                return 'd m';

            case '*':
                return '**-**-****';
        }
        return null;
    }

    /**
     *
     * @param string $type Current field data type
     * @return callable|null Function to perform masking
     */
    public function getMaskFunction(string $type): callable|null
    {
        if ('hide' == $type) {
            return function () {
                return null;
            };
        }
        $dateMask = $this->getDateMask($this->choice);

        return function ($value) use ($dateMask) {
            if ($value instanceof DateTimeInterface) {
                return $value->format($dateMask);
            }
            if ($value === null) {
                return null;
            }
            foreach($this->possibleStorageFormats as $storageFormat) {
                $dateTimeValue = DateTimeImmutable::createFromFormat($storageFormat, $value);
                if ($dateTimeValue) {
                    return $dateTimeValue->format($dateMask);
                }
            }
            return $value;
        };
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
            '+' => $this->translator->_('Show completely'),
            'D' => $this->translator->_('Mask day of month'),
            'M' => $this->translator->_('Mask day and month'),
            'Y' => $this->translator->_('Mask year only'),
            '*' => $this->translator->_('Mask completely'),
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
        return '+' != $choice;
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
}
