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
 * @subpackage Lazy
 */

class MUtil_Lazy_ParallelRepeater implements MUtil_Lazy_RepeatableInterface
{
    protected $repeatables = array();

    /**
     * Return a lazy version of the call
     *
     * @return MUtil_Lazy_LazyInterface
     */
    public function __call($name, array $arguments)
    {
        $results = array();
        foreach ($this->repeatables as $id => $repeater) {
            if ($result = call_user_func_array(array($repeater, $name), $arguments)) {
                $results[$id] = $result;
            }
        }
        return $results;
    }

    public function __construct($repeatable_args = null)
    {
        $args = MUtil_Ra::args(func_get_args());
        foreach ($args as $id => $repeatable) {
            if (null != $repeatable) {
                $this->addRepeater($repeatable, $id);
            }
        }
    }

    /**
     * Returns the current item. Starts the loop when needed.
     *
     * return mixed The current item
     */
    public function __current()
    {
        $results = array();
        foreach ($this->repeatables as $id => $repeater) {
            if ($result = $repeater->__curent()) {
                $results[$id] = $result;
            }
        }
        return $results;
    }

    /**
     * Return a lazy version of the property retrieval
     *
     * @return MUtil_Lazy_LazyInterface
     */
    public function __get($name)
    {
        $results = array();
        foreach ($this->repeatables as $id => $repeater) {
            if ($result = $repeater->$name) {
                $results[$id] = $result;
            }
        }
        return $results;
    }

    /**
     * Return the core data in the Repeatable in one go
     *
     * @return Iterator|array
     */
    public function __getRepeatable()
    {
        $results = array();
        foreach ($this->repeatables as $id => $repeater) {
            if ($result = $repeater->__getRepeatable()) {
                $results[$id] = $result;
            }
        }
        return $results;
    }

    /**
     * Returns the current item. Starts the loop when needed.
     *
     * return mixed The current item
     */
    public function __next()
    {
        $results = array();
        foreach ($this->repeatables as $id => $repeater) {
            if ($result = $repeater->__next()) {
                $results[$id] = $result;
            }
        }
        // MUtil_Echo::r($results, 'Parallel next');
        return $results;
    }

    /**
     * Return a lazy version of the property retrieval
     *
     * @return MUtil_Lazy_Property
     */
    public function __set($name, $value)
    {
        throw new MUtil_Lazy_LazyException('You cannot set a Lazy object.');
    }

    /**
     * The functions that starts the loop from the beginning
     *
     * @return mixed True if there is data.
     */
    public function __start()
    {
        $result = false;
        foreach ($this->repeatables as $repeater) {
            $result = $repeater->__start() || $result;
        }
        // MUtil_Echo::r(array_keys($this->repeatables), 'Parallel start');
        // MUtil_Echo::r($result, 'Parallel start');
        return $result;
    }

    public function addRepeater($repeater, $id = null)
    {
        if (! $repeater instanceof MUtil_Lazy_RepeatableInterface) {
            $repeater = new MUtil_Lazy_Repeatable($repeater);
        }
        if (null === $id) {
            $this->repeatables[] = $repeater;
        } else {
            $this->repeatables[$id] = $repeater;
        }

        return $repeater;
    }

    public function offsetExists($offset)
    {
        foreach ($this->repeatables as $repeater) {
            if ($repeater->offsetExists($offset)) {
                return true;
            }
        }

        return false;
    }

    public function offsetGet($offset)
    {
        $results = array();
        foreach ($this->repeatables as $id => $repeater) {
            if ($result = $repeater[$offset]) {
                $results[$id] = $result;
            }
        }
        return $results;
    }

    public function offsetSet($offset, $value)
    {
        throw new MUtil_Lazy_LazyException('You cannot set a Lazy object.');
    }

    public function offsetUnset($offset)
    {
        throw new MUtil_Lazy_LazyException('You cannot unset a Lazy object.');
    }
}