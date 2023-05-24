<?php

namespace Gems\Db\Migration;

interface SeedInterface
{
    public function getDescription(): string|null;

    public function getDependencies(): array|null;

    public function getOrder(): int;



    public function __invoke(): array;
}
