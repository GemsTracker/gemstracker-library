<?php

namespace Gems\Db\Migration;

interface SeedInterface
{
    public function __invoke(): array;
}