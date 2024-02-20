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

use Gems\Cache\HelperAdapter;
use Gems\Util\UtilDbHelper;
use MUtil\Registry\TargetAbstract;
use Zalt\Base\TranslateableTrait;

/**
 * Abstract utility class containing caching and sql loading function
 *
 * @package    Gems
 * @subpackage UtilAbstract
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 24-sep-2015 11:37:10
 */
class UtilAbstract extends TargetAbstract
{
    use \MUtil\Translate\TranslateableTrait;

    /**
     *
     * @var HelperAdapter
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
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \MUtil\Registry\Source
     */
    protected $source;

    public function __construct(
        protected UtilDbHelper $utilDbHelper,
    ) {}

    /**
     * Returns a callable if a method is called as a variable
     *
     * @param string $name
     * @return \MUtil\Lazy\Call
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return \MUtil\Lazy::call(array($this, $name));
        }

        throw new \Gems\Exception\Coding("Unknown method '$name' requested as callable.");
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
    protected function _getSelectColCached($cacheId, $sql, $binds = array(), $tags = array(), $natSort = false): array
    {
        $cacheId = HelperAdapter::createCacheKey([get_called_class(), $cacheId], $sql, $binds, $natSort);

        $result = $this->cache->getCacheItem($cacheId);

        if ($result) {
            return $result;
        }

        try {
            $result = $this->db->fetchCol($sql, (array) $binds);

            if ($natSort) {
                natsort($result);
            }

            $this->cache->setCacheItem($cacheId, $result, (array) $tags);
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
     * @param string $sort Optional function to sort on, only known functions will do
     * @return array
     */
    protected function _getSelectPairsCached($cacheId, $sql, $binds = array(), $tags = array(), $sort = null)
    {
        $cacheId = HelperAdapter::createCacheKey([get_called_class(), $cacheId], $sql, $binds, $sort);

        $result = $this->cache->getCacheItem($cacheId);

        if ($result) {
            return $result;
        }

        try {
            $result = $this->db->fetchPairs($sql, (array) $binds);

            if ($result && $sort) {
                $this->_sortResult($result, $sort);
            }

            $this->cache->setCacheItem($cacheId, $result, (array) $tags);
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
     * @param string $sort Optional function to sort on, only known functions will do
     * @return array
     */
    protected function _getSelectPairsProcessedCached($cacheId, $sql, $function, $binds = array(), $tags = array(), $sort = null)
    {
        $cacheId = HelperAdapter::createCacheKey([get_called_class(), $cacheId], $sql, $binds, $sort);

        $result = $this->cache->getCacheItem($cacheId);

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

            $this->cache->setCacheItem($cacheId, $result, (array) $tags);
        } catch (\Zend_Db_Statement_Mysqli_Exception $e) {
            error_log($e->getMessage());
            $result = array();
        }

        return $result;
    }

    /**
     * Sort the array using the specified sort function
     *
     * @param array $result
     * @param string|callable $sort Sort function
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
}
