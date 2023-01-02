<?php


use Phinx\Seed\AbstractSeed;

class DefaultGroups extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $now = new \DateTimeImmutable();
        $data = [
            [
                'ggp_id_group' => 900,
                'ggp_name' => 'Super Administrators',
                'ggp_code' => 'superadmin',
                'ggp_description' => 'Super administrators with access to the whole site',
                'ggp_role' => 'super',
                'ggp_may_set_groups' => '900,901,902,903',
                'ggp_default_group' => 903,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_member_type' => 'staff',
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 901,
                'ggp_name' => 'Site Admins',
                'ggp_code' => 'siteadmin',
                'ggp_description' => 'Site Administrators',
                'ggp_role' => 'siteadmin',
                'ggp_may_set_groups' => '901,902,903',
                'ggp_default_group' => 903,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_member_type' => 'staff',
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 902,
                'ggp_name' => 'Local Admins',
                'ggp_code' => 'localadmin',
                'ggp_description' => 'Local Administrators',
                'ggp_role' => 'admin',
                'ggp_may_set_groups' => '903',
                'ggp_default_group' => 903,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_member_type' => 'staff',
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 903,
                'ggp_name' => 'Staff',
                'ggp_code' => 'staff',
                'ggp_description' => 'Health care staff',
                'ggp_role' => 'staff',
                'ggp_may_set_groups' => null,
                'ggp_default_group' => null,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_member_type' => 'staff',
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 904,
                'ggp_name' => 'Respondents',
                'ggp_code' => 'respondents',
                'ggp_description' => 'Respondents',
                'ggp_role' => 'respondent',
                'ggp_may_set_groups' => null,
                'ggp_default_group' => null,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_member_type' => 'respondent',
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 905,
                'ggp_name' => 'Security',
                'ggp_code' => 'security',
                'ggp_description' => 'Security',
                'ggp_role' => 'security',
                'ggp_may_set_groups' => null,
                'ggp_default_group' => null,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_member_type' => 'respondent',
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
        ];

        $groups = $this->table('gems__groups');
        $groups->insert($data)
              ->saveData();
    }
}
