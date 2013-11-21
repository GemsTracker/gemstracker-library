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
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Handles completion of a token, mostly started by Gems_Task_CheckTokenCompletion
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_Tracker_ProcessTokenCompletion extends MUtil_Task_TaskAbstract
{
    /**
     * @var Gems_Loader
     */
    public $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($tokenData = null, $userId = null)
    {
        $batch   = $this->getBatch();
        $tracker = $this->loader->getTracker();
        $token   = $tracker->getToken($tokenData);

        if ($token->isCompleted()) {
            $respTrack = $token->getRespondentTrack();

            if ($result = $respTrack->handleRoundCompletion($token, $userId)) {
                $a = $batch->addToCounter('roundCompletionCauses');
                $b = $batch->addToCounter('roundCompletionChanges', $result);
                $batch->setMessage('roundCompletionChanges',
                        sprintf($this->_('%d token round completion events caused changed to %d tokens.'), $a, $b)
                        );
            }

            $trackId = $respTrack->getRespondentTrackId();
            $batch->setTask('Tracker_CheckTrackTokens', 'chktrck-' . $trackId, $trackId, $userId);
        }

        // Free memory
        $tracker->removeToken($token);
    }
}