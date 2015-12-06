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
 * A lazy object for when you want to access an array but either the array
 * itself and/or the offset is a lazy object.
 *
 * @package    MUtil
 * @subpackage Lazy
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */

class MUtil_Lazy_ArrayAccessor extends \MUtil_Lazy_LazyAbstract
{
    /**
     *
     * @var mixed Possibly lazy array
     */
    private $_array;

    /**
     *
     * @var mixed Possibly lazy offset
     */
    private $_offset;

    /**
     *
     * @param mixed Possibly lazy array
     * @param mixed Possibly lazy offset
     */
    public function __construct($array, $offset)
    {
        $this->_array  = $array;
        $this->_offset = $offset;
    }

    /**
    * The functions that fixes and returns a value.
    *
    * Be warned: this function may return a lazy value.
    *
    * @param \MUtil_Lazy_StackInterface $stack A \MUtil_Lazy_StackInterface object providing variable data
    * @return mixed
    */
    public function __toValue(\MUtil_Lazy_StackInterface $stack)
    {
        $array  = $this->_array;
        $offset = $this->_offset;

        while ($offset instanceof \MUtil_Lazy_LazyInterface) {
            $offset = $offset->__toValue($stack);
        }
        while ($array instanceof \MUtil_Lazy_LazyInterface) {
            $array = $array->__toValue($stack);
        }

        if (\MUtil_Lazy::$verbose) {
            \MUtil_Echo::header('Lazy offset get for offset: <em>' . $offset . '</em>');
            \MUtil_Echo::classToName($array);
        }

        if (null === $offset) {
            if (isset($array[''])) {
                $value = $array[''];
            } else {
                $value = null;
            }
        } elseif (is_array($offset)) {
            // When the offset is itself an array, return an
            // array of values applied to this offset.
            $value = array();
            foreach (\MUtil_Lazy::riseRa($offset, $stack) as $key => $val) {
                if (isset($array[$val])) {
                    $value[$key] = $val;
                }
            }
        } elseif (isset($array[$offset])) {
            $value = $array[$offset];
        }

        while ($value instanceof \MUtil_Lazy_LazyInterface) {
            $value = $value->__toValue($stack);
        }
        if (is_array($value)) {
            $value = \MUtil_Lazy::riseRa($value, $stack);
        }
        return $value;
    }

}
