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
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TaskAbstract.php$
 */

/**
 * Basic implementation of MUtil_Task_TaskInterface, the interface for a Task object.
 * The MUtil_Registry_TargetInterface allows the automatic loading of global objects.
 *
 * Task objects split large jobs into a number of serializeable small jobs that are
 * stored in the session or elsewhere and that can be executed one job at a time
 * split over multiple runs.
 *
 * @package    MUtil
 * @subpackage Task
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
abstract class MUtil_Task_TaskAbstract extends MUtil_Translate_TranslateableAbstract implements MUtil_Task_TaskInterface
{
    /**
     *
     * @var MUtil_Task_TaskBatch
     */
    protected $batch;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    // public function execute();

    /**
     * Returns the batch this task belongs to
     *
     * @return MUtil_Task_TaskBatch
     */
    public function getBatch()
    {
        if (! $this->batch instanceof MUtil_Task_TaskBatch) {
            throw new MUtil_Batch_BatchException(sprintf(
                    "Batch not set during execution of task class %s!!",
                    __CLASS__
                    ));
        }

        return $this->batch;
    }

    /**
     * Sets the batch this task belongs to
     *
     * This method will be called from the Gems_Task_TaskRunnerBatch upon execution of the
     * task. It allows the task to communicate with the batch queue.
     *
     * @param MUtil_Task_TaskBatch $batch
     * @return MUtil_Task_TaskInterface (continuation pattern)
     */
    public function setBatch(MUtil_Task_TaskBatch $batch)
    {
        $this->batch = $batch;
        return $this;
    }
}
