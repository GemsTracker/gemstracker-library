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
 * @package    MUtil
 * @subpackage Iterator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ItemCallbackIterator.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace MUtil\Iterator;

/**
 * Calls a function for each item in an iterator before returning it
 *
 * @package    MUtil
 * @subpackage Iterator
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.7.2 28-okt-2015 17:34:25
 */
class ItemCallbackIterator implements \OuterIterator, \Countable
{
    /**
     *
     * @var callable
     */
    private $_callback;

    /**
     *
     * @var \Iterator
     */
    private $_iterator;

    /**
     *
     * @param \Traversable $iterator
     * @param Callable $callback
     */
    public function __construct(\Traversable $iterator, $callback)
    {
        $this->_iterator = $iterator;
        while ($this->_iterator instanceof \IteratorAggregate) {
            $this->_iterator = $this->_iterator->getIterator();
        }

        $this->_callback = $callback;
    }

    /**
     * Count elements of an object
     *
     * Rewinding version of count 
     *
     * @return int
     */
    public function count()
    {
        if ($this->_iterator instanceof \Countable) {
            return $this->_iterator->count();
        }

        $count = iterator_count($this->_iterator);
        $this->_iterator->rewind();
        return $count;
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        return call_user_func($this->_callback, $this->_iterator->current());
    }

    /**
     * Returns the inner iterator for the current entry.
     *
     * @return \Iterator
     */
    public function getInnerIterator()
    {
        return $this->_iterator;
    }

    /**
     * Return the key of the current element
     *
     * @return mixed
     */
    public function key()
    {
        return $this->_iterator->key();
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        $this->_iterator->next();
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->_iterator->rewind();
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_iterator->valid();
    }

}
