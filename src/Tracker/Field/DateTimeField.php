<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:43:37
 */
class DateTimeField extends DateField
{
    /**
     * The model type
     *
     * @var int
     */
    protected $type = \MUtil\Model::TYPE_DATETIME;

    /**
     * The format string for outputting dates
     *
     * @var string
     */
    protected $phpDateTimeFormat = 'j M Y H:i:s';

    /**
     * The format string for outputting dates
     *
     * @var string
     */
    protected $zendDateTimeFormat = 'dd MMM yyyy HH:mm:ss';

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return \MUtil\Model\Bridge\FormBridge::getFixedOption('datetime', 'dateFormat');
    }

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getStorageFormat()
    {
        return \Gems\Tracker::DB_DATETIME_FORMAT;
    }
}
