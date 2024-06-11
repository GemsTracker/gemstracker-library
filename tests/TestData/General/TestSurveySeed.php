<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestSurveySeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__surveys' => [
                [
                    'gsu_id_survey' => 500,
                    'gsu_survey_name' => 'BMI',
                    'gsu_id_primary_group' => 904,
                    'gsu_id_source' => 20,
                    'gsu_active' => 1,
                    'gsu_changed_by' => 1,
                    'gsu_created_by' => 1,
                ],
            ],
        ];
    }
}