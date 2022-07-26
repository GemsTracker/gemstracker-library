<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 11-mei-2015 19:01:10
 */
class ConsentField extends FieldAbstract
{
    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings)
    {
        $empty = $this->util->getTranslated()->getEmptyDropdownArray();

        $settings['elementClass'] = 'Select';
        $settings['multiOptions'] = $empty + $this->util->getDbLookup()->getUserConsents();
    }
}
