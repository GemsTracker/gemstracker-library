<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestCommJobSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__comm_jobs' => [
                [
                    'gcj_id_job' => 800,
                    'gcj_id_order' => 10,
                    'gcj_id_communication_messenger' => 1300,
                    'gcj_id_message' => 20,
                    'gcj_id_user_as' => 1,
                    'gcj_from_method' => 'O',
                    'gcj_process_method' => 'O',
                    'gcj_filter_mode' => 'N',
                    'gcj_changed_by' => 1,
                    'gcj_created_by' => 1,
                ],
            ],
        ];
    }
}