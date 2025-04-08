<?php

declare(strict_types=1);


namespace Gems\Cache;

use DateInterval;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\LogicException;

class HelperAdapter extends TagAwareAdapter
{
    /**
     * Create a unique cache key, based on the provided arguments.
     *
     * @param array<int, string> $plainKeyParts Array of strings to include in the cache
     *                                          key as is, so we still know what's what.
     * @param ...$args mixed All other arguments provided to the original caching function.
     * @return string A unique string that can be used as a cache key.
     */
    public static function createCacheKey(array $plainKeyParts, ...$args): string
    {
        return self::cleanupForCacheId(implode('_', $plainKeyParts) . '_' . md5(serialize($args)));
    }

    /**
     * Cleans up everything to a save cacheId
     */
    public static function cleanupForCacheId(string $cacheId): string
    {
        return preg_replace('([^a-zA-Z0-9_])', '_', $cacheId);
    }

    public function hasCacheItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    public function getCacheItem(string $key): mixed
    {
        $item = $this->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function setCacheItem(string $key, mixed $value, array|string $tag=null,
        DateInterval|int|null $expiresAfter=null)
    {
        $item = $this->getItem($key);
        if ($tag !== null && $item instanceof CacheItem) {
            try {
                $item->tag($tag);
            } catch (LogicException) {}
        }
        if ($expiresAfter !== null) {
            $item->expiresAfter($expiresAfter);
        }
        $item->set($value);
        $this->save($item);
    }
}
