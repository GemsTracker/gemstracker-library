<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestRoundSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__rounds' => [
                [
                    'gro_id_round' => 40000,
                    'gro_id_track' => 7000,
                    'gro_id_survey' => 500,
                    'gro_survey_name' => 'BMI',
                    'gro_changed_by' => 1,
                    'gro_created_by' => 1,
                ],
            ],
        ];
    }
}