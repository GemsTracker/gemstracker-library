<?php

namespace Gems\Util\Lock;

use DateInterval;
use DateTimeInterface;

interface LockInterface
{
    public function isLocked(): bool;

    public function getLockTime(): ?DateTimeInterface;

    /**
     * @param DateInterval|int|null $expiresAfter date interval or number of seconds
     * @return void
     */
    public function lock(DateInterval|int|null $expiresAfter=null): void;

    public function unlock(): void;
}