<?php

namespace GemsTest\TestData\General\Condition;

use Cassandra\Date;
use Gems\Db\Migration\SeedAbstract;

class TestTokenConditionSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'gems__tokens' => [
                [
                    'gto_id_token' => '1234-comp',
                    'gto_id_respondent_track' => 100000,
                    'gto_id_round' => 40000,
                    'gto_id_respondent' => 101,
                    'gto_id_organization' => 70,
                    'gto_reception_code' => 'OK',
                    'gto_id_track' => 7000,
                    'gto_id_survey' => 500,
                    'gto_completion_time' => $now->format('Y-m-d H:i:s'),
                    'gto_changed_by' => 1,
                    'gto_created_by' => 1,
                ],
                [
                    'gto_id_token' => '1234-skip',
                    'gto_id_respondent_track' => 100000,
                    'gto_id_round' => 40001,
                    'gto_id_respondent' => 101,
                    'gto_id_organization' => 70,
                    'gto_reception_code' => 'OK',
                    'gto_id_track' => 7000,
                    'gto_id_survey' => 500,
                    'gto_valid_from' => $now->add(new \DateInterval('P12D'))->format('Y-m-d H:i:s'),
                    'gto_changed_by' => 1,
                    'gto_created_by' => 1,
                ],
                [
                    'gto_id_token' => '1234-cont',
                    'gto_id_respondent_track' => 100000,
                    'gto_id_round' => 40001,
                    'gto_id_respondent' => 101,
                    'gto_id_organization' => 70,
                    'gto_reception_code' => 'OK',
                    'gto_id_track' => 7000,
                    'gto_id_survey' => 500,
                    'gto_valid_from' => $now->add(new \DateInterval('P22D'))->format('Y-m-d H:i:s'),
                    'gto_changed_by' => 1,
                    'gto_created_by' => 1,
                ],
            ],
        ];
    }
}