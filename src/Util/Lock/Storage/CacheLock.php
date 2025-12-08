<?php

namespace Gems\Util\Lock\Storage;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Gems\Cache\HelperAdapter;

class CacheLock extends LockStorageAbstract
{
    public function __construct(protected HelperAdapter $cache)
    {}

    public function isLocked(): bool
    {
        if ($this->cache->hasItem($this->key)) {
            $lockInfo = $this->cache->getCacheItem($this->key);
            if (isset($lockInfo['enabled'])) {
                return (bool)$lockInfo['enabled'];
            }
        }
        return false;
    }

    public function getLockTime(): ?DateTimeInterface
    {
        if ($this->cache->hasItem($this->key)) {
            $lockInfo = $this->cache->getCacheItem($this->key);
            if (isset($lockInfo['startTime'])) {
                if ($lockInfo instanceof DateTimeInterface) {
                    return $lockInfo;
                }
            }
        }

        return null;
    }

    public function lock(DateInterval|int|null $expiresAfter=null): void
    {
        $lockInfo = [
            'enabled' => true,
            'startTime' => new DateTimeImmutable(),
        ];
        $this->cache->setCacheItem($this->key, $lockInfo, null, $expiresAfter);
    }

    public function setKey(string $key): void
    {
        parent::setKey($this->cache::cleanupForCacheId($key));
    }

    public function unlock(): void
    {
        $this->cache->deleteItems([$this->key]);
    }
}