<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestConsentsSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'gems__consents' => [
                [
                    'gco_description' => 'Yes',
                    'gco_order' => 10,
                    'gco_code' => 'consent given',
                    'gco_changed_by' => 1,
                    'gco_created_by' => 1,
                ],
                [
                    'gco_description' => 'No',
                    'gco_order' => 20,
                    'gco_code' => 'do not use',
                    'gco_changed_by' => 1,
                    'gco_created_by' => 1,
                ],
                [
                    'gco_description' => 'Unknown',
                    'gco_order' => 30,
                    'gco_code' => 'do not use',
                    'gco_changed_by' => 1,
                    'gco_created_by' => 1,
                ],
            ],
        ];
    }
}