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
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Handles running tasks independent on the kind of task
 *
 * Continues on the MUtil_Batch_BatchAbstract, exposing some methods to allow the task
 * to interact with the batch queue.
 *
 * Tasks added to the queue should be loadable via Gems_Loader and implement the Gems_Task_TaskInterface
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_TaskRunnerBatch extends MUtil_Batch_BatchAbstract
{
    /**
     * @var Gems_Loader
     */
    public $loader;

    public $minimalStepDurationMs = 1000;

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->loader = GemsEscort::getInstance()->loader;
    }

    /**
     * Add a message to the message stack.
     *
     * @param string $text A message to the user
     * @return Gems_Task_TaskRunnerBatch
     */
    public function addMessage($text)
    {
        parent::addMessage($text);
        return $this;
    }

    /**
     * Increment a named counter
     *
     * @param string $name
     * @param integer $add
     * @return integer
     */
    public function addToCounter($name, $add = 1)
    {
        return parent::addToCounter($name, $add);
    }

    /**
     * Add a task to the stack, optionally adding as much parameters as needed
     *
     * @param string $task
     * @return Gems_Task_TaskRunnerBatch
     */
    public function addTask($task, $param1 = null)
    {
        $params = array_slice(func_get_args(), 1);
        $this->addStep('runTask', $task, $params);

        return $this;
    }

    public function runTask($task, $params)
    {
        $params = array_slice(func_get_args(), 1);
        $taskClass = $this->loader->getTask($task);
        if ($taskClass instanceof Gems_Task_TaskInterface) {
            $taskClass->setBatch($this);
            call_user_func_array(array($taskClass, 'execute'), $params[0]);
        } else {
            throw new Gems_Exception(sprintf('ERROR: Task by name %s not found', $task));
        }
    }

    /**
     * Add/set a message on the message stack with a specific id.
     *
     * @param scalar $id
     * @param string $text A message to the user
     * @return Gems_Task_TaskRunnerBatch
     */
    public function setMessage($id, $text)
    {
        parent::setMessage($id, $text);
        return $this;
    }

    /**
     * Add an execution step to the command stack.
     *
     * @param string $task
     * @param mixed $id A unique id to prevent double adding of something to do
     * @param mixed $param1 Scalar or array with scalars, as many parameters as needed allowed
     * @return Gems_Task_TaskRunnerBatch
     */
    public function setTask($task, $id, $param1 = null)
    {
        $params = array_slice(func_get_args(), 2);
        $this->setStep('runTask', $id, $task, $params);

        return $this;
    }
}