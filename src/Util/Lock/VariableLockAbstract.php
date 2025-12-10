<?php

namespace Gems\Util\Lock;

use DateInterval;
use DateTimeInterface;
use Gems\Util\Lock\Storage\FileLock;
use Gems\Util\Lock\Storage\LockStorageAbstract;

abstract class VariableLockAbstract implements LockInterface
{
    protected string $key;

    public function __construct(protected LockStorageAbstract $lockStorage, protected string $rootDir = '')
    {
        if ($this->lockStorage instanceof FileLock) {
            $this->key = $this->getLockDirectory() . $this->key . '.lock';
        }
        $this->lockStorage->setKey($this->key);
    }

    public function isLocked(): bool
    {
        return $this->lockStorage->isLocked();
    }

    protected function getLockDirectory(): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
    }

    public function getLockTime(): ?DateTimeInterface
    {
        return $this->lockStorage->getLockTime();
    }

    public function lock(DateInterval|int|null $expiresAfter=null): void
    {
        $this->lockStorage->lock($expiresAfter);
    }

    public function unlock(): void
    {
        $this->lockStorage->unlock();
    }
}