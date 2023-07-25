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

use Gems\Tracker;
use MUtil\Model;
use MUtil\Model\Bridge\FormBridge;

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
    protected int $type = Model::TYPE_DATETIME;

    /**
     * The format string for outputting dates
     *
     * @var string
     */
    protected string $phpDateTimeFormat = 'j M Y H:i:s';

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getDateFormat(): string
    {
        return Model::getTypeDefault(Model::TYPE_DATETIME, 'dateFormat');
    }

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getStorageFormat(): string
    {
        return Tracker::DB_DATETIME_FORMAT;
    }
}
