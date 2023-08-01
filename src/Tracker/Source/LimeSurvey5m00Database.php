<?php


namespace Gems\Tracker\Source;

class LimeSurvey5m00Database extends LimeSurvey3m00Database
{

    /**
     *
     * @var string class name for creating field maps
     */
    protected string $fieldMapClass = LimeSurvey5m00FieldMap::class;
}
