<?php

/**
 *
 * @package    Gems
 * @subpackage UtilAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

/**
 * Abstract utility class containing caching and sql loading function
 *
 * @package    Gems
 * @subpackage UtilAbstract
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 24-sep-2015 11:37:10
 */
class UtilAbstract extends \MUtil_Translate_TranslateableAbstract
{
    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

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
     *
     * @var \MUtil_Registry_Source
     */
    protected $source;

    /**
     * Returns a callable if a method is called as a variable
     *
     * @param string $name
     * @return \MUtil_Lazy_Call
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return \MUtil_Lazy::call(array($this, $name));
        }

        throw new \Gems_Exception_Coding("Unknown method '$name' requested as callable.");
    }

    /**
     * Utility function for loading a complete query from cache into objects
     *
     * @param string $cacheId The class is prepended to this id
     * @param object $object The object to put the data in
     * @param mixed $sql string or \Zend_Db_Select
     * @param array $binds sql paramters
     * @param mixed $tags atring or array of strings
     * @return array
     */
    protected function _getObjectsAllCached($cacheId, $object, $sql, $binds = null, $tags = array())
    {
        $output = array();
        $rows   = $this->_getSelectAllCached($cacheId, $sql, $binds, $tags);

        if ($rows) {
            $this->source->applySource($object);

            foreach ($rows as $row) {
                $tmp = clone $object;
                $tmp->exchangeArray($row);
                $output[] = $tmp;
            }
        }

        return $output;
    }

    /**
     * Utility function for loading a complete query from cache
     *
     * @param string $cacheId The class is prepended to this id
     * @param mixed $sql string or \Zend_Db_Select
     * @param array $binds sql paramters
     * @param mixed $tags atring or array of strings
     * @param boolean $natSort Perform a natsort over the output
     * @return array
     */
    protected function _getSelectAllCached($cacheId, $sql, $binds = array(), $tags = array(), $natSort = false)
    {
        $cacheId = strtr(get_class($this) . '_a_' . $cacheId, '\\/', '__');

        $result = $this->cache->load($cacheId);

        if ($result) {
            return $result;
        }

        try {
            $result = $this->db->fetchAll($sql, (array) $binds);

            if ($natSort) {
                natsort($result);
            }

            $this->cache->save($result, $cacheId, (array) $tags);
        } catch (\Zend_Db_Statement_Mysqli_Exception $e) {
            error_log($e->getMessage());
            $result = array();
        }

        return $result;
    }

    /**
     * Utility function for loading a single column from cache
     *
     * @param string $cacheId The class is prepended to this id
     * @param mixed $sql string or \Zend_Db_Select
     * @param array $binds sql paramters
     * @param mixed $tags atring or array of strings
     * @param boolean $natSort Perform a natsort over the output
     * @return array With a column ofr values
     */
    protected function _getSelectColCached($cacheId, $sql, $binds = array(), $tags = array(), $natSort = false)
    {
        $cacheId = strtr(get_class($this) . '_a_' . $cacheId, '\\/', '__');

        $result = $this->cache->load($cacheId);

        if ($result) {
            return $result;
        }

        try {
            $result = $this->db->fetchCol($sql, (array) $binds);

            if ($natSort) {
                natsort($result);
            }

            $this->cache->save($result, $cacheId, (array) $tags);
        } catch (\Zend_Db_Statement_Mysqli_Exception $e) {
            error_log($e->getMessage());
            $result = [];
        }

        return $result;
    }

    /**
     * Utility function for loading a query paired from cache
     *
     * @param string $cacheId The class is prepended to this id
     * @param mixed $sql string or \Zend_Db_Select
     * @param array $binds sql paramters
     * @param mixed $tags a string or array of strings
     * @param string Optional function to sort on, only known functions will do
     * @return array
     */
    protected function _getSelectPairsCached($cacheId, $sql, $binds = array(), $tags = array(), $sort = null)
    {
        $cacheId = strtr(get_class($this) . '_p_' . $cacheId, '\\/', '__');

        $result = $this->cache->load($cacheId);

        if ($result) {
            return $result;
        }

        try {
            $result = $this->db->fetchPairs($sql, (array) $binds);

            if ($result && $sort) {
                $this->_sortResult($result, $sort);
            }

            $this->cache->save($result, $cacheId, (array) $tags);
        } catch (\Zend_Db_Statement_Mysqli_Exception $e) {
            error_log($e->getMessage());
            $result = array();
        }

        return $result;
    }

