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
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Parent class for all array based attribute classes.
 *
 * Useable as is, using spaces as value separators by default.
 *
 * Parameter setting checks for the addition of special types,
 * just as MUtil_Html_HtmlElement.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_ArrayAttribute extends MUtil_Html_AttributeAbstract
    implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * String used to glue items together
     *
     * @var string
     */
    protected $_separator = ' ';

    /**
     * Specially treated types for a specific subclass
     *
     * @var array function name => class
     */
    protected $_specialTypes;

    /**
     * Specially treated types as used for each subclass
     *
     * @var array function name => class
     */
    private $_specialTypesDefault = array(
        'setRequest' => 'Zend_Controller_Request_Abstract',
        'setView'    => 'Zend_View',
        );

    protected $_values;

    public function __construct($name, $arg_array = null)
    {
        if ($this->_specialTypes) {
            $this->_specialTypes = $this->_specialTypes + $this->_specialTypesDefault;
        } else {
            $this->_specialTypes = $this->_specialTypesDefault;
        }

        $value = MUtil_Ra::args(func_get_args(), 1);

        parent::__construct($name, $value);
    }

    /**
     * Returns the rendered values of th earray elements
     *
     * @return array
     */
    protected function _getArrayRendered()
    {
        $results = array();

        $view = $this->getView();
        $renderer = MUtil_Html::getRenderer();
        foreach ($this->getArray() as $key => $value) {
            $results[$key] = $renderer->renderAny($view, $value);
        }

        return $results;
    }

    /**
     * Certain types must always be processed in a special manner.
     * This is independent of whether the type is passed as an
     * attribute or element content.
     *
     * @param $value mixed The value to check
     * @param $key optional The key used to add the value.
     * @return true|false True if nothing was done, false if the $value was processed.
     */
    private function _notSpecialType($value, $key = null)
    {
        if ($key) {
            if (method_exists($this, $fname = 'set' . $key)) {
                $this->$fname($value);

                return false;
            }
        }

        foreach ($this->_specialTypes as $method => $class) {
            if ($value instanceof $class) {
                $this->$method($value, $key);

                return false;
            }
        }

        return true;
    }

    protected function _setItem($key, $value)
    {
        if ($this->_notSpecialType($value, $key)) {
            if (null === $key) {
                $this->_values[] = $value;
            } else {
                $this->_values[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Add to the attribute
     *
     * @param mixed $keyOrValue The key if a second parameter is specified, otherwise a value
     * @param mixed $valueIfKey Optional, the value if a key is specified
     * @return \MUtil_Html_ArrayAttribute (continuation pattern)
     */
     public function add($keyOrValue, $valueIfKey = null)
    {
        // Key is specified first, but when no key it is the value.
        if (null == $valueIfKey) {
            $offset = null;
            $value  = $keyOrValue;
        } else {
            $offset = $keyOrValue;
            $value  = $valueIfKey;
        }

        if (is_array($value) || (($value instanceof Traversable) && (! $value instanceof MUtil_Lazy_LazyInterface))) {
            foreach ($value as $key => $item) {
                $this->_setItem($key, $item);
            }
        } else {
            $this->_setItem($offset, $value);
        }

        return $this;
    }

    public function count()
    {
        return count($this->_values);
    }

    /**
     * Get the scalar value of this attribute.
     *
     * @return string | int | null
     */
    public function get()
    {
        $results = array();

        foreach ($this->_getArrayRendered() as $key => $value) {
            $results[] = $this->getKeyValue($key, $value);
        }

        if ($results) {
            return trim(implode($this->getSeparator(), $results), $this->getSeparator());
        }

        return null;
    }

    /**
     * Function that allows subclasses to define their own
     * mechanism for redering the key/value combination.
     *
     * E.g. key=value instead of just the value.
     *
     * @param scalar $key
     * @param string $value Output escaped value
     * @return string
     */
    public function getKeyValue($key, $value)
    {
        return $value;
    }

    protected function getArray()
    {
        return (array) $this->_values;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_values);
    }

    public function getSeparator()
    {
        return $this->_separator;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_values);
    }

    public function offsetGet($offset)
    {
        return $this->_values[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->_values[] = $value;
        } else {
            $this->_values[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->_values[$offset]);
    }

    /**
     * Set the values of this attribute.
     *
     * @param mixed $keyOrValue The key if a second parameter is specified, otherwise a value
     * @param mixed $valueIfKey Optional, the value if a key is specified
     * @return \MUtil_Html_ArrayAttribute (continuation pattern)
     */
   public function set($keyOrValue, $valueIfKey = null)
    {
        if ($this->_values) {
            $this->_values = array();
        }

        return $this->add($keyOrValue, $valueIfKey);
    }

    public function setSeparator($separator)
    {
        $this->_separator = separator;
        return $this;
    }
}