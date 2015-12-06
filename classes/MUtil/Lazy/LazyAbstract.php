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
 * The basic workhorse function for all Lazy objects.
 *
 * It returns a new Lazy object for every call, property get or array offsetGet
 * applied to the sub class Lazy object and implements the Lazy interface
 *
 * @package    MUtil
 * @subpackage Lazy
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Lazy_LazyAbstract implements \MUtil_Lazy_LazyInterface
{
    /**
     * Return a lazy version of the call
     *
     * @return \MUtil_Lazy_Call
     */
    public function __call($name, array $arguments)
    {
        return new \MUtil_Lazy_Call(array($this, $name), $arguments);
    }

    /**
     * Return a lazy version of the property retrieval
     *
     * @return \MUtil_Lazy_Property
     */
    public function __get($name)
    {
        // WARNING
        //
        // I thought about caching properties. Always useful when a property is
        // used a lot. However, this would mean that every LazyAbstract value
        // would have to store a cache, just in case this happens.
        //
        // All in all I concluded the overhead is probably not worth it, though I
        // did not test this.
        return new \MUtil_Lazy_Property($this, $name);
    }

    /**
     * You cannot set a Lazy object.
     *
     * throws \MUtil_Lazy_LazyException
     */
    public function __set($name, $value)
    {
        throw new \MUtil_Lazy_LazyException('You cannot set a Lazy object.');
    }

    /**
     * Every Lazy Interface implementation has to try to
     * change the result to a string or return an error
     * message as a string.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $stack = new \MUtil_Lazy_Stack_EmptyStack(__FUNCTION__);
            $value = $this;

            while ($value instanceof \MUtil_Lazy_LazyInterface) {
                $value = $this->__toValue($stack);
            }

            if (is_string($value)) {
                return $value;
            }

            // TODO: test
            if (is_object($value) && (! method_exists($value, '__toString'))) {
                return 'Object of type ' . get_class($value) . ' cannot be converted to string value.';
            }

            return (string) $value;

        } catch (\Exception $e) {
            // Cannot pass exception from __toString().
            //
            // So catch all exceptions and return error message.
            // Make sure to use @see get() if you do not want this to happen.
            return $e->getMessage();
        }
    }
    
    /**
     * Returns a lazy call where this object is the first parameter
     *
     * @param $callableOrObject object|callable
     * @param $nameOrArg1 optional method|mixed
     * @param $argn optional mixed
     * @return LazyInterface
     */
    public function call($callableOrObject, $nameOrArg1 = null, $argn = null)
    {
        $args = func_get_args();
        $callable = array_shift($args);

        if (is_callable($callable)) {
            // Put $this as the first parameter
            array_unshift($args, $this);

        } elseif (is_object($callable)) {
            // Second argument should be string that is function name
            $callable = array($callable, array_shift($args));

            // Put $this as the first parameter
            array_unshift($args, $this);

        } else {
            // First argument should be method of this object.
            $callable = array($this, $callable);
        }

        return new \MUtil_Lazy_Call($callable, $args);
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        return new \MUtil_Lazy_ArrayAccessor($this, $offset);
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