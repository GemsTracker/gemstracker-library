<?php

/**
 * A Zend Cache 1 backend wrapper for PSR-16 compatible Cache libraries like Symfony cache 3.4+
 */

namespace Gems\Cache\Backend;


use Psr\SimpleCache\CacheInterface;
use Gems\Cache\Backend;

class Psr16Cache extends Backend implements \Zend_Cache_Backend_Interface
{
    /**
     * @var CacheInterface PSR-16 cache interface
     */
    protected $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the cache storage adapter
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * Note : return value is always "string" (unserialization is done by the core not by the backend)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        return $this->cache->get($id, false);
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        return $this->cache->has($id);
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data            Datas to cache
     * @param  string $id              Cache id
     * @param  array $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int   $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        if (!empty($tags)) {
            $this->_log('Tags are not supported in psr-16 backends');
        }
        if ($specificLifetime === false) {
            $specificLifetime = null;
        }

        return $this->cache->set($id, $data, $specificLifetime);
    }

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id)
    {
        $this->cache->delete($id);
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        \MUtil_Echo::track($mode);
        switch ($mode) {
            case \Zend_Cache::CLEANING_MODE_ALL:
                $this->cache->clear();
                return true;
                break;
            case \Zend_Cache::CLEANING_MODE_OLD:
                throw new \Exception("CLEANING_MODE_OLD is unsupported by the PSR-16 backends.");
                break;
            case \Zend_Cache::CLEANING_MODE_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                throw new \Exception('Tags are unsupported by the PSR-16 backends.');
                break;
            default:
                \Zend_Cache::throwException('Invalid mode for clean() method');
                break;
        }
    }
}