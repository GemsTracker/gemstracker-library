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
 * @subpackage Lazy
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    MUtil
 * @subpackage Lazy
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Lazy_Repeatable implements \MUtil_Lazy_RepeatableInterface
{
    /**
     * When true array, otherwise interator
     *
     * @var boolean
     */
    protected $_arrayMode;

    /**
     * The current item as changing for each iteration
     *
     * @var mixed
     */
    protected $_currentItem;

    /**
     * Static lazy object used to for all Lazy call's and get's for data
     *
     * @var \MUtil_Lazy_Call
     */
    protected $_currentLazy;

    /**
     *
     * @var mixed The actual array or \Iterator or \IteratorAggregate or other item to repeat
     */
    protected $_repeatable;

    /**
     *
     * @var mixed The array or \Iterator that repeats
     */
    protected $_repeater = null;

    /**
     * Return a lazy version of the call
     *
     * @return \MUtil_Lazy_Call
     */
    public function __call($name, array $arguments)
    {
        return new \MUtil_Lazy_Call(array($this->_currentLazy, $name), $arguments);
    }

    /**
     *
     * @param mixed $repeatable The array or \Iterator or \IteratorAggregate or other item to repeat
     */
    public function __construct($repeatable)
    {
        $this->_currentLazy = new \MUtil_Lazy_Call(array($this, '__current'));
        $this->_repeatable  = $repeatable;
    }

    /**
     * Returns the current item. Starts the loop when needed.
     *
     * return mixed The current item
     */
    public function __current()
    {
        if (null === $this->_currentItem) {
            return $this->__next();
        }

        return $this->_currentItem;
    }

    /**
     * Return a lazy version of the property retrieval
     *
     * @return \MUtil_Lazy_Property
     */
    public function __get($name)
    {
        return new \MUtil_Lazy_Property($this->_currentLazy, $name);
    }

    /**
     * Returns the repeating data set.
     *
     * return \Traversable|array
     */
    public function __getRepeatable()
    {
        $value = $this->_repeatable;
        while ($value instanceof \MUtil_Lazy_LazyInterface) {
            $value = $value->__toValue(array());
        }

        return $value;
    }

    /**
     * Returns the current item. Starts the loop when needed.
     *
     * return mixed The current item
     */
    public function __next()
    {
        if (null === $this->_repeater) {
            if (! $this->__start()) {
                return null;
            }
        }

        if ($this->_arrayMode) {
            if (null === $this->_currentItem) {
                $this->_currentItem = reset($this->_repeater);
            } else {
                $this->_currentItem = next($this->_repeater);
            }
        } else {
            if (null === $this->_currentItem) {
                $this->_repeater->rewind();
            } else {
                $this->_repeater->next();
            }
            if ($this->_repeater->valid()) {
                $this->_currentItem = $this->_repeater->current();
            } else {
                $this->_currentItem = false;
            }
        }

        if (is_array($this->_currentItem)) {
            // Make the array elements accessible as properties
            $this->_currentItem = new \ArrayObject($this->_currentItem, \ArrayObject::ARRAY_AS_PROPS);
        }

        return $this->_currentItem;
    }

    /**
     * Return a lazy version of the property retrieval
     *
     * @return \MUtil_Lazy_Property
     */
    public function __set($name, $value)
    {
        throw new \MUtil_Lazy_LazyException('You cannot set a Lazy object.');
    }

    /**
     * The functions that starts the loop from the beginning
     *
     * @return mixed True if there is data.
     */
    public function __start()
    {
        $value = $this->__getRepeatable();

        // \MUtil_Echo::r($value);

        if (is_array($value)) {
            $this->_repeater  = $value;
            $this->_arrayMode = true;

        } elseif ($value instanceof \Iterator) {
            $this->_repeater  = $value;
            $this->_arrayMode = false;

        } elseif ($value instanceof \IteratorAggregate) {
            $this->_repeater = $value->getIterator();
            while ($this->_repeater instanceof \IteratorAggregate) {
                // Won't be used often, but hey! better be sure
                $this->_repeater = $this->_repeater->getIterator();
            }
            $this->_arrayMode = false;

        } else {
            $this->_repeater  = array($value);
            $this->_arrayMode = true;
        }

        $this->_currentItem = null;

        if ($this->_arrayMode) {
            return (boolean) count($this->_repeater);

        } else {
            return $this->_repeater->valid();
        }
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        return new \MUtil_Lazy_ArrayAccessor($this->_currentLazy, $offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \MUtil_Lazy_LazyException('You cannot set a Lazy object.');
    }

    public function offsetUnset($offset)
    {
        throw new \MUtil_Lazy_LazyException('You cannot unset a Lazy object.');
    }
}
