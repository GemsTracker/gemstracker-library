<?php

/**
 *
 * @package    Gems
 * @subpackage Registry
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Add's automatic caching to an registry target object.
 *
 * @package    Gems
 * @subpackage Registry
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class Gems_Registry_CachedArrayTargetAbstract extends \Gems_Registry_TargetAbstract
{
    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = array();

    /**
     * The current data.
     *
     * @var array
     */
    protected $_data;

    /**
     * The id for this data
     *
     * @var mixed
     */
    protected $_id;

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
     * Does this data item exist?
     *
     * @var boolean
     */
    public $exists = false;

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
     * Return false on checkRegistryRequestsAnswers when the answer is not an array
     *
     * @var boolean
     */
    protected $requireArray = true;

    /**
     * Set in child classes
     *
     * @var string Name of table used in gtrs_table 
     */
    protected $translationTable;
    
    /**
     * Creates the object.
     *
     * @param mixed $id Whatever identifies this object.
     */
    public function __construct($id)
    {
        $this->_id = $id;
    }

    /**
     * isset() safe array access helper function.
     *
     * @param string $name
     * @return mixed
     */
    protected function _get($name)
    {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }
    }

    /**
     * Get the cacheId for the organization
     *
     * @return string
     */
    private function _getCacheId()
    {
        return \MUtil_String::toCacheId(GEMS_PROJECT_NAME . '__' . get_class($this) . '__' . $this->_id);
    }

    /**
     * array access test helper function.
     *
     * @param string $name
     * @return boolean
     */
    protected function _has($name)
    {
        return (boolean) isset($this->_data[$name]);
    }

    /**
     * @return bool This instance can be cached
     */
    protected function _hasCacheId()
    {
        return (boolean) $this->_id;
    }
    
    /**
     * Changes a value and signals the cache.
     *
     * @param string $name
     * @param mixed $value
     * @return \Gems_Registry_CachedArrayTargetAbstract (continuation pattern)
     */
    protected function _set($name, $value)
    {
        $this->_data[$name] = $value;

        // Do not reload / save here:
        // 1: other changes might follow,
        // 2: it might not be used,
        // 3: e.g. database saves may change other data.
        $this->invalidateCache();

        return $this;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        $lang = $this->locale->getLanguage();
        
        if ($this->cache && $this->_hasCacheId()) {
            $cacheId     = $this->cleanupForCacheId($this->_getCacheId());
            $cacheLang   = $cacheId . $this->cleanupForCacheId("_" . $lang); 
            $this->_data = $this->cache->load($cacheLang);
        } else {
            $cacheId = false;
        }

        if (! $this->_data) {
            $this->_data = $this->loadData($this->_id);

            if ($cacheId) {
                $this->cache->save($this->_data, $cacheId, $this->_cacheTags);
            }
            
            if (($lang != $this->project->getLocaleDefault()) && $this->translationTable && $this->project->translateDatabaseFields()) {
                
                $tSelect = $this->db->select();
                $tSelect->from('gems__translations', ['gtrs_field', 'gtrs_translation'])
                        ->where('gtrs_table = ?', $this->translationTable)
                        ->where('gtrs_keys = ?', $this->_id)
                        ->where('gtrs_iso_lang = ?', $lang)
                        ->where('LENGTH(gtrs_translation) > 0');

                $translations = $this->db->fetchPairs($tSelect);
                // \MUtil_Echo::track($tSelect->__toString(), $translations);

                if ($translations) {
                    foreach ($this->_data as $item => $value) {
                        if (isset($translations[$item])) {
                            // Set value to the translation
                            $this->_data[$item] = $translations[$item];
                        }
                    }
                }
            }

            if ($cacheId) {
                $this->cache->save($this->_data, $cacheLang, $this->_cacheTags);
            }
        }
        // \MUtil_Echo::track($this->_data);

        $this->exists = is_array($this->_data);

        return ($this->exists || (! $this->requireArray)) && parent::checkRegistryRequestsAnswers();
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

    /**
     * Empty the cache of the organization
     *
     * @return \Gems_User_Organization (continutation pattern)
     */
    public function invalidateCache()
    {
        if ($this->cache) {
            $cacheId = $this->_getCacheId();
            $this->cache->remove($cacheId);
        }
        return $this;
    }

    /**
     * Load the data when the cache is empty.
     *
     * @param mixed $id
     * @return array The array of data values
     */
    abstract protected function loadData($id);
}
