<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
        return preg_replace('/[^a-zA-Z0-9_]/', '_', GEMS_PROJECT_NAME . '__' . get_class($this) . '__' . $this->_id);
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

        return is_array($this->_data) && parent::checkRegistryRequestsAnswers();
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
