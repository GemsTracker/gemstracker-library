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
 * @package    MUtil
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Return a value (the kind is up to the user) using a scalar key.
 *
 * The advantages to using e.g. a standard array object is that both the
 * key type and the search algorithm can be customized in each child class.
 *
 * @package    MUtil
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Util_LookupList
{
    protected $_elements;

    public function __construct(array $initialList = null)
    {
        $this->set((array) $initialList);
    }

    /**
     * Function triggered when the underlying lookup array has changed.
     *
     * This function exists to allow overloading in subclasses.
     *
     * @return void
     */
    protected function _changed()
    {  }

    /**
     * Item lookup function.
     *
     * This is a separate function to allow overloading by subclasses.
     *
     * @param scalar $key
     * @param mixed $default
     * @return mixed
     */
    protected function _getItem($key, $default = null)
    {
        if (array_key_exists($key, $this->_elements)) {
            return $this->_elements[$key];
        } else {
            return $default;
        }
    }

    public function add($key, $result = null)
    {
        if (is_array($key)) {
            $this->merge($key);
        } else {
            $this->set($key, $result);
        }
        return $this;
    }

    public function get($key = null, $default = null)
    {
        if (null === $key) {
            return $this->_elements;
        } else {
            return $this->_getItem($key, $default);
        }
    }

    public function merge(array $mergeList)
    {
        $this->_elements = array_merge($this->_elements, $mergeList);
        $this->_changed();
        return $this;
    }

    public function remove($key)
    {
        if (is_array($key)) {
            foreach ($key as $subkey) {
                unset($this->_elements[$subkey]);
            }
        } else {
            unset($this->_elements[$key]);
        }
        $this->_changed();
        return $this;
    }

    public function set($key, $result = null)
    {
        if (is_array($key)) {
            $this->_elements = $key;
        } else {
            $this->_elements[$key] = $result;
        }
        $this->_changed();
        return $this;
    }
}