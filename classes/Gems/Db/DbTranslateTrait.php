<?php


namespace Gems\Db;


trait DbTranslateTrait
{
    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * @var array list of tables that have translations
     */
    protected $translateTables;

    /**
     * @var array list of tables as keys and the fields that have translated
     */
    protected $translations;

    /**
     * Get a combined translation key to find the current translation
     *
     * @param $tableName string Name of the table
     * @param $field string name of the table column
     * @param $keyValues array|string values of all table keys, separated by _
     * @return string
     */
    public function getKey($tableName, $field, $keyValues)
    {
        if (is_array($keyValues)) {
            $keyValues = join('_', $keyValues);
        }
        return $tableName . '_' . $field . '_' .  $keyValues;
    }

    /**
     * All tables with translations as keys and an array with all table fields with translations
     *
     * @return array List of tables and columns with translations
     */
    public function getTablesWithTranslations()
    {
        $cacheId = 'dataBaseTablesWithTranslations';

        if (!$this->translateTables) {
            $tables = $this->cache->load($cacheId);
            if ($tables) {
                return $tables;
            }

            $select = $this->db->select();
            $select->from('gems__translations', ['gtrs_table', 'gtrs_field'])
                ->group(['gtrs_table', 'gtrs_field']);

            $rows = $this->db->fetchAll($select);

            $tables = [];
            foreach ($rows as $row) {
                $tables[$row['gtrs_table']][] = $row['gtrs_field'];
            }

            $this->cache->save($tables, $cacheId, ['database_translations']);
        }

        return $tables;
    }


    /**
     * Key value database translations
     * @return array List of translations
     */
    public function getTranslations()
    {
        if (!$this->translations) {

            $cacheId = 'dataBaseTranslations' . '_' . $this->locale->getLanguage();

            $translations = $this->cache->load($cacheId);
            if ($translations) {
                return $translations;
            }

            $select = $this->db->select();
            $select->from('gems__translations', [])
                ->columns(
                    [
                        'key' => new \Zend_Db_Expr("CONCAT(gtrs_table, '_', gtrs_field, '_', gtrs_keys)"),
                        'gtrs_translation'
                    ]
                )
                ->where('gtrs_iso_lang = ?', $this->locale->getLanguage());

            $this->translations = $this->db->fetchPairs($select);

            $this->cache->save($this->translations, $cacheId, ['database_translations']);
        }

        return $this->translations;
    }
}
