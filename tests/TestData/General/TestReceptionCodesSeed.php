<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestReceptionCodesSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__reception_codes' => [
                [
                    'grc_id_reception_code' => 'OK',
                    'grc_description' => '',
                    'grc_success' => 1,
                    'grc_for_surveys' => 1,
                    'grc_redo_survey' => 0,
                    'grc_for_tracks' => 1,
                    'grc_for_respondents' => 1,
                    'grc_overwrite_answers' => 0,
                    'grc_active' => 1,
                    'grc_changed_by' => 1,
                    'grc_created_by' => 1,
                ],
                [
                    'grc_id_reception_code' => 'redo',
                    'grc_description' => 'Redo survey',
                    'grc_success' => 0,
                    'grc_for_surveys' => 1,
                    'grc_redo_survey' => 2,
                    'grc_for_tracks' => 0,
                    'grc_for_respondents' => 0,
                    'grc_overwrite_answers' => 1,
                    'grc_active' => 1,
                    'grc_changed_by' => 1,
                    'grc_created_by' => 1,
                ],
                [
                    'grc_id_reception_code' => 'refused',
                    'grc_description' => 'Survey refused',
                    'grc_success' => 0,
                    'grc_for_surveys' => 1,
                    'grc_redo_survey' => 0,
                    'grc_for_tracks' => 0,
                    'grc_for_respondents' => 0,
                    'grc_overwrite_answers' => 0,
                    'grc_active' => 1,
                    'grc_changed_by' => 1,
                    'grc_created_by' => 1,
                ],
                [
                    'grc_id_reception_code' => 'retract',
                    'grc_description' => 'Consent retracted',
                    'grc_success' => 0,
                    'grc_for_surveys' => 0,
                    'grc_redo_survey' => 0,
                    'grc_for_tracks' => 1,
                    'grc_for_respondents' => 1,
                    'grc_overwrite_answers' => 1,
                    'grc_active' => 1,
                    'grc_changed_by' => 1,
                    'grc_created_by' => 1,
                ],
                [
                    'grc_id_reception_code' => 'skip',
                    'grc_description' => 'Skipped by calculation',
                    'grc_success' => 0,
                    'grc_for_surveys' => 1,
                    'grc_redo_survey' => 0,
                    'grc_for_tracks' => 0,
                    'grc_for_respondents' => 0,
                    'grc_overwrite_answers' => 1,
                    'grc_active' => 0,
                    'grc_changed_by' => 1,
                    'grc_created_by' => 1,
                ],
                [
                    'grc_id_reception_code' => 'stop',
                    'grc_description' => 'Stopped participating',
                    'grc_success' => 0,
                    'grc_for_surveys' => 2,
                    'grc_redo_survey' => 0,
                    'grc_for_tracks' => 1,
                    'grc_for_respondents' => 1,
                    'grc_overwrite_answers' => 0,
                    'grc_active' => 1,
                    'grc_changed_by' => 1,
                    'grc_created_by' => 1,
                ],
                [
                    'grc_id_reception_code' => 'moved',
                    'grc_description' => 'Moved to new survey',
                    'grc_success' => 0,
                    'grc_for_surveys' => 1,
                    'grc_redo_survey' => 0,
                    'grc_for_tracks' => 0,
                    'grc_for_respondents' => 0,
                    'grc_overwrite_answers' => 1,
                    'grc_active' => 0,
                    'grc_changed_by' => 1,
                    'grc_created_by' => 1,
                ],
            ],
        ];
    }
}