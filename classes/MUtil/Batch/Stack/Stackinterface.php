<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Batch
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Stackinterface.php 2394 2015-01-15 14:30:10Z matijsdejong $
 */

/**
 * Interface for external storage of stack
 *
 * @package    MUtil
 * @subpackage Batch
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
interface MUtil_Batch_Stack_Stackinterface
{
    /**
     * Add an execution step to the command stack.
     *
     * @param string $method Name of a method of this object
     * @param array  $params Array with scalars, as many parameters as needed allowed
     * @return boolean When true, increment the number of commands, otherwise the command existed
     */
    public function addStep($method, array $params);

    /**
     * Return true when there still exist unexecuted commands
     *
     * @return boolean
     */
    public function hasNext();

    /**
     * Return the next command
     *
     * @return array 0 => command, 1 => params
     */
    public function getNext();

    /**
     * Run the next command
     *
     * @return void
     */
    public function gotoNext();

    /**
     * Register a class as being allowed in the stack
     *
     * @param string $className
     * @return \MUtil_Batch_Stack_StackInterface (continuation pattern)
     */
    public function registerAllowedClass($className);

    /**
     * Reset the stack
     *
     * @return \MUtil_Batch_Stack_Stackinterface (continuation pattern)
     */
    public function reset();

    /**
     * Add/set an execution step to the command stack. Named to prevent double addition.
     *
     * @param string $method Name of a method of the batch object
     * @param mixed $id A unique id to prevent double adding of something to do
     * @param array  $params Array with scalars, as many parameters as needed allowed
     * @return boolean When true, increment the number of commands, otherwise the command existed
     */
    public function setStep($method, $id, $params);
}
