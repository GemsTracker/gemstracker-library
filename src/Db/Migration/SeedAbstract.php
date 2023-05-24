<?php

namespace Gems\Db\Migration;

abstract class SeedAbstract implements SeedInterface
{
    public function getDependencies(): array|null
    {
        return null;
    }

    public function getDescription(): string|null
    {
        return null;
    }

    public function getOrder(): int
    {
        return 1000;
    }
}
