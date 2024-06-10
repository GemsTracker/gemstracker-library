<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestOrganizationSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__organizations' => [
                [
                    'gor_id_organization' => 70,
                    'gor_name' => 'Test organization',
                    'gor_contact_email' => 'info@test-organization.test',
                    'gor_changed_by' => 1,
                    'gor_created_by' => 1,
                ],
                [
                    'gor_id_organization' => 71,
                    'gor_name' => 'Other organization',
                    'gor_contact_email' => 'info@other-organization.test',
                    'gor_changed_by' => 1,
                    'gor_created_by' => 1,
                ]
            ],
        ];
    }
}