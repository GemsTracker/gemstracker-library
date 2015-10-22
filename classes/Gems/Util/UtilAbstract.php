<?php

/**
 * Copyright (c) 2015, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage UtilAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: UtilAbstract.php 2493 2015-04-15 16:29:48Z matijsdejong $
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
    protected function _getSelectAllCached($cacheId, $sql, $binds = array(), $tags = array())
    {
        $cacheId = strtr(get_class($this) . '_a_' . $cacheId, '\\/', '__');

        $result = $this->cache->load($cacheId);

        if ($result) {
            return $result;
        }

        $result = $this->db->fetchAll($sql, (array) $binds);

        if ($natSort) {
            natsort($result);
        }

        $this->cache->save($result, $cacheId, (array) $tags);

        return $result;
    }

    /**
     * Utility function for loading a query paired from cache
     *
     * @param string $cacheId The class is prepended to this id
     * @param mixed $sql string or \Zend_Db_Select
     * @param array $binds sql paramters
     * @param mixed $tags atring or array of strings
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

        $result = $this->db->fetchPairs($sql, (array) $binds);

        if ($result && $sort) {
            $this->_sortResult($result, $sort);
        }

        $this->cache->save($result, $cacheId, (array) $tags);

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

        return $result;
    }

    /**
     * Sort the array using the specified sort function
     *
     * @param array $result
     * @param strng $sort
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
