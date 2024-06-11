<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestGroupsSeed extends SeedAbstract
{
    public function __invoke(): array
    {
        return [
            'gems__groups' => [
                [
                    'ggp_id_group' => 900,
                    'ggp_code' => 'super_administrators',
                    'ggp_name' => 'Super Administrators',
                    'ggp_description' => 'Super administrators with access to the whole site',
                    'ggp_role' => 'super',
                    'ggp_may_set_groups' => '900,901,902,903',
                    'ggp_default_group' => 903,
                    'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                    'ggp_group_active' => 1,
                    'ggp_staff_members' => 1,
                    'ggp_member_type' => 'staff',
                    'ggp_respondent_members' => 0,
                    'ggp_changed_by' => 1,
                    'ggp_created_by' => 1,
                ],
                [
                    'ggp_id_group' => 903,
                    'ggp_code' => 'staff',
                    'ggp_name' => 'Staff',
                    'ggp_description' => 'Health care staff',
                    'ggp_role' => 'staff',
                    'ggp_may_set_groups' => null,
                    'ggp_default_group' => null,
                    'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                    'ggp_group_active' => 1,
                    'ggp_staff_members' => 1,
                    'ggp_respondent_members' => 0,
                    'ggp_member_type' => 'staff',
                    'ggp_changed_by' => 1,
                    'ggp_created_by' => 1,
                ],
                [
                    'ggp_id_group' => 904,
                    'ggp_code' => 'respondents',
                    'ggp_name' => 'Respondents',
                    'ggp_description' => 'Respondents',
                    'ggp_role' => 'respondent',
                    'ggp_may_set_groups' => null,
                    'ggp_default_group' => null,
                    'ggp_no_2factor_ip_ranges' => '127.0.0.1',
                    'ggp_group_active' => 1,
                    'ggp_staff_members' => 1,
                    'ggp_respondent_members' => 0,
                    'ggp_member_type' => 'respondent',
                    'ggp_changed_by' => 1,
                    'ggp_created_by' => 1,
                ],
            ],
        ];
    }
}