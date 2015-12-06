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
class MUtil_Lazy_Property extends \MUtil_Lazy_LazyAbstract
{
    private $_object;
    private $_property;

    public function __construct($object, $property)
    {
        $this->_object = $object;
        $this->_property = $property;
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
        $object = $this->_object;
        while ($object instanceof \MUtil_Lazy_LazyInterface) {
            $object = $object->__toValue($stack);
        }

        $property = $this->_property;
        while ($property instanceof \MUtil_Lazy_LazyInterface) {
            $property = $property->__toValue($stack);
        }

        if (is_object($object)) {
            if (isset($object->$property)) {
                return $object->$property;
            } /* else {
               \MUtil_Echo::r(get_class($object), 'NO PROPERTY ' . $property);
            } // */
        }  /* else {
            \MUtil_Echo::r($object, 'NO OBJECT ' . $property);
        } // */
    }
}
