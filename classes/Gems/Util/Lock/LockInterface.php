<?php

namespace Gems\Util\Lock;

use DateTimeInterface;

interface LockInterface
{
    public function isLocked(): bool;

    public function getLockTime(): ?DateTimeInterface;

    public function lock(): void;

    public function unlock(): void;
}