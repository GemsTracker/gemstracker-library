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
     * @param array $maskFields of [fieldname => class dependent setting]
     */
    public function __construct(array $maskFields);

    /**
     *
     * @return string current choice
     */
    public function getChoice();

    /**
     *
     * @param boolean $hideWhollyMasked When true the labels of wholly masked items are removed
     * @return array of fieldname => settings to set in using model or null when nothing needs to be set
     */
    public function getDataModelOptions($hideWhollyMasked);

    /**
     *
     * @return array of fieldnames of mask fields
     */
    public function getMaskFields();

    /**
     *
     * @return array of values of setting model
     */
    public function getSettingOptions();

    /**
     *
     * @return mixed default value
     */
    public function getSettingsDefault();

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is invisible
     */
    public function isFieldInvisible($fieldName);

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is partially (or wholly) masked (or invisible)
     */
    public function isFieldMaskedPartial($fieldName);

    /**
     *
     * @param string $fieldName
     * @return boolean True if this field is wholly masked (or invisible)
     */
    public function isFieldMaskedWhole($fieldName);

    /**
     *
     * @param array $row A row of data to mask
     * @return void
     */
    public function maskRow(array &$row);

    /**
     *
     * @param string $choice
     * @return $this
     */
    public function setChoice($choice);
}
