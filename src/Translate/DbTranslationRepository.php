<?php

namespace Gems\Translate;

use Gems\Db\ResultFetcher;
use Gems\Locale\Locale;
use Laminas\Db\Sql\Expression;

class DbTranslationRepository
{
    protected string $language;
    protected bool $translationsEnabled = false;

    public function __construct(
        protected ResultFetcher $resultFetcher,
        Locale $locale,
        array $config,
    )
    {
        $this->language = $locale->getLanguage();
        $this->translationsEnabled = $config['model']['translateDatabaseFields'] ?? false;
    }

    public function fetchTranslatedRow(string $tableName, string $keyField, mixed $keyValue): array|null
    {
        $select = $this->resultFetcher->getSelect($tableName);
        $select->where([
            $keyField => $keyValue,
        ]);

        $result = $this->resultFetcher->fetchRow($select);

        return $this->translateTable($tableName, $keyField, $result);
    }

    /**
     * @param string $tableName
     * @param string $keyValue
     * @param array $row
     * @return array
     */
    protected function translateRow(string $tableName, string $keyValue, array $row): array
    {
        $tSelect = $this->resultFetcher->getSelect('gems__translations');
        $tSelect->columns(['gtrs_field', 'gtrs_translation'])
            ->where([
                'gtrs_table' => $tableName,
                'gtrs_keys' => $keyValue,
                'gtrs_iso_lang' => $this->language,
                'LENGTH(gtrs_translation) > 0'
            ]);

        try {
            $translations = $this->resultFetcher->fetchPairs($tSelect);
        } catch (\Exception $sme) {
            // Ignore: as can be setup error
            $translations = [];
            dump("Translations table required, but does not exist.");
            error_log($sme->getMessage());
        }

        if ($translations) {
            foreach ($row as $item => $value) {
                if (isset($translations[$item])) {
                    // Set value to the translation
                    $row[$item] = $translations[$item];
                }
            }
        }

        return $row;
    }

    /**
     * @param string $tableName
     * @param string $keyField
     * @param array $data
     * @return array<int, array>
     */
    public function translateTable(string $tableName, string $keyField, array $data): array
    {
        if (!$this->translationsEnabled) {
            return $data;
        }

        foreach ($data as &$row) {
            $key = $row[$keyField] ?? false;
            if ($key) {
                $row = $this->translateRow($tableName, $key, $row);
            }
        }
        return $data;
    }

    /**
     * Translates multiple tables in a row
     *
     * @param array $tables Array of table name => key field name
     * @param array $data
     * @return array
     */
    public function translateTables(array $tables, array $data): array
    {
        if (!$this->translationsEnabled) {
            return $data;
        }
        foreach ($tables as $table => $key) {
            if (isset($data[$key])) {
                $data = $this->translateTable($table, $data[$key], $data);
            }
        }

        return $data;
    }


}