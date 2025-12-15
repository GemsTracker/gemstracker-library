<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestTokenSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__tokens' => [
                [
                    'gto_id_token' => '1234-abcd',
                    'gto_id_respondent_track' => 100000,
                    'gto_id_round' => 40000,
                    'gto_id_respondent' => 101,
                    'gto_id_organization' => 70,
                    'gto_reception_code' => 'OK',
                    'gto_id_track' => 7000,
                    'gto_id_survey' => 500,
                    'gto_round_order' => 10,
                    'gto_changed_by' => 1,
                    'gto_created_by' => 1,
                ],
                [
                    'gto_id_token' => '4321-dcba',
                    'gto_id_respondent_track' => 100000,
                    'gto_id_round' => 40001,
                    'gto_id_respondent' => 101,
                    'gto_id_organization' => 70,
                    'gto_reception_code' => 'OK',
                    'gto_id_track' => 7000,
                    'gto_id_survey' => 500,
                    'gto_round_order' => 20,
                    'gto_changed_by' => 1,
                    'gto_created_by' => 1,
                ],
            ],
        ];
    }
}