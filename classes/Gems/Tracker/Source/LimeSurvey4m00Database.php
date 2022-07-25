<?php


namespace Gems\Tracker\Source;

class LimeSurvey4m00Database extends \Gems\Tracker\Source\LimeSurvey3m00Database
{

    /**
     *
     * @var string class name for creating field maps
     */
    protected $fieldMapClass = 'Gems\\Tracker\\Source\\LimeSurvey4m00FieldMap';
}
