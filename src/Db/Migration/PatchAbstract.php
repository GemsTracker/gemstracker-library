<?php

namespace Gems\Db\Migration;

abstract class PatchAbstract implements PatchInterface
{

    public function getDescription(): string|null
    {
        return null;
    }

    public function getOrder(): int
    {
        return 1000;
    }
}