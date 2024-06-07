<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestRespondentSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__respondents' => [
                [
                    'grs_id_user' => 101,
                    'grs_ssn' => 111222333,
                    'grs_first_name' => 'Fleur',
                    'grs_surname_prefix' => 'van den',
                    'grs_last_name' => 'Berg',
                    'grs_gender' => 'F',
                    'grs_birthday' => '2001-05-23',
                    'grs_address_1' => 'Dorpstraat 1',
                    'grs_zipcode' => '1234 AA',
                    'grs_city' => 'Amsterdam',
                    'grs_phone_1' => '+31 6 12345678',
                    'grs_changed_by' => 1,
                    'grs_created_by' => 1,
                ],
            ],
            'gems__respondent2org' => [
                [
                    'gr2o_patient_nr' => 'TEST001',
                    'gr2o_id_organization' => 70,
                    'gr2o_id_user' => 101,
                    'gr2o_email' => 'test@mail.com',
                    'gr2o_mailable' => 100,
                    'gr2o_consent' => 'Yes',
                    'gr2o_reception_code' => 'OK',
                    'gr2o_opened_by' => 1,
                    'gr2o_changed_by' => 1,
                    'gr2o_created_by' => 1,
                ],
            ],
        ];
    }
}