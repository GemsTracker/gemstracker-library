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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Batch_ProcessTokensBatch extends MUtil_Batch_BatchAbstract
{
    /**
     *
     * @var Gems_Tracker
     */
    public $tracker;

    public function __construct($where, Gems_Tracker $tracker)
    {
        parent::__construct(__CLASS__ . '::' . $where);

        $this->tracker = $tracker;
    }

    public function addToken($tokenData, $userId)
    {
        if (is_array($tokenData)) {
             if (!isset($tokenData['gto_id_token'])) {
                 throw new Gems_Exception_Coding('$tokenData array should atleast have a key "gto_id_token" containing the requested token');
             }
            $tokenId = $tokenData['gto_id_token'];
        } else {
            $tokenId = $tokenData;
        }

        MUtil_Echo::track($tokenData);
        $this->addStep('checkTokenCompletion', 'tokchk-' . $tokenId, $tokenData);
    }

    protected function checkTrackTokens($respTrackData, $userId)
    {
        $respTrack = $this->tracker->getRespondentTrack($respTrackData);

        if ($result = $respTrack->checkTrackTokens($userId)) {
            $this->addToCounter('tokenDateCauses');
            $this->addToCounter('tokenDateChanges', $result);
        }
    }

    protected function checkTokenCompletion($tokenData, $userId)
    {
        $this->addToCounter('checkedTokens');
        $token = $this->tracker->getToken($tokenData);

        if ($result = $token->checkTokenCompletion($userId)) {
            if ($result & Gems_Tracker_Token::COMPLETION_DATACHANGE) {
                $this->addToCounter('resultDataChanges');
            }
            if ($result & Gems_Tracker_Token::COMPLETION_EVENTCHANGE) {
                $this->addToCounter('surveyCompletionChanges');
            }
        }

        if ($token->isCompleted()) {
            $this->addStep('processTokenCompletion', 'tokproc-' . $token->getTokenId(), $tokenData, $userId);
        }
    }

    protected function processTokenCompletion($tokenData, $userId)
    {
        $token = $this->tracker->getToken($tokenData);

        if ($token->isCompleted()) {
            $respTrack = $token->getRespondentTrack();

            if ($result = $respTrack->handleRoundCompletion($token, $userId)) {
                $this->addToCounter('roundCompletionCauses');
                $this->addToCounter('roundCompletionChanges', $result);
            }

            $trackId = $respTrack->getRespondentTrackId();
            $this->addStep('checkTrackTokens', 'chktrck-' . $trackId, $trackId, $userid);
        }
    }
}
