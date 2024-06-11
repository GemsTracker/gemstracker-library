<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestMailCodesSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            "gems__mail_codes" => [
                [
                    "gmc_id" => 0,
                    "gmc_mail_to_target" => 'No',
                    "gmc_mail_cause_target" => 'Never mail',
                    "gmc_for_surveys" => 0,
                    "gmc_changed_by" => 1,
                    "gmc_created_by" => 1,
                ],
                [
                    "gmc_id" => 100,
                    "gmc_mail_to_target" => 'Yes',
                    "gmc_mail_cause_target" => 'Mail',
                    "gmc_for_surveys" => 1,
                    "gmc_changed_by" => 1,
                    "gmc_created_by" => 1,
                ],
            ],
        ];
    }
}