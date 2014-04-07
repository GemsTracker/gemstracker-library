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
 *
 * @package    MUtil
 * @subpackage Lazy
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Lazy_Call extends MUtil_Lazy_LazyAbstract
{
    private $_callable;
    private $_params;

    public function __construct($callable, array $params = array())
    {
        $this->_callable = $callable;
        $this->_params   = $params;
    }

    /**
     * The functions that returns the value.
     *
     * Returning an instance of MUtil_Lazy_LazyInterface is allowed.
     *
     * @param MUtil_Lazy_StackInterface $stack A MUtil_Lazy_StackInterface object providing variable data
     * @return mixed
     */
    protected function _getLazyValue(MUtil_Lazy_StackInterface $stack)
    {
        $params = $this->_params;

        if (is_array($this->_callable)) {
            list($object, $method) = $this->_callable;
            while ($object instanceof MUtil_Lazy_LazyInterface) {
                $object = $object->__toValue($stack);
            }
            $callable = array($object, $method);

            if (! (is_object($object) && (method_exists($object, $method) || method_exists($object, '__call')))) {
                if (function_exists($method)) {
                    // Add the object as the first parameter
                    array_unshift($params, $object);
                    $callable = $method;

                } elseif ('if' === strtolower($method)) {
                    if ($object) {
                        return isset($params[0]) ? $params[0] : null;
                    } else {
                        return isset($params[1]) ? $params[1] : null;
                    }
                }
            }

        } else {
            $method   = $this->_callable; // For error message
            $callable = $this->_callable;
        }

        if (is_callable($callable)) {
            $params = MUtil_Lazy::rise($params, $stack);
            /* if ('_' == $method) {
                MUtil_Echo::r($params);
            } */

            return call_user_func_array($callable, $params);
        }

        throw new MUtil_Lazy_LazyException('Lazy execution exception! "' . $method . '" is not a valid callable.');
    }
}
