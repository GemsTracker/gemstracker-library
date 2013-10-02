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
 * The Ra class contains static array processing functions that are used to give PHP/Zend some
 * Python and Haskell like parameter processing functionality.
 *
 * Ra class: pronouce "array" except on 19 september, then it is "ahrrray".
 *
 * The functions are:<ol>
 * <li>MUtil_Ra::args    => Python faking</li>
 * <li>MUtil_Ra::flatten => flatten an array renumbering keys</li>
 * <li>MUtil_Ra::pairs   => the parameters represent name => value pairs</li></ol>
 *
 * @package    MUtil
 * @subpackage Ra
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Ra
{
    const RELAXED = 0;
    const STRICT  = 1;

    private static $_initialToArrayList = array(
        'ArrayObject'                      => 'getArrayCopy',
        'MUtil_Lazy_LazyInterface'         => 'MUtil_Lazy::rise',
        'MUtil_Lazy_RepeatableInterface'   => '__current',
        'Zend_Config'                      => 'toArray',
        'Zend_Controller_Request_Abstract' => 'getParams',
        'Zend_Db_Table_Row_Abstract'       => 'toArray',
        'Zend_Db_Table_Rowset_Abstract'    => 'toArray',
        'Zend_Date'                        => 'toArray',


        // Last function to try
        'Traversable' => 'iterator_to_array'
        );

    private static $_toArrayConverter;

    public static $toArrayConverterLoopLimit = 10;

    /**
     * The args() function makes position independent argument passing possible.
     *
     * FLATTENING THE INPUT
     *
     * The input is usually just the output of func_get_args(). This array is flattened
     * like this:
     * <code>
     * MUtil_Ra::args(
     *  array(0 => array(0 => 'f', 1 => array('o' => '0', 0 => 'b')), 1 => array('a' => array('r' => 'r'))));
     * =>
     *  array(0 => 'f', 'o' => '0', 1 => 'b', 'a' => array('r' => 'r'))
     * </code>
     * Watch the last array() statement: when an item is an array has a numeric key it is flattened,
     * when the item is an array but has a string key it is kept as is.
     *
     *
     * If you assign a name twice, the last value is used:
     * <code>
     * MUtil_Ra::args(
     *  array(array('a' => 'b'), array('a' => 'c'));
     * =>
     *  array('a' => 'c');
     * </code>
     *
     * SKIPPING ARGUMENTS
     *
     * When the first X arguments passed to a function are fixed, you can skip the flattening of the
     * items by specifiying a numeric $skipOrName value.
     * <code>
     * MUtil_Ra::args(
     *  array(0 => array(0 => 'f', 1 => array('o' => '0', 0 => 'b')), 1 => array('a' => array('r' => 'r'))),
     *  1);
     * =>
     * array('a' => array('r' => 'r'))
     * </code>
     *
     * NAMING ARGUMENTS
     *
     * When you want flexibility in passing arguments a better option than using fixed arguments
     * is using named arguments. With array('foo', 'bar') as $skipOrName parameter the previous
     * example output becomes:
     * <code>
     * MUtil_Ra::args(
     *  array(0 => array(0 => 'f', 1 => array('o' => '0', 0 => 'b')), 1 => array('a' => array('r' => 'r'))),
     *  array('foo', 'bar'));
     * =>
     *  array('foo' => 'f', 'o' => '0', 'bar' => 'b', 'a' => array('r' => 'r'))
     * </code>
     * The names are assigned only to numeric array elements and are assigned depth first. On the
     * other hand the args function does not care about the output array positions so the actual
     * output has a different order:
     * <code>
     * array('o' => '0', 'a' => array('r' => 'r'), 'foo' => 'f', 'bar' => 'b')
     * </code>
     *
     * Using the $skipOrName array('a', 'c', 'o') the same example returns:
     * <code>
     * MUtil_Ra::args(
     *  array(0 => array(0 => 'f', 1 => array('o' => '0', 0 => 'b')), 1 => array('a' => array('r' => 'r'))),
     *  array('a', 'c', 'o'));
     * =>
     *  array('c' => 'f', 'o' => '0', 1 => 'b', 'a' => array('r' => 'r'))
     * </code>
     * As both 'a' and 'o' exist already they are not reassigned, independent of the position
     * where they were defined in the original input.
     *
     * As 'c' is the only parameter not assigned it is assigned to the first numeric parameter
     * value.
     *
     *
     * TYPING NAMED ARGUMENTS
     *
     * args() also supports class-typed arguments. The $skipOrName parameter then uses the
     * name of the parameter as the array key and the class or interface name as the value:
     * <code>
     * MUtil_Ra::args(
     *  array(new Zend_DB_Select(), array('a', 'b', new Zend_Foo()))),
     *  array('foo' => 'Zend_Foo', 'bar', 'foobar' => 'Zend_Db_Select'));
     * =>
     *  array('foo' => new Zend_Foo(), 'bar' => 'a', 'foobar' => new Zend_Db_Select(), 0 => 'b');
     * </code>
     * Of course the actual order is not important, as is the actual number assigned to the last
     * parameter value.
     *
     * Assignment is depth first. Mind you, assignment is name first, instanceof second as long
     * as the $mode = MUtil_Ra::RELAXED. If the name does not correspond to the specified type
     * it is still assigned. Also the assignment order is again depth first:
     * <code>
     * MUtil_Ra::args(
     *  array(new Zend_Foo(1), array('a', 'b', new Zend_Foo(2)), array('foobar' => 'x')),
     *  array('foo' => 'Zend_Foo', 'bar' => 'Zend_Foo', 'foobar' => 'Zend_Db_Select'));
     * =>
     *  array('foo' => new Zend_Foo(1), 'bar' => new Zend_Foo(2), 'foobar' => 'x', 0 => 'a', 1 => 'b');
     * </code>
     *
     *
     * OTHER TYPE OPTIONS
     *
     * Apart from class names you can also use is_*() functions to test for a type. E.g. is_string() or
     * is_boolean(). You can also write your own is_whatever() function.
     *
     * You can assign multiple types as an array. The array will search all the arguments first for the
     * first type, then the second, etc..
     *
     * The next example will get the first passed compatible Zend element (which your code can use to get
     * the id of) or else the first available string parameter.
     * <code>
     *  array('id' => array('Zend_Form_Element', ''Zend_Form_DisplayGroup', 'Zend_Form', 'is_string'));
     * </code>
     *
     * ADDING DEFAULTS
     *
     * func_get_args() returns the passed arguments without any specified default values. When your
     * function has defaults you have to add them as an 'name' => 'value' array as the third argument.
     *
     * So the example:
     * <code>
     * $args = MUtil_Ra::args(func_get_args(), array('class1',  'class2'), array('class1' => 'odd',  'class2' => 'even'));
     * </code>
     * Will return this for the inputs:
     * <code>
     * array() = array('class1' => 'odd',  'class2' => 'even');
     * array(null) = array('class1' => null,  'class2' => 'even');
     * array('r1', 'r2') = array('class1' => 'r1',  'class2' => 'r2');
     * array('r1', 'r2', 'r3') = array('class1' => 'r1',  'class2' => 'r2', 0 => 'r3');
     * </code>
     *
     * @param array $args       An array containing the arguments to process (usually func_get_args() output)
     * @param mixed $skipOrName If numeric the number of arguments in $args to leave alone, otherwise the names of numbered
     *                          elements. Class names can also be specified.
     * @param array $defaults   An array of argument name => default_value pairs.
     * @param boolean $mode     The $skipOrName types are only used as hints or must be strictly adhered to.
     * @return array Flattened array containing the arguments.
     */
    public static function args(array $args, $skipOrName = 0, $defaults = array(), $mode = self::RELAXED)
    {
        if ($skipOrName) {
            if (is_integer($skipOrName)) {
                // TEST RESULT
                //
                // As expected array_slice() is an order of magnitude faster than
                // using array_shift() repeatedly. It is even faster to use
                // array_slice() repeatedly than to use array_shift().
                $args = array_slice($args, $skipOrName);

            } else {
                $laxTypes = (self::RELAXED === $mode);

                if (is_array($skipOrName)) {
                    $names = $skipOrName;
                } else {
                    $names = array($skipOrName);
                }

                // Assign numbered array items to the names specified (if any)
                foreach ($names as $n1 => $n2) {
                    // The current element is always the first in the args array,
                    // as long as the corresponding key is numeric.
                    //
                    // When the "supply" of numeric keys is finished we have processed
                    // all the keys that were passed.
                    reset($args);
                    $current = key($args);
                    if (! is_int($current)) {
                        break;
                    }

                    // The parameter type
                    if (is_int($n1)) {
                        $ntype = null;
                        $name  = $n2;
                    } else {
                        $ntype = $n2;
                        $name  = $n1;
                    }

                    if (is_array($ntype)) { // Algebraic type!
                        foreach ($ntype as $stype) {
                            if (self::argsSearchKey($name, $stype, $args, $laxTypes)) {
                                break;
                            }
                            $ntype = $stype;  // Allows using null as a last type
                        }
                    } else {
                        self::argsSearchKey($name, $ntype, $args, $laxTypes);
                    }

                    // 1: Not yet set && 2: lax types used
                    if ((! isset($args[$name])) &&
                        ($laxTypes || (null === $ntype)) &&
                        isset($args[$current])) {

                        $args[$name] = $args[$current];
                        unset($args[$current]);
                    }
                }
            }
        }

        $output = array();

        if ($args) {
            // flatten out all sub-arrays with a numeric key
            self::argsRenumber($args, $output);
        }

        if ($defaults) {
            // Add array with default values/
            $output = $output + $defaults;
        }

        return $output;
    }

    private static function argsRenumber(array $input, array &$output)
    {
        foreach ($input as $key => $value) {
            if (is_int($key)) {
                if (is_array($value)) {
                    self::argsRenumber($value, $output);
                } else {
                    $output[] = $value;
                }
            } else {
                $output[$key] = $value;
            }
        }
    }

    private static function argsSearchKey($needle, $needleType, array &$haystack, $laxTypes)
    {
        foreach ($haystack as $key => $value) {
            if (is_int($key)) {
                // Check higher up in array
                if (is_array($value) && self::argsSearchKey($needle, $needleType, $value, $laxTypes)) {
                    // Give the value the correct array key
                    //
                    // This bubbles the array value up to the current $haystack level
                    $haystack[$needle] = $value[$needle];
                    unset($haystack[$key][$needle]);

                    // Remove array if no longer in use
                    if (count($haystack[$key]) == 0) {
                        unset($haystack[$key]);
                    }
                    return true;
                }
            } elseif ($laxTypes && ($needle == $key)) {
                return true;
            }
            if ($needleType) {
                // Check for type os check for is_etc... function
                $isType = ($value instanceof $needleType) ||
                    ((substr($needleType, 0, 3) == 'is_') && function_exists($needleType) && $needleType($value));

                if ($isType) {
                    // Give the value the correct array key
                    $haystack[$needle] = $value;
                    unset($haystack[$key]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Put braces around the array keys.
     *
     * @param array $input
     * @param string $left Brace string, e.g. '%', '{', '[', '"'
     * @param string $right Optional, when emptu same as left.
     * @return array Array with the same values but braces around the keys
     */
    public static function braceKeys(array $input, $left, $right = null)
    {
        if (null === $right) {
            $right = $left;
        }
        $results = array();

        foreach ($input as $key => $value) {
            $results[$left . $key . $right] = $value;
        }

        return $results;
    }

    /**
     * Extracts a column from a nested array of values, maintaining index association.
     *
     * The default RELAXED mode will return only values where these exist and are not null.
     * STRICT mode will return all values plus null for all keys in $input.
     *
     * @param string $index Index of the column to extract
     * @param array $input A nested array from which we extract a column
     * @param int $mode STRICT means missing values are returned as 'null'
     * @return array An array containing the requested column
     */
    public static function column($index, array $input, $mode = self::RELAXED)
    {
        $all = (self::STRICT === $mode);
        $results = array();
        foreach ($input as $key => $row) {
            if (isset($row[$index])) {
                $results[$key] = $row[$index];
            } elseif ($all) {
                $results[$key] = null;
            }
        }
        return $results;
    }

    /**
     * Search through nested array for the first row
     * that contains the whole $keys array.
     *
     * @param array $data A nested array
     * @param array $keys index => value
     * @return mixed Key from data if found or null otherwise
     */
    public static function findKeys(array $data, array $keys)
    {
        if (count($keys) == 1) {
            $value = reset($keys);
            $key   = key($keys);
            foreach ($data as $index => $row) {
                if (isset($row[$key]) && ($row[$key] == $value)) {
                    return $index;
                }
            }
        } else {
            foreach ($data as $index => $row) {
                $found = true;
                foreach ($keys as $key => $value) {
                    if ((!isset($row[$key])) || ($row[$key] !== $value)) {
                        $found = false;
                        break;
                    }
                }
                if ($found) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Flattens an array recursively.
     *
     * All keys are removed and items are added depth-first to the output array.
     *
     * @param array $input
     * @return array
     */
    public static function flatten(array $input)
    {
        $output = array();
        self::flattenSub($input, $output);
        return $output;
    }

    private static function flattenSub(array $input, array &$output)
    {
        foreach ($input as $value) {
            if (is_array($value)) {
                self::flattenSub($value, $output);
            } else {
                $output[] = $value;
            }
        }
    }

    public static function getToArrayConverter()
    {
        if (! self::$_toArrayConverter) {
            self::setToArrayConverter(self::$_initialToArrayList);
        }

        return self::$_toArrayConverter;
    }

    /**
     * Returns true if the $object either is an array or can be converted to an array.
     *
     * @param mixed $object
     * @return boolean
     */
    public static function is($object)
    {
        if (is_array($object)) {
            return true;
        }

        return self::getToArrayConverter()->get($object);
    }

    /**
     * Test whether the value is scalar or an array containing
     * scalars or scalar arrays.
     *
     * @param mixed $value
     * @return boolean
     */
    public static function isScalar($value)
    {
        if (null === $value) {
            return true;

        }

        if (is_array($value)) {
            foreach($value as $sub_value) {
                if (! self::isScalar($sub_value)) {
                    return false;
                }
            }
            return true;

        }

        return is_scalar($value);
    }

    /**
     * This functions splits an array into two arrays, one containing
     * the integer keys and one containing the string keys and returns
     * an array containing first the integer key array and then the
     * string key array.
     *
     * @param array $arg The input array
     * @return array array(integer_keys, string_keys)
     */
    public static function keySplit(array $arg)
    {
        $nums    = array();
        $strings = array();

        foreach ($arg as $key => $value) {
            if (is_integer($key)) {
                $nums[$key] = $value;
            } else {
                $strings[$key] = $value;
            }
        }

        return array($nums, $strings);
    }

    /**
     * Returns a sequential array of all non-scalar values in $value,
     * recursing through any nested arrays.
     *
     * @param mixed $value
     * @return array
     */
    public static function nonScalars($value)
    {
        $output = array();

        self::nonScalarFinder($value, $output);

        return $output;
    }

    /**
     * Add's all the non-scaler values in $value to output/
     *
     * @param mixed $value
     * @param array $output
     * @return void $output is the real result
     */
    private static function nonScalarFinder($value, array &$output)
    {
        if (null === $value) {
            return;
        }

        if (is_array($value)) {
            foreach($value as $sub_value) {
                self::nonScalarFinder($sub_value, $output);
            }
        } elseif (! is_scalar($value)) {
            $output[] = $value;
        }
    }

    /**
     * A function that transforms an array in the form key1, value1, key2, value2 into array(key1 => value1, key2 => value2).
     *
     * When the $args array contains only a single sub array, then this value is assumed to be the return value. This allows
     * functions using pairs() to process their values to accept both:
     *    f1('key1', 'value1', 'key2', 'value2')
     *  and:
     *    $a = array('key1' => 'value1', 'key2' => 'value2');
     *    f1($a)
     *
     * @param array $args Usually func_get_args() from the calling function.
     * @param int $skip The number of items to skip before stating processing
     * @return array
     */
    public static function pairs(array $args, $skip = 0)
    {
        $count = count($args);

        // When only one array element was passed that is the return value.
        if ($count == $skip + 1) {
            $arg = $args[$skip];
            if (is_array($arg)) {
                return $arg;
            }
            if (is_object($arg)) {
                return self::to($arg);
            }
        }

        // When odd number of items: add null value at end to even out values.
        if (1 == (($count - $skip) % 2)) {
            $args[] = null;
        }

        $pairs = array();
        for ($i = $skip; $i < $count; $i += 2) {
            $pairs[$args[$i]] = $args[$i + 1];
        }

        return $pairs;
    }

    public static function setToArrayConverter($converter)
    {
        if ($converter instanceof MUtil_Util_ClassList) {
            self::$_toArrayConverter = $converter;
        } elseif (is_array($converter)) {
            self::$_toArrayConverter = new MUtil_Util_ClassList($converter);
        }
    }

    public static function to($object, $mode = self::STRICT)
    {
        // Allow type chaining => Lazy => Config => array
        $i = 0;
        $converter = self::getToArrayConverter();
        while (is_object($object) && ($function = $converter->get($object))) {
            $object = call_user_func($function, $object);

            if (++$i > self::$toArrayConverterLoopLimit) {
                throw new Zend_Exception('Object of type ' . get_class($object) . ' with loops in array conversion.');
            }
        }

        // MUtil_Echo::r($object);
        if (is_array($object)) {
            return $object;
        }

        if (self::STRICT === $mode) {
            if (get_class($object)) {
                throw new Zend_Exception('Object of type ' . get_class($object) . ' could not be converted to array.');
            } else {
                throw new Zend_Exception('Item of type ' . gettype($object) . ' could not be converted to array.');
            }
        }

        return array();
    }
}

function is_ra_array($value)
{
    return MUtil_Ra::is($value);
}
