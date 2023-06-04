<?php

namespace Gems\Fake;

class Survey extends \Gems\Tracker\Survey
{
    public function __construct(?array $gemsSurveyData = null)
    {
        if ($gemsSurveyData === null) {
            $gemsSurveyData = $this->getSurveyData();
        }
        parent::__construct($gemsSurveyData);
    }

    public function getSurveyData(): array
    {
        return [
            'gsu_id_survey' => 0,
            'gsu_survey_name' => 'Example survey',
        ];
    }
}