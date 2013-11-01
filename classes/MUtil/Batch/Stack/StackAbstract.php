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
 * @version    $Id: SessionStack.php$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Batch
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
abstract class MUtil_Batch_Stack_StackAbstract implements MUtil_Batch_Stack_Stackinterface
{
    /**
     * The classes allowed in the stack
     *
     * @var MUtil_Util_ClassList
     */
    protected $_allowedClasses;

    /**
     * Add/set the command to the stack
     *
     * @param array $command
     * @param string $id Optional id to repeat double execution
     * @return boolean When true, increment the number of commands, otherwise the command existed
     */
    abstract protected function _addCommand(array $command, $id = null);

    protected function _checkParams(array $params)
    {
        $checks = MUtil_Ra::nonScalars($params);
        if ($this->_allowedClasses) {
            $checks = MUtil_Ra::nonScalars($params);
            foreach ($checks as $object) {
                if (! $this->_allowedClasses->get($object)) {
                    $name   = get_class($object);
                    $method = reset($params);
                    throw new MUtil_Batch_BatchException("Not allowed batch class $name parameter for method: '$method'.");
                }
            }

        } elseif (! MUtil_Ra::isScalar($params)) {
            $checks = MUtil_Ra::nonScalars($params);
            $object = reset($checks);
            $name   = get_class($object);
            $method = reset($params);
            throw new MUtil_Batch_BatchException("Not allowed batch class $name parameter for method: '$method'.");
        }
    }

    /**
     * Make sure the allowed class list exists
     *
     * @return void
     */
    protected function _ensureAllowedClassList()
    {
        if (! $this->_allowedClasses) {
            $this->_allowedClasses = new MUtil_Util_ClassList();
        }
    }

    /**
     * Add an execution step to the command stack.
     *
     * @param string $method Name of a method of the batch object
     * @param array  $params Array with scalars, as many parameters as needed allowed
     * @return boolean When true, increment the number of commands, otherwise the command existed
     */
    public function addStep($method, array $params)
    {
        $this->_checkParams($params);

        return $this->_addCommand(array($method, $params));
    }

    /**
     * Return the next command
     *
     * @return array()
     */
    // public function getNext();

    /**
     * Run the next command
     *
     * @param mixed $batch Should be MUtil_Batch_BatchAbstract but could be changed in implementations
     * @return void
     */
    // public function gotoNext($batch);

    /**
     * Return true when there still exist unexecuted commands
     *
     * @return boolean
     */
    // public function hasNext()

    /**
     * Register a class as being allowed in the stack
     *
     * @param string $className
     * @return \MUtil_Batch_Stack_StackAbstract (continuation pattern)
     */
    public function registerAllowedClass($className)
    {
        if (! $this->_allowedClasses) {
            $this->_ensureAllowedClassList();
        }
        if (is_object($className)) {
            $className = get_class($className);
        }
        $this->_allowedClasses->set($className, true);

        return $this;
    }

    /**
     * Reset the stack
     *
     * @return \MUtil_Batch_Stack_Stackinterface (continuation pattern)
     */
    // public function reset()

    /**
     * Add/set an execution step to the command stack. Named to prevent double addition.
     *
     * @param string $method Name of a method of the batch object
     * @param mixed $id A unique id to prevent double adding of something to do
     * @param array  $params Array with scalars, as many parameters as needed allowed
     * @return boolean When true, increment the number of commands, otherwise the command existed
     */
    public function setStep($method, $id, $params)
    {
        $this->_checkParams($params);

        return $this->_addCommand(array($method, $params), $id);
    }
}
