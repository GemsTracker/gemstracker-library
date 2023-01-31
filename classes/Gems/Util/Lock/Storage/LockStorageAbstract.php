<?php

namespace Gems\Util\Lock\Storage;

use Gems\Util\Lock\LockInterface;

abstract class LockStorageAbstract implements LockInterface
{
    protected string $key;

    public function setKey(string $key): void
    {
        $this->key = $key;
    }
}