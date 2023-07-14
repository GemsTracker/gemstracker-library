<?php

namespace Gems\Fake;

use Gems\Db\ResultFetcher;
use Gems\Tracker;
use Gems\Tracker\TrackEvents;

class Survey extends \Gems\Tracker\Survey
{
    public function __construct(
        array|null $data = null,
        Tracker $tracker,
        ResultFetcher $resultFetcher,
        TrackEvents $trackEvents
    ) {
        if ($data === null) {
            $data = $this->getSurveyData();
        }
        parent::__construct(
            $data,
            $tracker,
            $resultFetcher,
            $trackEvents,
        );
    }

    public function getSurveyData(): array
    {
        return [
            'gsu_id_survey' => 0,
            'gsu_survey_name' => 'Example survey',
        ];
    }
}
