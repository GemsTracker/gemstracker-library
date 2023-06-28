<?php

namespace GemsTest\TestData\Db\PatchRepository;

use Gems\Db\Migration\PatchAbstract;

class PhpPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'add created field to test table';
    }

    public function getOrder(): int
    {
        return 100;
    }

    public function __invoke(): array
    {
        return [
            'ALTER TABLE test__table ADD tt_created timestamp not null default current_timestamp',
        ];
    }
}