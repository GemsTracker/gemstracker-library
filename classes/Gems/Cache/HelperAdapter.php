<?php

declare(strict_types=1);


namespace Gems\Cache;

use DateInterval;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\LogicException;

class HelperAdapter extends TagAwareAdapter
{
    private AdapterInterface $pool;

    public function __construct(
        AdapterInterface $itemsPool,
        AdapterInterface $tagsPool = null,
        float $knownTagVersionsTtl = 0.15
    ) {
        $this->pool = $itemsPool;
        parent::__construct($itemsPool, $tagsPool, $knownTagVersionsTtl);
    }

    /**
     * Cleans up everything to a save cacheId
     */
    public static function cleanupForCacheId(string $cacheId): string
    {
        return preg_replace('([^a-zA-Z0-9_])', '_', $cacheId);
    }

    public function getCacheItem(string $key): mixed
    {
        if ($this->pool->hasItem($key)) {
            $item = $this->pool->getItem($key);
            return $item->get();
        }

        return null;
    }

    public function setCacheItem(string $key, mixed $value, array|string $tag=null,
        DateInterval|int|null$expiresAfter=null)
    {
        $item = $this->pool->getItem($key);
        if ($tag !== null && $item instanceof CacheItem) {
            try {
                $item->tag($tag);
            } catch (LogicException) {}
        }
        if ($expiresAfter !== null) {
            $item->expiresAfter($expiresAfter);
        }
        $item->set($value);
        $this->pool->save($item);
    }
}
