<?php

namespace Gems\Fake;

use Gems\Db\ResultFetcher;
use Gems\Tracker;
use Gems\Tracker\TrackEvents;

class Survey extends \Gems\Tracker\Survey
{
    public function __construct(
        Tracker $tracker,
        ResultFetcher $resultFetcher,
        TrackEvents $trackEvents,
        array|null $data = null,
    ) {
        if ($data === null) {
            $data = $this->getSurveyData();
        }
        parent::__construct(
            $tracker,
            $resultFetcher,
            $trackEvents,
            $data,
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
