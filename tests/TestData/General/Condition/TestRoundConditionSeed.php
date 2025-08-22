<?php

namespace GemsTest\TestData\General\Condition;

use Gems\Db\Migration\SeedAbstract;

class TestRoundConditionSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__rounds' => [
                [
                    'gro_id_round' => 40000,
                    'gro_id_track' => 7000,
                    'gro_id_survey' => 500,
                    'gro_round_description' => 'T0',
                    'gro_survey_name' => 'survey 1',
                    'gro_changed_by' => 1,
                    'gro_created_by' => 1,
                ],
                [
                    'gro_id_round' => 40001,
                    'gro_id_track' => 7000,
                    'gro_id_survey' => 500,
                    'gro_round_description' => 'T3',
                    'gro_survey_name' => 'survey 1',
                    'gro_condition'  => 1000,
                    'gro_changed_by' => 1,
                    'gro_created_by' => 1,
                ],
                [
                    'gro_id_round' => 40002,
                    'gro_id_track' => 7000,
                    'gro_id_survey' => 500,
                    'gro_round_description' => 'T3',
                    'gro_survey_name' => 'survey 1',
                    'gro_condition'  => 1000,
                    'gro_changed_by' => 1,
                    'gro_created_by' => 1,
                ],
            ],
        ];
    }
}