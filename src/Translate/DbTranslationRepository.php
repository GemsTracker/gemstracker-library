<?php

namespace Gems\Translate;

use Gems\Db\ResultFetcher;
use Gems\Locale\Locale;
use Phinx\Util\Expression;

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
        $defaultLocale = $config['locale']['default'] ?? null;
        $configEnabled = $config['translations']['databaseFields'] ?? false;
        if ($this->language !== $defaultLocale && $configEnabled) {
            $this->translationsEnabled = true;
        }
    }

    /**
     * @param string $tableName
     * @param string $keyValue
     * @param array $data
     * @return array
     */
    public function translateTable(string $tableName, string $keyValue, array $data): array
    {
        if (!$this->translationsEnabled) {
            return $data;
        }

        $tSelect = $this->resultFetcher->getSelect('gems__translations');
        $tSelect->columns(['gtrs_field', 'gtrs_translation'])
            ->where([
                'gtrs_table' => $tableName,
                'gtrs_keys' => $keyValue,
                'gtrs_iso_lang' => $this->language,
                new Expression('LENGTH(gtrs_translation) > 0')
            ]);

        try {
            $translations = $this->resultFetcher->fetchPairs($tSelect);
            // \MUtil\EchoOut\EchoOut::track($tSelect->__toString(), $translations);
        } catch (\Exception $sme) {
            // Ignore: as can be setup error
            $translations = [];
            \MUtil\EchoOut\EchoOut::r("Translations table required, but does not exist.");
            error_log($sme->getMessage());
        }

        if ($translations) {
            foreach ($data as $item => $value) {
                if (isset($translations[$item])) {
                    // Set value to the translation
                    $data[$item] = $translations[$item];
                }
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