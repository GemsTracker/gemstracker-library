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
 * Why get lazy:
 * 1 - You want to use a result later that is not yet known
 * 2 - You want the result repeated for a sequence of items
 * 3 - You want a result on some object but do not have the object yet
 *
 * What is a result you might want:
 * 1 - the object itself
 * 2 - a call to an object method
 * 3 - an object propery
 * 4 - an array object
 *
 * @package    MUtil
 * @subpackage Lazy
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Lazy
{
    public static function alternate($value1, $value2)
    {
        $args = func_get_args();
        return new MUtil_Lazy_Alternate($args);
    }

    public static function call($callable, $arg_array = null)
    {
        $args = array_slice(func_get_args(), 1);
        return new MUtil_Lazy_Call($callable, $args);
    }

    public static function comp($opLeft, $oper, $opRight)
    {
        $lambda = create_function('$a, $b', 'return $a ' . $oper . ' $b;');
        return new MUtil_Lazy_Call($lambda, array($opLeft, $opRight));
    }

    /**
     * The arguments are flattened lazily into one single array
     * and then joined together without separator.
     *
     * @param mixed $arg_array
     * @return MUtil_Lazy_Call
     */
    public static function concat($arg_array = null)
    {
        $args = func_get_args();

        return new MUtil_Lazy_Call('implode', array('', new MUtil_Lazy_Call('MUtil_Ra::flatten', $args)));
    }

    public static function first($args_array)
    {
        $args = func_get_args();

        // Last value first
        $result = array_shift($args);

        foreach ($args as $arg) {
            $result = new MUtil_Lazy_Call(array($result, 'if'), array($result, $arg));
        }
        return $result;
    }

    /**
     * Lazy if statement
     *
     * @param mixed $if The value tested during raise
     * @param mixed $then The value after raise when $if is true
     * @param mixed $else The value after raise when $if is false
     * @return MUtil_Lazy_Call
     */
    public static function iff($if, $then, $else = null)
    {
        return new MUtil_Lazy_Call(array($if, 'if'), array($then, $else));
    }

    /**
     * Lazy if statement
     *
     * @param mixed $if The value tested during raise
     * @param mixed $then The value after raise when $if is true
     * @param mixed $else The value after raise when $if is false
     * @return MUtil_Lazy_Call
     */
    public static function iif($if, $then, $else = null)
    {
        return new MUtil_Lazy_Call(array($if, 'if'), array($then, $else));
    }

    public static function L($var)
    {
        if (is_object($var)) {
            if ($var instanceof MUtil_Lazy_LazyInterface) {
                return $var;
            } elseif ($var instanceof MUtil_Lazy_Procrastinator) {
                return $var->toLazy();
            }

            return new MUtil_Lazy_ObjectWrap($var);

        } elseif(is_array($var)) {
            return new MUtil_Lazy_ArrayWrap($var);

        } else {
            return new MUtil_Lazy_LazyGet($var);
        }
    }

    /**
     * Return a lazy callable to an object
     *
     * @param Object $object
     * @param string $method Method of the object
     * @param mixed $arg_array1 Optional, first of any arguments to the call
     * @return MUtil_Lazy_Call
     */
    public static function method($object, $method, $arg_array1 = null)
    {
        $args = array_slice(func_get_args(), 2);
        return new MUtil_Lazy_Call(array($object, $method), $args);
    }

    public static function offsetGet($array, $offset)
    {
        return new MUtil_Lazy_ArrayAccessor($array, $offset);
    }

    public static function property($object, $property)
    {
        return new MUtil_Lazy_Property($object, $property);
    }

    /**
     * Raises a MUtil_Lazy_LazyInterface one level, but may still
     * return a MUtil_Lazy_LazyInterface.
     *
     * This function is usually used to perform a e.g. filter function on object that may e.g.
     * contain Repeater objects.
     *
     * @param mixed $object Usually an object of type MUtil_Lazy_LazyInterface
     * @param mixed $stack Optional variable stack for evaluation
     * @return mixed
     */
    public static function raise($object, $stack = null)
    {
        if ($object instanceof MUtil_Lazy_LazyInterface) {
            return $object->__toValue(self::toStack($stack, __FUNCTION__));
        } else {
            return $object;
        }
    }

    /**
     *
     * @param mixed $repeatable
     * @return MUtil_Lazy_RepeatableInterface
     */
    public static function repeat($repeatable)
    {
        if ($repeatable instanceof MUtil_Lazy_RepeatableInterface) {
            return $repeatable;
        }

        return new MUtil_Lazy_Repeatable($repeatable);
    }

    /**
     * Raises a MUtil_Lazy_LazyInterface until the return object is not a
     * MUtil_Lazy_LazyInterface object.
     *
     * @param mixed $object Usually an object of type MUtil_Lazy_LazyInterface
     * @param mixed $stack Optional variable stack for evaluation
     * @return mixed Something not lazy
     */
    public static function rise($object, $stack = null)
    {
        $stack = self::toStack($stack, __FUNCTION__);

        // Resolving when MUtil_Lazy_LazyInterface.
        while ($object instanceof MUtil_Lazy_LazyInterface) {
            $object = $object->__toValue($stack);
        }

        if ($object && is_array($object)) {
            foreach ($object as $key => $val) {
                $result[$key] = self::rise($val, $stack);
            }

            return $result;
        }

        return $object;
    }

    /**
     * Turns any input into a MUtil_Lazy_StackInterface object.
     *
     * @param mixed $stack Value to be turned into stack for evaluation
     * @param string A string describing where the stack was created.
     * @return MUtil_Lazy_StackInterface A usable stack
     */
    private static function toStack($stack, $source)
    {
        if ($stack instanceof MUtil_Lazy_StackInterface) {
            return $stack;
        }

        return new MUtil_Lazy_Stack_EmptyStack($source);
    }
}

if (defined('MUTIL_LAZY_FUNCTIONS')) {

    function iff($if, $then, $else = null)
    {
        return MUtil_Lazy::iff($if, $then, $else = null);
    }

    function iif($if, $then, $else = null)
    {
        return MUtil_Lazy::iif($if, $then, $else = null);
    }

    function L($var)
    {
        return MUtil_Lazy::L($var);
    }

    function lazy($var)
    {
        return MUtil_Lazy::L($var);
    }
 }