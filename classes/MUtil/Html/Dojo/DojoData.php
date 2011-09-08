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
 */

/**
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Html_Dojo
 */

class MUtil_Html_Dojo_DojoData implements ArrayAccess, Countable, IteratorAggregate
{
    // public $script;

    protected $_dojoAttribs = array();

    public function __get($name)
    {
        if (array_key_exists($name, $this->_dojoAttribs)) {
            return $this->_dojoAttribs[$name];
        }
    }

    public function __isset ($name)
    {
        return array_key_exists($name, $this->_dojoAttribs);
    }

    public function __set($name, $value)
    {
        $this->_dojoAttribs[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this->_dojoAttribs[$name]);
    }

    /**
     * Cast a boolean to a string value
     *
     * @param  mixed $item
     * @param  string $key
     * @return void
     */
    protected function _castBoolToString(&$item, $key)
    {
        if (!is_bool($item)) {
            return;
        }
        $item = ($item) ? "true" : "false";
    }

    public function count()
    {
        return count($this->_dojoAttribs);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_dojoAttribs);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_dojoAttribs);
    }

    public function offsetGet($offset)
    {
        return $this->_dojoAttribs[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->_dojoAttribs[] = $value;
        } else {
            $this->_dojoAttribs[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->_dojoAttribs[$offset]);
    }

    public function processView(Zend_View_Abstract $view, MUtil_Html_Dojo_DojoElementAbstract $dojoElement)
    {
        if ($dojoElement->dijit) {
            $params = $this->_dojoAttribs;
            $params['dojoType'] = $dojoElement->dijit;

            array_walk_recursive($params, array($this, '_castBoolToString'));

            $dojo = $view->dojo();
            $dojo->enable();
            $dojo->requireModule($dojoElement->module);
            $dojo->setDijit($dojoElement->id, $params);
        }
    }
}
