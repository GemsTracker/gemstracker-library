<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestTrackSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'gems__tracks' => [
                [
                    'gtr_id_track' => 7000,
                    'gtr_track_name' => 'Weight track',
                    'gtr_date_start' => $now->format('Y-m-d'),
                    'gtr_track_class' => 'AnyStepEngine',
                    'gtr_changed_by' => 1,
                    'gtr_created_by' => 1,
                ],
            ],
        ];
    }
}