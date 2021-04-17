<?php

/**
 *
 * @package    Gems
 * @subpackage Translate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Translate;

/**
 *
 * @package    Gems
 * @subpackage Translate
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
trait DbTranslateUtilTrait
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var bool Should data be translated? Set in initDbTranslations()
     */
    protected $dbTranslationOff = true;

    /**
     * @var string Set in initDbTranslations()
     */
    protected $language; 
    
    /**
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Call this in afterRegistry() or checkRegistryRequestsAnswers()
     */
    protected function initDbTranslations()
    {
        $this->language = $this->locale->getLanguage();

        $this->dbTranslationOff = ($this->language == $this->project->getLocaleDefault()) ||
            (! $this->project->translateDatabaseFields());
    }

    /**
     * @param $tableName
     * @param $keyField
     * @param $keyValue
     * @return array|mixed|null
     */
    public function fetchTranslatedRow($tableName, $keyField, $keyValue)
    {
        $data = $this->db->fetchRow("SELECT * FROM $tableName WHERE $keyField = ? LIMIT 1", $keyValue);
        
        if ($this->dbTranslationOff) {
            return $data;
        } 
        if ($data) {
            return $this->translateTable($tableName, $keyValue, $data);
        }
        return $data;
    }
    
    /**
     * @param string $tableName
     * @param string $keyValue
     * @param array $data  
     * @return array
     */
    protected function translateTable($tableName, $keyValue, array $data)
    {
        if ($this->dbTranslationOff) {
            return $data;
        }
        
        $tSelect = $this->db->select();
        $tSelect->from('gems__translations', ['gtrs_field', 'gtrs_translation'])
                ->where('gtrs_table = ?', $tableName)
                ->where('gtrs_keys = ?', $keyValue)
                ->where('gtrs_iso_lang = ?', $this->language)
                ->where('LENGTH(gtrs_translation) > 0');

        $translations = $this->db->fetchPairs($tSelect);
        // \MUtil_Echo::track($tSelect->__toString(), $translations);

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
    protected function translateTables(array $tables, array $data)
    {
        if ($this->dbTranslationOff) {
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