<?php

namespace Gems\Config;

class Role
{
    public function __invoke(): array
    {
        return [
            'definition_date' => '2022-11-23 00:00:00',
            'roles' => [
                'staff' => ['grl_name' => 'staff', 'grl_description' => 'staff', 'grl_parents' => [], 'grl_privileges' => []],
                'super' => ['grl_name' => 'super', 'grl_description' => 'super', 'grl_parents' => [], 'grl_privileges' => []],
            ]
        ];
    }
}
