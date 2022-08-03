<?php

class TestUsers extends \Phinx\Seed\AbstractSeed
{
    public function getDependencies()
    {
        return [
            'DefaultOrganizations',
        ];
    }

    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run()
    {
        $now = new \DateTimeImmutable();
        $data = [
            [
                'grs_id_user' => 101,
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
        ];

        $respondents = $this->table('gems__respondents');
        $respondents->insert($data)
              ->saveData();


        $respondentOrgData = [
            [
                'gr2o_patient_nr' => 'TEST001',
                'gsf_login' => 'jjansen',
                'gsf_id_organization' => 70,
                'gsf_active' => 1,
                'gsf_id_primary_group' => 903,
                'gsf_iso_lang' => 'nl',
                'gsf_logout_on_survey' => 0,
                'gsf_mail_watcher' => 0,
                'gsf_email' => 'jjansen@test.test',
                'gsf_first_name' => 'Janneke',
                'gsf_surname_prefix' => null,
                'gsf_last_name' => 'Jansen',
                'gsf_gender' => 'F',
                'gsf_job_title' => 'Arts',
                'gsf_phone_1' => null,
                'gsf_is_embedded' => 0,
                'gsf_changed' => $now->format('Y-m-d H:i:s'),
                'gsf_changed_by' => 1,
                'gsf_created' => $now->format('Y-m-d H:i:s'),
                'gsf_created_by' => 1,
            ]
        ];

        $respondent2org = $this->table('gems__respondent2org');
        $respondent2org->insert($respondentOrgData)
            ->saveData();
    }
}
