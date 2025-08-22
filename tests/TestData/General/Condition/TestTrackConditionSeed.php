<?php

namespace GemsTest\TestData\General\Condition;

use Gems\Db\Migration\SeedAbstract;

class TestTrackConditionSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'gems__tracks' => [
                [
                    'gtr_id_track' => 7000,
                    'gtr_track_name' => 'Condition track',
                    'gtr_date_start' => $now->format('Y-m-d'),
                    'gtr_track_class' => 'AnyStepEngine',
                    'gtr_active' => 1,
                    'gtr_changed_by' => 1,
                    'gtr_created_by' => 1,
                ],
                [
                    'gtr_id_track' => 7001,
                    'gtr_track_name' => 'Other track',
                    'gtr_date_start' => $now->format('Y-m-d'),
                    'gtr_track_class' => 'AnyStepEngine',
                    'gtr_active' => 1,
                    'gtr_changed_by' => 1,
                    'gtr_created_by' => 1,
                ],
            ],
        ];
    }
}