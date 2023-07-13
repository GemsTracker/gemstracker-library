<?php

namespace Gems\Translate;

use Gems\Cache\HelperAdapter;
use Gems\Locale\Locale;

class CachedDbTranslationRepository
{
    public function __construct(
        private readonly DbTranslationRepository $dbTranslationRepository,
        private readonly HelperAdapter $cache,
        private readonly Locale $locale,
    )
    {}

    /**
     * @param string $tableName
     * @param string $keyValue
     * @param array $data
     * @return array
     */
    public function translateTable(string $cacheKey, string $tableName, string $keyValue, array $data): array
    {
        $cacheKey = HelperAdapter::cleanupForCacheId($cacheKey . '_' . $this->locale->getLanguage());

        if ($this->cache->hasItem($cacheKey)) {
            return $this->cache->getCacheItem($cacheKey);
        }

        $translatedData = $this->dbTranslationRepository->translateTable($tableName, $keyValue, $data);
        $this->cache->setCacheItem($cacheKey, $translatedData);

        return $translatedData;
    }

    public function translateTables(string $cacheKey, array $tableNames, array $data): array
    {
        $cacheKey = HelperAdapter::cleanupForCacheId($cacheKey . '_' . $this->locale->getLanguage());
        if ($this->cache->hasItem($cacheKey)) {
            return $this->cache->getCacheItem($cacheKey);
        }

        $translatedData = $this->dbTranslationRepository->translateTables($tableNames, $data);
        $this->cache->setCacheItem($cacheKey, $translatedData);

        return $translatedData;
    }
}