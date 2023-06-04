<?php

namespace Gems\Util;

use Gems\Cache\HelperAdapter;
use Gems\Db\ResultFetcher;
use Gems\Locale\Locale;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Select;

class UtilDbHelper
{
    protected bool $translateDatabaseFields = false;

    public function __construct(protected ResultFetcher $resultFetcher, protected HelperAdapter $cache, protected Locale $locale, array $config)
    {
        if (isset($this->config['translations'], $this->config['translations']['databaseFields']) && $this->config['translations']['databaseFields'] === true) {
            $this->translateDatabaseFields = true;
        }
    }

    protected function fetchSortedCached(string $functionName, string $cacheKey, Select|string $select, ?array $params = null, ?array $tags = null, ?callable $resultFunction = null): mixed
    {
        $cacheKey = HelperAdapter::cleanupForCacheId($cacheKey);

        if ($this->cache->hasItem($cacheKey)) {
            return $this->cache->getCacheItem($cacheKey);
        }

        $results = $this->resultFetcher->$functionName($select, $params);
        if ($results) {
            if (is_callable($resultFunction)) {
                $resultFunction($results);
            }
            $this->cache->setCacheItem($cacheKey, $results, $tags);

            return $results;
        }
        return null;
    }

    protected function getProcessedCached(string $fetchFunctionName, string $cacheKey, string|Select $select, callable $function, ?array $params = null, ?array $tags = null, ?callable $sort = null): array
    {
        $resultFunction = function(array $results) use ($function, $sort) {
            foreach($results as &$result) {
                $result = $function($result);
            }
            $sort($sort);
        };

        $result = $this->fetchSortedCached('fetchPairs', $cacheKey, $select, $params, $tags, $resultFunction);

        if ($result === null) {
            $result = [];
        }

        return $result;
    }

    public function getSelectAllCached(string $cacheId, string|Select $select, ?array $params = null, ?array $tags = null, bool $natSort = false): array
    {
        $cacheKey = static::class . '_a_' . $cacheId;

        $sort = null;
        if ($natSort) {
            $sort = 'natsort';
        }

        $result = $this->fetchSortedCached('fetchAll', $cacheKey, $select, $params, $tags, $sort);

        if ($result === null) {
            $result = [];
        }

        return $result;
    }

    public function getSelectColCached(string $cacheId, string|Select $select, ?array $params = null, ?array $tags = null, bool $natSort = false): array
    {
        $cacheKey = static::class . '_c_' . $cacheId;

        $sort = null;
        if ($natSort) {
            $sort = 'natsort';
        }

        $result = $this->fetchSortedCached('fetchCol', $cacheKey, $select, $params, $tags, $sort);

        if ($result === null) {
            $result = [];
        }

        return $result;
    }

    public function getSelectPairsCached(string $cacheId, string|Select $select, ?array $params = null, ?array $tags = null, ?callable $sort = null): array
    {
        $cacheKey = static::class . '_p_' . $cacheId;

        $result = $this->fetchSortedCached('fetchPairs', $cacheKey, $select, $params, $tags, $sort);

        if ($result === null) {
            $result = [];
        }

        return $result;
    }

    public function getSelectPairsProcessedCached(string $cacheId, string|Select $select, callable $function, ?array $params = null, ?array $tags = null, ?callable $sort = null): array
    {
        $cacheKey = static::class . '_' . $cacheId;

        return $this->getProcessedCached('fetchPairs', $cacheKey, $select, $function, $params, $tags, $sort);
    }

    public function getSelectProcessedCached(string $cacheId, string|Select $select, callable $function, ?array $params = null, ?array $tags = null, ?callable $sort = null): array
    {
        $cacheKey = static::class . '_' . $cacheId;

        return $this->getProcessedCached('fetchAll', $cacheKey, $select, $function, $params, $tags, $sort);
    }

    public function getTranslatedPairsCached(string $table, string $keyColumnName, string $valueColumnName, ?array $tags = [], ?array $where = null, ?callable $sort = null): array
    {
        $currentLanguage = $this->locale->getLanguage();
        $rawCacheId = "__trans $table $keyColumnName $valueColumnName";
        if ($where) {
            $rawCacheId .= http_build_query($where);
        }
        $cacheKey   = HelperAdapter::cleanupForCacheId($rawCacheId);
        $cacheLangKey = HelperAdapter::cleanupForCacheId($cacheKey . '_' . $currentLanguage);

        if ($this->cache->hasItem($cacheLangKey)) {
            return $this->cache->getCacheItem($cacheLangKey);
        }

        $result = $this->cache->getCacheItem($cacheKey);
        if (! $result) {
            $select = $this->resultFetcher->getSelect($table);
            $select->columns([$keyColumnName, $valueColumnName]);

            if ($where) {
                $select->where($where);
            }
            $result = $this->fetchSortedCached('fetchPairs', $cacheKey, $select, null, $tags, $sort);
        }

        if ($result && $this->translateDatabaseFields) {
            $translateSelect = $this->resultFetcher->getSelect('gems__translations');
            $translateSelect->columns(['gtrs_keys', 'gtrs_translation'])
                ->where([
                    'gtrs_table' => $table,
                    'gtrs_field' => $valueColumnName,
                    'gtrs_iso_lang' => $currentLanguage,
                ])->where->greaterThanOrEqualTo(new Expression('LENGTH(gtrs_translation)'), 0);

            $translations = $this->resultFetcher->fetchPairs($select);

            if ($translations) {
                foreach ($result as $item => $value) {
                    if (isset($translations[$item])) {
                        // Set value to the translation
                        $result[$item] = $translations[$item];
                    }
                }
            }

            $this->cache->setCacheItem($cacheLangKey, $result);
        }

        return $result ?: [];
    }
}