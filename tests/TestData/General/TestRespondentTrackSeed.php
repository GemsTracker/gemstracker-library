<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestRespondentTrackSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'gems__respondent2track' => [
                [
                    'gr2t_id_respondent_track' => 100000,
                    'gr2t_id_user' => 101,
                    'gr2t_id_organization' => 70,
                    'gr2t_id_track' => 7000,
                    'gr2t_count' => 1,
                    'gr2t_start_date' => $now->format('Y-m-d H:i:s'),
                    'gr2t_changed_by' => 1,
                    'gr2t_created_by' => 1,
                ],
            ],
        ];
    }
}