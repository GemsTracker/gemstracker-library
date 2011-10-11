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
 * Wrap lazyness around an object.
 *
 * Calls to methods and properties return a lazy object that will be
 * evaluated only when forced to a string value or when called using ->__toValue().
 *
 * <code>
 * $arrayObj = new ArrayObject();
 * $arrayObj->setFlags(ArrayObject::ARRAY_AS_PROPS);
 *
 * $arrayObj['a'] = 'old';
 *
 * $lazy_obj = new MUtil_Lazy_ObjectWrap($arrayObj);
 * $output = array($arrayObj->a, $lazy_obj->a, $arrayObj->count(), $lazy_obj->count());
 *
 * echo $output[0] . ' -> ' . $output[1] . ' | ' . $output[2] . ' -> ' . $output[3];
 * // Result old -> old | 1 -> 1
 *
 * $arrayObj->a = 'new';
 * $arrayObj[] = 2;
 *
 * echo $output[0] . ' -> ' . $output[1] . ' | ' . $output[2] . ' -> ' . $output[3];
 * // Result old -> new | 1 -> 2
 * </code>
 *
 * @package    MUtil
 * @subpackage Lazy
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Lazy_ObjectWrap extends MUtil_Lazy_LazyAbstract
{
    protected $_object;

    public function __construct($object)
    {
        $this->_object = $object;
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
        return $this->_object;
    }
}
