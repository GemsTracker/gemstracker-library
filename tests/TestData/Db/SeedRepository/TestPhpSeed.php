<?php

namespace GemsTest\Data\Db\SeedRepository;

use Gems\Db\Migration\SeedInterface;

class TestPhpSeed implements SeedInterface
{
    public function __invoke(): array
    {
        return [
            'test__table' => [
                [
                    'tt_description' => 'hi php',
                ],
            ],
        ];
    }
}