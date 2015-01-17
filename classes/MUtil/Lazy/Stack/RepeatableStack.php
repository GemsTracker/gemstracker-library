<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @subpackage Lazy_Stack
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RepeatableStack.php $
 */

namespace MUtil\Lazy\Stack;

/**
 *
 *
 * @package    MUtil
 * @subpackage Lazy_Stack
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.4 17-jan-2015 14:30:59
 */
class RepeatableStack implements \MUtil_Lazy_StackInterface
{
    /**
     * The object to get properties from
     *
     * @var \MUtil_Lazy_Repeatable
     */
    protected $_object;

    /**
     *
     * @param \MUtil_Lazy_Repeatable $object
     */
    public function __construct(\MUtil_Lazy_Repeatable $object)
    {
        $this->_object = $object;
    }

    /**
     * Returns a value for $name
     *
     * @param string $name A name indentifying a value in this stack.
     * @return A value for $name
     */
    public function lazyGet($name)
    {
        // MUtil_Echo::track($name, isset($this->_object->$name), MUtil_Lazy::rise($this->_object->$name), $this->_object->getLazyValue($name));
        return \MUtil_Lazy::rise($this->_object->__get($name));
    }

    /**
     * Set this stack to throw an exception
     *
     * @param mixed $throw boolean
     * @return \MUtil_ArrayStack (continuation pattern_
     */
    public function setThrowOnMiss($throw = true)
    {
        $this->_throwOnMiss = $throw;
        return $this;
    }
}
