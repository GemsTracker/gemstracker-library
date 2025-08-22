<?php

namespace GemsTest\TestData\General\Condition;

use Gems\Db\Migration\SeedAbstract;

class TestSurveyConditionSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__surveys' => [
                [
                    'gsu_id_survey' => 500,
                    'gsu_survey_name' => 'survey 1',
                    'gsu_id_primary_group' => 904,
                    'gsu_id_source' => 20,
                    'gsu_surveyor_id' => 12345678,
                    'gsu_code' => null,
                    'gsu_active' => 1,
                    'gsu_changed_by' => 1,
                    'gsu_created_by' => 1,
                ],
                [
                    'gsu_id_survey' => 501,
                    'gsu_survey_name' => 'survey 2',
                    'gsu_id_primary_group' => 904,
                    'gsu_id_source' => 20,
                    'gsu_surveyor_id' => 12345679,
                    'gsu_code' => 'code',
                    'gsu_active' => 1,
                    'gsu_changed_by' => 1,
                    'gsu_created_by' => 1,
                ],
                [
                    'gsu_id_survey' => 502,
                    'gsu_survey_name' => 'survey 3',
                    'gsu_id_primary_group' => 904,
                    'gsu_id_source' => 20,
                    'gsu_surveyor_id' => 12345680,
                    'gsu_code' => 'code',
                    'gsu_active' => 1,
                    'gsu_changed_by' => 1,
                    'gsu_created_by' => 1,
                ],
                [
                    'gsu_id_survey' => 503,
                    'gsu_survey_name' => 'survey 4',
                    'gsu_id_primary_group' => 904,
                    'gsu_id_source' => 20,
                    'gsu_surveyor_id' => 12345681,
                    'gsu_code' => null,
                    'gsu_active' => 1,
                    'gsu_changed_by' => 1,
                    'gsu_created_by' => 1,
                ],
            ],
        ];
    }
}