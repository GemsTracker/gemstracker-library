<?php

namespace Gems\Db;

use Gems\Cache\HelperAdapter;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;

class CachedResultFetcher
{
    public function __construct(protected ResultFetcher $resultFetcher, protected HelperAdapter $cache)
    {}

    protected function fetchCached(string $functionName, string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null, $default = null): mixed
    {
        $cacheKey = HelperAdapter::cleanupForCacheId($cacheKey);

        if ($this->cache->hasItem($cacheKey)) {
            return $this->cache->getCacheItem($cacheKey);
        }

        $results = $this->resultFetcher->$functionName($select, $params);
        if ($results) {
            $this->cache->setCacheItem($cacheKey, $results, $tags);
            return $results;
        }
        return $default;
    }

    public function fetchPairs(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null): ?array
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }

    public function fetchAll(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null): ?array
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }

    public function fetchCol(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null): ?array
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }

    public function fetchOne(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null): string|int|null
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }

    public function fetchRow(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null): ?array
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }

    public function fetchAssociative(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null): ?array
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }

    public function fetchAllAssociative(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null): ?array
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }

    public function getAdapter(): Adapter
    {
        return $this->resultFetcher->getAdapter();
    }

    public function getCache(): HelperAdapter
    {
        return $this->cache;
    }

    public function getResultFetcher(): ResultFetcher
    {
        return $this->resultFetcher;
    }

    public function getSelect(null|string|TableIdentifier $table = null): Select
    {
        return $this->resultFetcher->getSelect($table);
    }

    public function query(string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null)
    {
        return $this->fetchCached(__FUNCTION__, $cacheKey, $select, $params, $tags);
    }
}