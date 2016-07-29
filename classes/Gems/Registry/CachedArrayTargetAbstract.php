<?php

/**
 *
 * @package    Gems
 * @subpackage Registry
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
     * Does this data item exist?
     *
     * @var boolean
     */
    public $exists = false;

    /**
     * Return false on checkRegistryRequestsAnswers when the anser is not an array
     *
     * @var boolean
     */
    protected $requireArray = true;

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
        if ($this->cache) {
            $cacheId = $this->_getCacheId();
            $this->_data = $this->cache->load($cacheId);
        } else {
            $cacheId = false;
        }

        if (! $this->_data) {
            $this->_data = $this->loadData($this->_id);

            if ($cacheId) {
                $this->cache->save($this->_data, $cacheId, $this->_cacheTags);
            }
        }
        // \MUtil_Echo::track($this->_data);

        $this->exists = is_array($this->_data);

        return ($this->exists || (! $this->requireArray)) && parent::checkRegistryRequestsAnswers();
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
