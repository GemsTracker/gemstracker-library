<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestRolesSeed extends SeedAbstract
{
    public function __invoke(): array
    {
        return [
            'gems__roles' => [
                [
                    'grl_id_role' => 802,
                    'grl_name' => 'respondent',
                    'grl_description' => 'respondent',
                    'grl_parents' => null,
                    'grl_privileges' => null,
                    'grl_changed_by' => 1,
                    'grl_created_by' => 1,
                ],
                [
                    'grl_id_role' => 804,
                    'grl_name' => 'staff',
                    'grl_description' => 'staff',
                    'grl_parents' => null,
                    'grl_privileges' => null,
                    'grl_changed_by' => 1,
                    'grl_created_by' => 1,
                ],
                [
                    'grl_id_role' => 809,
                    'grl_name' => 'super',
                    'grl_description' => 'super',
                    'grl_parents' => '804',
                    'grl_privileges' => null,
                    'grl_changed_by' => 1,
                    'grl_created_by' => 1,
                ],
            ],
        ];
    }
}