    /**
     * Utility function for loading a query from cache
     *
     * @param string $cacheId The class is prepended to this id
     * @param mixed $sql string or \Zend_Db_Select
     * @param callable $function The function called with each row to form the result
     * @param array $binds sql paramters
     * @param mixed $tags string or array of strings
     * @param string Optional function to sort on, only known functions will do
     * @return array
     */
    protected function _getSelectPairsProcessedCached($cacheId, $sql, $function, $binds = array(), $tags = array(), $sort = null)
    {
        $cacheId = get_class($this) . '_' . $cacheId;

        $result = false; //$this->cache->load($cacheId);

        if ($result) {
            return $result;
        }

        try {
            $result = $this->db->fetchPairs($sql, (array) $binds);

            if ($result) {
                foreach ($result as $id => & $value) {
                    $value = call_user_func($function, $value);
                }

                if ($sort) {
                    $this->_sortResult($result, $sort);
                }
            }

            $this->cache->save($result, $cacheId, (array) $tags);
        } catch (\Zend_Db_Statement_Mysqli_Exception $e) {
            error_log($e->getMessage());
            $result = array();
        }

        return $result;
    }

    /**
     * Utility function for loading a query from cache
     *
     * @param string $cacheId The class is prepended to this id
     * @param mixed $sql string or \Zend_Db_Select
     * @param callable $function The function called with each row to form the result
     * @param string $keyField The field containing the key for each row
     * @param mixed $tags string or array of strings
     * @param string Optional function to sort on, only known functions will do
     * @return array
     */
    protected function _getSelectProcessedCached($cacheId, $sql, $function, $keyField, $tags = array(), $sort = null)
    {
        $cacheId = get_class($this) . '_' . $cacheId;

        $result = false; //$this->cache->load($cacheId);

        if ($result) {
            return $result;
        }

        $result = array();
        try {
            $rows   = $this->db->fetchAll($sql);

            if ($rows) {
                foreach ($rows as $row) {
                    if (! isset($result[$row[$keyField]])) {
                        $result[$row[$keyField]] = call_user_func($function, $row);
                    }
                }

                if ($sort) {
                    $this->_sortResult($result, $sort);
                }
            }

            $this->cache->save($result, $cacheId, (array) $tags);
        } catch (\Zend_Db_Statement_Mysqli_Exception $e) {
            error_log($e->getMessage());
        }

        return $result;
    }

    /**
     * Utility function for loading a translated paired from cache
     *
     * @param string $table
     * @param string $key
     * @param string $label
     * @param mixed $tags a string or array of strings
     * @param string $where Input for $select->where()
     * @param string Optional function to sort on, only known functions will do
     * @return array
     * @throws \Zend_Cache_Exception
     */
    protected function _getTranslatedPairsCached($table, $key, $label, $tags = array(), $where = null, $sort = null)
    {
        $lang      = $this->locale->getLanguage();
        $cacheId   = $this->cleanupForCacheId("__trans $table $key $label $where ");
        $cacheLang = $cacheId . $this->cleanupForCacheId($lang . "_");

        // \MUtil_Echo::track($cacheId, $cacheLang);
        
        $result = $this->cache->load($cacheLang);
        if ($result) {
            return $result;
        }

        $result = $this->cache->load($cacheId);
        if (! $result) {
            $select = $this->db->select();
            $select->from($table, [$key, $label]);
            
            if ($where) {
                $select->where($where);
            }
            try {
                $result = $this->db->fetchPairs($select);
        
                $this->cache->save($result, $cacheId, (array) $tags);
                
            } catch (\Zend_Exception $e) {
                // Do not save on an exception
                error_log($e->getMessage());
                return [];
            }
        }
        
        if ($result && $this->project->translateDatabaseFields() && ($lang != $this->project->getLocaleDefault())) {
            $tSelect = $this->db->select();
            $tSelect->from('gems__translations', ['gtrs_keys', 'gtrs_translation'])
                ->where('gtrs_table = ?', $table)
                ->where('gtrs_field = ?', $label)
                ->where('gtrs_iso_lang = ?', $lang)
                ->where('LENGTH(gtrs_translation) > 0');
                
            $translations = $this->db->fetchPairs($tSelect);
            // \MUtil_Echo::track($tSelect->__toString(), $translations);
            
            if ($translations) {
                foreach ($result as $item => $value) {
                    if (isset($translations[$item])) {
                        // Set value to the translation
                        $result[$item] = $translations[$item];
                    }
                }
            }
        }

        if ($result && $sort) {
            $this->_sortResult($result, $sort);
        }
        // \MUtil_Echo::track($result);

        // Save the translated version
        $this->cache->save($result, $cacheLang, (array) $tags);
        
        return $result ?: [];
    }

    /**
     * Sort the array using the specified sort function
     *
     * @param array $result
     * @param callable $sort Sort function
     */
    protected function _sortResult(array &$result, $sort = 'asort')
    {
        // Sorting
        switch ($sort) {
            case 'asort':
                asort($result);
                break;

            case 'ksort':
                ksort($result);
                break;

            case 'natsort':
                natsort($result);
                break;

            default:
                $sort($result);
        }
    }

    /**
     * Cleans up everything to a save cacheId
     *
     * @param string $cacheId
     * @return string
     */
    public static function cleanupForCacheId($cacheId)
    {
        return preg_replace('([^a-zA-Z0-9_])', '_', $cacheId);
    }
}
