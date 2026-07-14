<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Source
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Source;

use Gems\Tracker\Source\LimeSurvey3m00SharedSource;

/**
 * @package    Gems
 * @subpackage Tracker\Source
 * @since      Class available since version 1.0
 */
class LimeSurvey5m00SharedSource extends LimeSurvey3m00SharedSource
{
    /**
     *
     * @var string class name for creating field maps
     */
    protected string $fieldMapClass = LimeSurvey5m00FieldMap::class;
}