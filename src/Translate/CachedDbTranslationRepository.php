<?php

namespace Gems\Translate;

use Gems\Cache\HelperAdapter;
use Gems\Db\CachedResultFetcher;
use Gems\Locale\Locale;
use Gems\Model\TranslationModel;
use Laminas\Db\Sql\Expression;

class CachedDbTranslationRepository
{
    public static array $cacheTags = ['database_translations'];

    protected array $translations = [];

    public function __construct(
        private readonly DbTranslationRepository $dbTranslationRepository,
        private readonly CachedResultFetcher $cachedResultFetcher,
        private readonly Locale $locale,
    )
    {}

    private function getKeyFields(array $keyFields, array $row): string
    {
        $keyValues = [];
        foreach($keyFields as $fieldName) {
            if (isset($row[$fieldName])) {
                $keyValues[] = $row[$fieldName];
            }
        }

        return join('_', $keyValues);
    }

    /**
     * Key value database translations
     * @param string $language
     * @return array List of translations
     */
    public function getTranslations(string $language): array
    {
        if (! isset($this->translations[$language])) {
            $cacheId = 'dataBaseTranslations' . '_' . $language;

            $select = $this->cachedResultFetcher->getSelect('gems__translations');
            $select->columns([
                'key' => new Expression(TranslationModel::KEY_COLUMN),
                'gtrs_translation'
            ])
                ->where(['gtrs_iso_lang' => $language]);

            $this->translations[$language] = $this->cachedResultFetcher->fetchPairs($cacheId, $select, null, static::$cacheTags);
        }

        return $this->translations[$language];
    }

    public function translateRow(string $tableName, string|array $keyFields, array $row, string|null $language = null): array
    {
        $translations = $this->getTranslations($language ?? $this->locale->getCurrentLanguage());
        foreach($row as $columnName => $value) {
            $keyValues = $this->getKeyFields((array)$keyFields, $row);

            $rowKey = sprintf('%s_%s_%s', $tableName, $columnName, $keyValues);

            if (isset($translations[$rowKey])) {
                $row[$columnName] = $translations[$rowKey];
            }
        }

        return $row;
    }


    /**
     * @param string $tableName
     * @param string $keyField
     * @param array $data
     * @return array
     */
    public function translateTable(string $cacheKey, string $tableName, string $keyField, array $data): array
    {
        $cache = $this->cachedResultFetcher->getCache();
        $cacheKey = HelperAdapter::createCacheKey([get_called_class(), $cacheKey, $tableName, $keyField, $this->locale->getLanguage()], $data);

        if ($cache->hasItem($cacheKey)) {
            return $cache->getCacheItem($cacheKey);
        }

        $translatedData = $this->dbTranslationRepository->translateTable($tableName, $keyField, $data);
        $cache->setCacheItem($cacheKey, $translatedData);

        return $translatedData;
    }

    public function translateTables(string $cacheKey, array $tableNames, array $data): array
    {
        $cache = $this->cachedResultFetcher->getCache();
        $cacheKey = HelperAdapter::createCacheKey([get_called_class(), $cacheKey], $tableNames, $data);

        if ($cache->hasItem($cacheKey)) {
            return $cache->getCacheItem($cacheKey);
        }

        $translatedData = $this->dbTranslationRepository->translateTables($tableNames, $data);
        $cache->setCacheItem($cacheKey, $translatedData);

        return $translatedData;
    }
}