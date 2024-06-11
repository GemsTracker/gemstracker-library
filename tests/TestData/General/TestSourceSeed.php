<?php

namespace GemsTest\TestData\General;

use Gems\Db\Migration\SeedAbstract;

class TestSourceSeed extends SeedAbstract
{

    public function __invoke(): array
    {
        return [
            'gems__sources' => [
                [
                    'gso_id_source' => 20,
                    'gso_source_name' => 'Test Source',
                    'gso_ls_url' => 'https://survey.gemstracker.test',
                    'gso_ls_class' => 'LimeSurvey5m00Database',
                    'gso_changed_by' => 1,
                    'gso_created_by' => 1,
                ],
            ],
        ];
    }
}