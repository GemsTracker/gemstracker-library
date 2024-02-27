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

use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModel;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 *
 * @package    Gems
 * @subpackage User\Mask
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Dec 25, 2016 5:44:30 PM
 */
abstract class MaskerAbstract implements MaskerInterface
{
    /**
     *
     * @var string The current choice
     */
    protected string $choice;

    /**
     *
     * @param array $maskFields of [fieldName => class dependent setting]
     */
    public function __construct(
        protected readonly array $maskFields,
        protected readonly TranslatorInterface $translator,
    )
    {
        $this->choice     = $this->getSettingsDefault();
    }

    /**
     *
     * @return string current choice
     */
    public function getChoice(): string
    {
        return $this->choice;
    }

    /**
     *
     * @param string $type Current field data type
     * @param bool $maskOnLoad When true the mask is performed as load transformer instead of a format function
     * @return array of values to set in using model or false when nothing needs to be set
     */
    public function getDataModelMask(string $type, bool $maskOnLoad): array
    {
        $output['elementClass']   = 'Exhibitor';
        $output['readonly']       = 'readonly';
        $output['required']       = false;
        $output['no_text_search'] = true;

        $function = $this->getMaskFunction($type);
        if ($function) {
            // $output['itemDisplay']    = $function;
            if ($maskOnLoad) {
                $output[MetaModel::LOAD_TRANSFORMER] = $function;
            } else {
                $output['formatFunction'] = $function;
            }
        }

        return $output;
    }

    /**
     *
     * @param bool $hideWhollyMasked When true the labels of wholly masked items are removed
     * @param bool $maskOnLoad When true the mask is performed as load transformer instead of a format function
     * @return array of fieldName => settings to set in using model or null when nothing needs to be set
     */
    public function getDataModelOptions(bool $hideWhollyMasked, bool $maskOnLoad): array
    {
        $output = [];
        $types  = [];

        foreach ($this->maskFields as $field => $type) {
            if (! isset($types[$type])) {
                if ($this->isTypeMaskedPartial($type, $this->choice)) {
                    $types[$type] = $this->getDataModelMask($type, $maskOnLoad);
                    if ($hideWhollyMasked && $this->isTypeMaskedWhole($type, $this->choice)) {
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
     * @return array of fieldNames of mask fields
     */
    public function getMaskFields(): array
    {
        return array_keys($this->maskFields);
    }

    /**
     *
     * @param string $type Current field data type
     * @return callable|null Function to perform masking
     */
    abstract public function getMaskFunction(string $type): callable|null;

    /**
     *
     * @return array of values of setting model
     */
    public function getSettingOptions(): array
    {
        return [
            'default'      => $this->getSettingsDefault(),
            'multiOptions' => $this->getSettingsMultiOptions(),
            SqlRunnerInterface::NO_SQL => true,
        ];
    }

    /**
     *
     * @return mixed default value
     */
    abstract public function getSettingsDefault(): mixed;

    /**
     *
     * @return array of multi option values for setting model
     */
    abstract public function getSettingsMultiOptions(): array;

    /**
     *
     * @param string $fieldName
     * @return bool True if this field is invisible
     */
    public function isFieldInvisible(string $fieldName): bool
    {
        if (isset($this->maskFields[$fieldName])) {
            return $this->isTypeInvisible($this->maskFields[$fieldName], $this->choice);
        }

        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return bool True if this field is partially (or wholly) masked (or invisible)
     */
    public function isFieldMaskedPartial(string $fieldName): bool
    {
        if (isset($this->maskFields[$fieldName])) {
            return $this->isTypeMaskedPartial($this->maskFields[$fieldName], $this->choice);
        }

        return false;
    }

    /**
     *
     * @param string $fieldName
     * @return bool True if this field is wholly masked (or invisible)
     */
    public function isFieldMaskedWhole(string $fieldName): bool
    {
        if (isset($this->maskFields[$fieldName])) {
            return $this->isTypeMaskedWhole($this->maskFields[$fieldName], $this->choice);
        }

        return false;
    }

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is partially masked
     */
    abstract public function isTypeInvisible(string $type, string $choice): bool;

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is partially (or wholly) masked (or invisible)
     */
    abstract public function isTypeMaskedPartial(string $type, string $choice): bool;

    /**
     *
     * @param string $type Current field data type
     * @param string $choice
     * @return bool True if this field is masked (or invisible)
     */
    abstract public function isTypeMaskedWhole(string $type, string $choice): bool;

    /**
     *
     * @param string $type Current field data type
     * @param mixed $value
     * @return mixed
     */
    public function mask(string $type, mixed $value): mixed
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
    public function maskRow(array &$row): array
    {
        foreach ($this->maskFields as $field => $type) {
            if (array_key_exists($field, $row) && $this->isTypeMaskedPartial($type, $this->choice)) {
                $row[$field] = $this->mask($type, $row[$field]);
            }
        }
        return $row;
    }

    /**
     *
     * @param string $choice
     * @return self
     */
    public function setChoice(string $choice): self
    {
        $this->choice = $choice;

        return $this;
    }
}
