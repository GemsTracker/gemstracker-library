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
 * @since      Class available since version 1.8.2 Dec 25, 2016 5:42:50 PM
 */
interface MaskerInterface
{
    /**
     *
     * @return string current choice
     */
    public function getChoice(): string;

    /**
     * @param bool $hideWhollyMasked When true the labels of wholly masked items are removed
     * @param bool $maskOnLoad When true the mask is performed as load transformer instead of a format function
     * @return array of fieldName => settings to set in using model or null when nothing needs to be set
     */
    public function getDataModelOptions(bool $hideWhollyMasked, bool $maskOnLoad): array;

    /**
     *
     * @return array of fieldNames of mask fields
     */
    public function getMaskFields(): array;

    /**
     *
     * @return array of values of setting model
     */
    public function getSettingOptions(): array;

    /**
     *
     * @return mixed default value
     */
    public function getSettingsDefault(): mixed;

    /**
     *
     * @param string $fieldName
     * @return bool True if this field is invisible
     */
    public function isFieldInvisible(string $fieldName): bool;

    /**
     *
     * @param string $fieldName
     * @return bool True if this field is partially (or wholly) masked (or invisible)
     */
    public function isFieldMaskedPartial(string $fieldName): bool;

    /**
     *
     * @param string $fieldName
     * @return bool True if this field is wholly masked (or invisible)
     */
    public function isFieldMaskedWhole(string $fieldName): bool;

    /**
     *
     * @param array $row A row of data to mask
     * @return array
     */
    public function maskRow(array &$row): array;

    /**
     *
     * @param string $choice
     * @return self
     */
    public function setChoice(string $choice): self;
}
