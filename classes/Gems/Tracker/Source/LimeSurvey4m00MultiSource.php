<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Source
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Source;

/**
 * @package    Gems
 * @subpackage Tracker\Source
 * @since      Class available since version 1.0
 */
class LimeSurvey4m00MultiSource extends LimeSurvey3m00MultiSource
{
    /**
     *
     * @var string class name for creating field maps
     */
    protected $fieldMapClass = 'Gems\\Tracker\\Source\\LimeSurvey4m00FieldMap';
}