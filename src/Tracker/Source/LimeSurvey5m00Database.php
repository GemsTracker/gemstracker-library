<?php


namespace Gems\Tracker\Source;

class LimeSurvey5m00Database extends \Gems\Tracker\Source\LimeSurvey3m00Database
{

    /**
     *
     * @var string class name for creating field maps
     */
    protected $fieldMapClass = LimeSurvey5m00FieldMap::class;
}