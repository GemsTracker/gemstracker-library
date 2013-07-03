<?php

/**
 * Copyright (c) 2013, MagnaFacta BV
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of MagnaFacta BV nor the
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
 * XmlRa class: pronouce "Ra" as "array" except on 19 september, then it is "ahrrray".
 *
 * @package    MUtil
 * @subpackage XmlRa
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 MagnaFacta BV
 * @license    New BSD License
 * @version    $Id: Ra.php 938 2012-09-11 14:00:57Z matijsdejong $
 */

/**
 * Basic iterator over the child elements of an XmlRa element.
 *
 * @package    MUtil
 * @subpackage XmlRa
 * @copyright  Copyright (c) 2013 MagnaFacta BV
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class MUtil_XmlRa_XmlRaIterator implements Iterator
{
    /**
     * The position of the current node
     *
     * @var int
     */
    private $_currentCount = -1;

    /**
     * The current xml item
     *
     * @var MUtil_XmlRa
     */
    private $_currentNode;

    /**
     * Function for filtering the output results.
     *
     * Signature must be: function(mixed $value) where $value is
     * a MUtil_XmlRa::_returnValue output and returns a boolean.
     *
     * @var callable
     */
    private $_filterFunction;

    /**
     * Function for transforming the outpur result.
     *
     * Signature must be: function(mixed $value) where $value is
     * a MUtil_XmlRa::_returnValue output.
     *
     * @var callable
     */
    private $_mapFunction;

    /**
     * The start, i.e. parent xml item
     *
     * @var MUtil_XmlRa
     */
    private $_startNode;

    /**
     * Initialize the iterator
     *
     * @param MUtil_XmlRa $xmlra
     */
    public function __construct(MUtil_XmlRa $xmlra)
    {
        $this->_startNode = $xmlra;
    }

    /**
     * Clean up the variables
     */
    public function __destruct()
    {
        unset($this->_currentCount, $this->_currentNode, $this->_startNode);
    }

    /**
     * Iterator implementation for current child item
     *
     * @return MUtil_XmlRa
     */
    public function current()
    {
        if ($this->_mapFunction) {
            return call_user_func($this->_mapFunction, $this->_currentNode);
        }
        return $this->_currentNode;
    }

    /**
     * Iterator implementation, returns the index of the current item
     *
     * @return int
     */
    public function key()
    {
        return $this->_currentCount;
    }

    /**
     * Move the iterator one item further
     *
     * @return void
     */
    public function next()
    {
        $this->_currentCount++;

        if (isset($this->_startNode[$this->_currentCount])) {
            $this->_currentNode = $this->_startNode[$this->_currentCount];
        } else {
            $this->_currentNode = null;
        }

        if ($this->_currentNode && $this->_filterFunction) {
            if (!call_user_func($this->_filterFunction, $this->_currentNode)) {
                $this->next();
            }
        }
    }

    /**
     * Restart this iterator
     *
     * @return void
     */
    public function rewind()
    {
        $this->_currentCount = -1;
        $this->next();
    }

    /**
     * Set function for filtering the output results.
     *
     * Signature must be: function(mixed $value) where $value is
     * a MUtil_XmlRa::_returnValue output and returns a boolean.
     *
     *
     * @param callable $function function()
     * @return \MUtil_XmlRa_XmlRaIterator (continuation pattern)
     */
    public function setFilterFunction($function)
    {
        $this->_filterFunction = $function;
        return $this;
    }

    /**
     * Set function for transforming the outpur result.
     *
     * Signature must be: function(mixed $value) where $value is
     * a MUtil_XmlRa::_returnValue output.
     *
     * @param callable $function function()
     * @return \MUtil_XmlRa_XmlRaIterator (continuation pattern)
     */
    public function setMapFunction($function)
    {
        $this->_mapFunction = $function;
        return $this;
    }

    /**
     * Is there a current item
     *
     * @return boolean
     */
    public function valid()
    {
        return null !== $this->_currentNode;
    }
}
