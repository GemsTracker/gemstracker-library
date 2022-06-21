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
    public function run()
    {
        $now = new \DateTimeImmutable();
        $data = [
            [
                'ggp_id_group' => 900,
                'ggp_name' => 'Super Administrators',
                'ggp_description' => 'Super administrators with access to the whole site',
                'ggp_role' => 809,
                'ggp_may_set_groups' => '900,901,902,903',
                'ggp_default_group' => 903,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_staff_members' => 1,
                'ggp_respondent_members' => 0,
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 901,
                'ggp_name' => 'Site Admins',
                'ggp_description' => 'Site Administrators',
                'ggp_role' => 808,
                'ggp_may_set_groups' => '901,902,903',
                'ggp_default_group' => 903,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_staff_members' => 1,
                'ggp_respondent_members' => 0,
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 902,
                'ggp_name' => 'Local Admins',
                'ggp_description' => 'Local Administrators',
                'ggp_role' => 807,
                'ggp_may_set_groups' => '903',
                'ggp_default_group' => 903,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_staff_members' => 1,
                'ggp_respondent_members' => 0,
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 903,
                'ggp_name' => 'Staff',
                'ggp_description' => 'Health care staff',
                'ggp_role' => 804,
                'ggp_may_set_groups' => null,
                'ggp_default_group' => null,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_staff_members' => 1,
                'ggp_respondent_members' => 0,
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 904,
                'ggp_name' => 'Respondents',
                'ggp_description' => 'Respondents',
                'ggp_role' => 802,
                'ggp_may_set_groups' => null,
                'ggp_default_group' => null,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_staff_members' => 0,
                'ggp_respondent_members' => 1,
                'ggp_changed_by' => 0,
                'ggp_created' => $now->format('Y-m-d H:i:s'),
                'ggp_created_by' => 0
            ],
            [
                'ggp_id_group' => 905,
                'ggp_name' => 'Security',
                'ggp_description' => 'Security',
                'ggp_role' => 803,
                'ggp_may_set_groups' => null,
                'ggp_default_group' => null,
                'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                'ggp_group_active' => 1,
                'ggp_staff_members' => 0,
                'ggp_respondent_members' => 1,
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
