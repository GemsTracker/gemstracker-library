<?php

class TestUsers extends \Phinx\Seed\AbstractSeed
{
    public function getDependencies()
    {
        return [
            'DefaultGroups',
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
                'gul_id_user' => 2001,
                'gul_login' => 'jjansen',
                'gul_id_organization' => 80,
                'gul_user_class' => 'StaffUser',
                'gul_can_login' => 1,
                'gul_two_factor_key' => null,
                'gul_enable_2factor' => 0,
                'gul_otp_count' => 0,
                'gul_otp_requested' => null,
                'gul_changed' => $now->format('Y-m-d H:i:s'),
                'gul_changed_by' => 1,
                'gul_created' => $now->format('Y-m-d H:i:s'),
                'gul_created_by' => 1,
            ],
        ];

        $userLogins = $this->table('gems__user_logins');
        $userLogins->insert($data)
              ->saveData();


        $staffData = [
            [
                'gsf_id_user' => 3001,
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

        $userLogins = $this->table('gems__staff');
        $userLogins->insert($staffData)
            ->saveData();
    }
}
