<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestCommMessengersSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__comm_messengers' => [
                [
                    "gcm_id_messenger" => 1300,
                    "gcm_id_order" => 10,
                    "gcm_type" => "mail",
                    "gcm_name" => "E-mail",
                    "gcm_description" => "Send by E-mail",
                    "gcm_messenger_identifier" => null,
                    "gcm_active" => 1,
                    "gcm_changed_by" => 1,
                    "gcm_created_by" => 1,
                ],
            ],
        ];
    }
}