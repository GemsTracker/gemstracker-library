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
 * @subpackage Ra
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Magic object for enabling multiple objects to be treated as one object.
 *
 * This objects passed all object manipulations on to the array of objects contained in it
 * allowing you to handle multiple objects as if they were a single object.
 *
 * No the creators of object orientation do not tumble in their graves. First most of them
 * all still alive and second this is acutally just an extension of generics. PHP does
 * not have generics at this time and those languages that do, do not support this type of
 * generics, but there is no mathematical reason why this should not work.
 *
 * @package    MUtil
 * @subpackage Ra
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.0
 */
class MUtil_MultiWrapper implements \ArrayAccess
{
    /**
     * The items that will be treated as one.
     *
     * @var array
     */
    protected $_array;

    /**
     * The class name used to create new class instances for function call results
     *
     * @var string
     */
    protected $_class = __CLASS__;

    /**
     * Pass on functions calls ans return the results as a new Wrapper
     *
     * @param string $name
     * @param array $arguments
     * @return \MUtil_MultiWrapper
     */
    public function __call($name, array $arguments)
    {
        $result = array();

        foreach ($this->_array as $key => $obj) {
            $result[$key] = call_user_func_array(array($obj, $name), $arguments);
        }

        return new $this->_class($result);
    }

    /**
     *
     * @param array|Traversable $array
     */
    public function __construct($array)
    {
        $this->_array = $array;
    }

    public function __get($name)
    {
        $result = array();

        foreach ($this->_array as $key => $obj) {
            // Return only for those that have the property
            if (isset($obj->$name)) {
                $result[$key] = $obj->$name;
            }
        }

        return $result;
    }

    public function __isset($name)
    {
        // Return on first one found
        foreach ($this->_array as $key => $obj) {
            if (isset($obj->$name)) {
                return true;
            }
        }

        return false;
    }

    public function __set($name, $value)
    {
        foreach ($this->_array as $obj) {
            $obj->$name = $value;
        }
    }

    public function __unset($name)
    {
        foreach ($this->_array as $obj) {
            unset($obj->$name);
        }
    }

    public function offsetExists($offset)
    {
        // Return on first one found
        foreach ($this->_array as $key => $obj) {
            if (array_key_exists($offset, $obj)) {
                return true;
            }
        }

        return false;
    }

    public function offsetGet($offset)
    {
        $result = array();

        foreach ($this->_array as $key => $obj) {
            // Return only for those that have the item
            if (array_key_exists($offset, $obj)) {
                $result[$key] = $obj[$offset];
            }
        }

        return $result;
    }

    public function offsetSet($offset, $value)
    {
        foreach ($this->_array as $obj) {
            $obj[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        foreach ($this->_array as $obj) {
            unset($obj[$offset]);
        }
    }
}