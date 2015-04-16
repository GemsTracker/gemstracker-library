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
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This helper provides an easy method for running tasks in batch.
 *
 * Just provide the batch and the title to use and you will be fine.
 *
 * @package    Gems
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Controller_Action_Helper_BatchRunner extends \Zend_Controller_Action_Helper_Abstract
{
    /**
     *
     * @param \MUtil_Batch_BatchAbstract $batch
     * @param string $title
     * @param \Gems_AccessLog $accessLog
     */
    public function direct(\MUtil_Batch_BatchAbstract $batch, $title, $accessLog = null)
    {

        if ($batch->isConsole()) {
            $batch->runContinuous();

            $messages = array_values($batch->getMessages(true));
            echo implode("\n", $messages) . "\n";

            if ($echo = \MUtil_Echo::out()) {
                echo "\n\n================================================================\nECHO OUTPUT:\n\n";
                echo \MUtil_Console::removeHtml($echo);
            }
            if ($accessLog instanceof \Gems_AccessLog) {
                $accessLog->logChange($this->getRequest(), $messages, $echo);
            }
            exit;
        } elseif ($batch->run($this->getRequest())) {
            exit;
        } else {
            $controller = $this->getActionController();
            $batchContainer = $controller->html->div(array('class' => 'batch-container'));
            $batchContainer->h3($title);

            if ($batch->isFinished()) {
                $controller->addMessage($batch->getMessages(true), 'info');
                $batchContainer->pInfo($batch->getRestartButton($controller->_('Prepare recheck'), array('class' => 'actionlink')));
                if ($accessLog instanceof \Gems_AccessLog) {
                    $echo = array_filter(array_map('trim', preg_split('/<[^>]+>/', \MUtil_Echo::getOutput())));
                    $accessLog->logChange($this->getRequest(), null, $echo);
                }
            } else {
                if ($batch->count()) {
                    $batchContainer->pInfo($batch->getStartButton(sprintf($controller->_('Start %s jobs'), $batch->count())));
                    $batchContainer->append($batch->getPanel($controller->view, $batch->getProgressPercentage() . '%'));
                } else {
                    $batchContainer->pInfo($controller->_('Nothing to do.'));
                }
            }
        }
    }
}
