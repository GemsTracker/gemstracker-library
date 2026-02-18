<?php

namespace GemsTest\Function\Repository;

use Gems\Db\Migration\SeedAbstract;

class CommJobRepositoryTestRetractSeed extends SeedAbstract
{
    public function __invoke(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'gems__respondents' => [
                [
                    'grs_id_user' => 102,
                    'grs_ssn' => null,
                    'grs_first_name' => 'Sander',
                    'grs_surname_prefix' => 'van den',
                    'grs_last_name' => 'Berg',
                    'grs_gender' => 'F',
                    'grs_birthday' => '1950-05-23',
                    'grs_address_1' => 'Dorpstraat 1',
                    'grs_zipcode' => '1234 AA',
                    'grs_city' => 'Amsterdam',
                    'grs_changed_by' => 1,
                    'grs_created_by' => 1,
                ],
            ],
            'gems__respondent2org' => [
                [
                    'gr2o_patient_nr' => 'TEST002',
                    'gr2o_id_organization' => 70,
                    'gr2o_id_user' => 102,
                    'gr2o_email' => 'test002@test.test',
                    'gr2o_mailable' => 100,
                    'gr2o_consent' => 'Yes',
                    'gr2o_reception_code' => 'retract',
                    'gr2o_opened_by' => 1,
                    'gr2o_changed_by' => 1,
                    'gr2o_created_by' => 1,
                ],
            ],
            'gems__respondent2track' => [
                [
                    'gr2t_id_respondent_track' => 100005,
                    'gr2t_id_user' => 102,
                    'gr2t_id_organization' => 70,
                    'gr2t_id_track' => 7000,
                    'gr2t_count' => 1,
                    'gr2t_start_date' => $now->format('Y-m-d H:i:s'),
                    'gr2t_changed_by' => 1,
                    'gr2t_created_by' => 1,
                ],
            ],
            'gems__tokens' => [
                [
                    'gto_id_token' => '2345-bcde',
                    'gto_id_respondent_track' => 100005,
                    'gto_id_round' => 40000,
                    'gto_id_respondent' => 102,
                    'gto_id_organization' => 70,
                    'gto_reception_code' => 'OK',
                    'gto_id_track' => 7000,
                    'gto_id_survey' => 500,
                    'gto_round_order' => 10,
                    'gto_changed_by' => 1,
                    'gto_created_by' => 1,
                ],
            ],
        ];
    }
}