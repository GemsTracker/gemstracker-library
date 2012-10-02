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
 * @subpackage Batch
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This a an example / test implementation of MUtil_Batch_BatchAbstract.
 *
 * It does nothing but wait, but allows you to test the workings of the
 * batch processing in general and the use of a progress panel in general.
 *
 * PULL usage example ($this->view must be a Zend_View) with a nice start button:
 * <code>
 * $batch = new MUtil_Batch_WaitBatch();
 * if ($batch->run($this->getRequest())) {
 *     exit;
 * } else {
 *     $html = new MUtil_Html_Sequence();
 *     if ($batch->isFinished()) {
 *         $html->ol($batch->getMessages(true));
 *         $html->a(array($batch->progressParameterName => null), 'Restart');
 *     } else {
 *         // Populate the batch (from scratch).
 *         $batch->reset();
 *         $batch->addWaits(4, 2);
 *         $batch->addWaits(2, 1);
 *         $batch->addWaitsLater(15, 1);
 *         $batch->addWait(4, 'That took some time!');
 *         $batch->addWait(4, 'So we see the message. :)');
 *         $batch->addWaitsLater(1, 2);
 *         $batch->addWaits(4);
 *
 *         $html->append($batch->getPanel($this->view, $batch->getStartButton('Nice start')));
 *     }
 *     echo $html->render($this->view);
 * }
 * </code>
 *
 * PUSH usage example that starts automatically:
 * <code>
 * $batch = new MUtil_Batch_WaitBatch();
 * $batch->setMethodPush(5);
 * $batch->autoStart = true;
 * $batch->minimalStepDurationMs = 200;
 *
 * if ($batch->run($this->getRequest())) {
 *     exit;
 * } else {
 *     $html = new MUtil_Html_Sequence();
 *     if ($batch->isFinished()) {
 *         $html->ul($batch->getMessages(true));
 *         $html->a(array($batch->progressParameterName => null), 'Restart');
 *     } else {
 *         // Populate the batch (from scratch).
 *         $batch->reset();
 *         $batch->addWaitsMs(400, 20);
 *         $batch->addWaits(2, 1, 'Har har');
 *         $batch->addWaitsMs(20, 50);
 *
 *         $html->append($batch->getPanel($this->view));
 *     }
 *     echo $html->render($this->view);
 * }
 * </code>
 *
 * @package    MUtil
 * @subpackage Batch
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Batch_WaitBatch extends MUtil_Batch_BatchAbstract
{
    /**
     * The minimal time used between send progress reports.
     *
     * This enables quicker processing as multiple steps can be taken in a single
     * run(), without the run taking too long to answer.
     *
     * Set to 0 to report back on each step.
     *
     * @var int
     */
    public $minimalStepDurationMs = 100;

    /**
     * Add one second wait command to the command stack.
     *
     * @param int $seconds
     * @param text $message Optional, otherwise the message is the time of wait
     * @return MUtil_Batch_WaitBatch (continuation pattern)
     */
    public function addWait($seconds = 1, $message = null)
    {
        $this->addStep('waitFor', $seconds, $message);

        return $this;
    }

    /**
     * Add one microsecond wait command to the command stack.
     *
     * @param int $microsSeconds
     * @param text $message Optional, otherwise the message is the time of wait
     * @return MUtil_Batch_WaitBatch (continuation pattern)
     */
    public function addWaitMs($microsSeconds = 100, $message = null)
    {
        $this->addStep('waitForMs', $microsSeconds, $message);

        return $this;
    }

    /**
     * Add multiple second wait commands to the command stack.
     *
     * @param int $times
     * @param int $seconds
     * @param text $message Optional, otherwise the message is the time of wait
     * @return MUtil_Batch_WaitBatch (continuation pattern)
     */
    public function addWaits($times, $seconds = 1, $message = null)
    {
        for ($i = 0; $i < $times; $i++) {
            $this->addStep('waitFor', $seconds, $message);
        }

        return $this;
    }

    /**
     * Add multiple microsecond wait commands to the command stack.
     *
     * @param int $times
     * @param int $microsSeconds
     * @param text $message Optional, otherwise the message is the time of wait
     * @return MUtil_Batch_WaitBatch (continuation pattern)
     */
    public function addWaitsMs($times, $microsSeconds = 100, $message = null)
    {
        for ($i = 0; $i < $times; $i++) {
            $this->addStep('waitForMs', $microsSeconds, $message);
        }

        return $this;
    }

    /**
     * Testing purposes only, this code adds wait commands to the
     * command stack during running.
     *
     * The result is that you may see the percentage done actually
     * decrease instead of increase.
     *
     * @param int $times
     * @param int $seconds
     * @param text $message Optional, otherwise the message is the time of wait
     * @return MUtil_Batch_WaitBatch (continuation pattern)
     */
    public function addWaitsLater($times, $seconds = 1, $message = null)
    {
        $this->addStep('addWaits', $times, $seconds, $message);

        return $this;
    }

    /**
     * Testing purposes only, this code adds wait commands to the
     * command stack during running.
     *
     * The result is that you may see the percentage done actually
     * decrease instead of increase.
     *
     * @param int $times
     * @param int $microsSeconds
     * @param text $message Optional, otherwise the message is the time of wait
     * @return MUtil_Batch_WaitBatch (continuation pattern)
     */
    public function addWaitsMsLater($times, $microsSeconds = 100, $message = null)
    {
        $this->addStep('addWaitsMs', $times, $microsSeconds, $message);

        return $this;
    }

    /**
     * A step method that just waits for a number of seconds.
     *
     * @param int $seconds
     * @param string $message
     */
    protected function waitFor($seconds, $message)
    {
        sleep($seconds);

        if (! $message) {
            if ($seconds == 1) {
                $message = "Waited for 1 second.";
            } else {
                $message = sprintf("Waited for %d seconds.", $seconds);
            }
        }
        $this->addMessage($message);
    }

    /**
     * A step method that just waits for a number of microseconds.
     *
     * @param int $microsSeconds
     * @param string $message
     */
    protected function waitForMs($microsSeconds, $message)
    {
        usleep($microsSeconds);

        if (! $message) {
            if ($microsSeconds == 1) {
                $message = "Waited for 1 micro second.";
            } else {
                $message = sprintf("Waited for %.3f seconds.", $microsSeconds / 1000);
            }
        }

        $this->addMessage($message);
    }
}
