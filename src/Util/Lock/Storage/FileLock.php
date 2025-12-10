<?php

namespace Gems\Util\Lock\Storage;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

class FileLock extends LockStorageAbstract
{
    private function calculateExpiresAt(DateInterval|int|null $expiresAfter): int
    {
        if ($expiresAfter === null) {
            return 0;
        }
        if (is_int($expiresAfter)) {
            return time() + $expiresAfter;
        }
        return (new DateTimeImmutable())->add($expiresAfter)->getTimestamp();
    }

    public function isLocked(): bool
    {
        if (!file_exists($this->key)) {
            return false;
        }

        $expiresAt = file_get_contents($this->key);
        if ($expiresAt === false || $expiresAt === '') {
            return true;
        }

        if (time() >= (int)$expiresAt) {
            $this->unlock();
            return false;
        }

        return true;
    }

    public function getLockTime(): ?DateTimeInterface
    {
        if ($this->isLocked()) {
            $date = new DateTimeImmutable();
            return $date->setTimestamp(filemtime($this->key));
        }
        return null;
    }

    public function lock(DateInterval|int|null $expiresAfter=null): void
    {
        $expiresAt = $this->calculateExpiresAt($expiresAfter);
        file_put_contents($this->key, (string)$expiresAt);
    }

    public function unlock(): void
    {
        if (file_exists($this->key)) {
            unlink($this->key);
        }
    }
}