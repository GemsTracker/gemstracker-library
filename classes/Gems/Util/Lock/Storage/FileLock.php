<?php

namespace Gems\Util\Lock\Storage;

use DateTimeImmutable;
use DateTimeInterface;

class FileLock extends LockStorageAbstract
{
    public function isLocked(): bool
    {
        return file_exists($this->key);
    }

    public function getLockTime(): ?DateTimeInterface
    {
        if ($this->isLocked()) {
            $date = new DateTimeImmutable();
            return $date->setTimestamp(filectime($this->key));
        }
    }

    public function lock(): void
    {
        touch($this->key);
    }

    public function unlock(): void
    {
        if ($this->isLocked()) {
            unlink($this->key);
        }
    }
}