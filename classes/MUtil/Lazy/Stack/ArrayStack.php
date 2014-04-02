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
 * @subpackage Lazy_Stack
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ArrayStack.php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 * Get a simple array stack implemenation
 *
 * @package    MUtil
 * @subpackage ArrayStack
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Lazy_Stack_ArrayStack extends ArrayObject implements MUtil_Lazy_StackInterface
{
    /**
     * Should we throw an exception on a missing value?
     *
     * @var boolean
     */
    private $_throwOnMiss = false;

    /**
     * Returns a value for $name
     *
     * @param string $name A name indentifying a value in this stack.
     * @return A value for $name
     */
    public function lazyGet($name)
    {
        // MUtil_Echo::track($name, $this->offsetExists($name), $this->offsetGet($name), $this->getArrayCopy());
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }
        if ($this->_throwOnMiss) {
            throw new MUtil_Lazy_LazyException("No lazy stack variable defined for '$name' parameter.");
        }
        if (MUtil_Lazy::$verbose) {
            MUtil_Echo::header("No lazy stack variable defined for '$name' parameter.");
        }

        return null;
    }

    /**
     * Should we throw an exception on a missing value?
     *
     * @var boolean
     */

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
