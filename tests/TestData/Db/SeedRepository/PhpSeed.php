<?php

namespace GemsTest\Data\Db\SeedRepository;

use Gems\Db\Migration\SeedAbstract;

class PhpSeed extends SeedAbstract
{
    public function getDescription(): string|null
    {
        return 'php test seed';
    }

    public function getOrder(): int
    {
        return 501;
    }

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
