<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Batch_ProcessTokensBatch extends MUtil_Batch_BatchAbstract
{
    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * Set a little higher, to reduce the effect of the server response time and application startup
     */
    public $minimalStepDurationMs = 3000;

    /**
     * Add the check of a single token to the batch.
     *
     * @param mixed $tokenData Array or token id
     * @param int $userId Gems user id
     * @return Gems_Tracker_Batch_ProcessTokensBatch (Continuation pattern)
     */
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

        // MUtil_Echo::track($tokenData);
        $this->setStep('checkTokenCompletion', 'tokchk-' . $tokenId, $tokenId, $userId);
        $this->addToCounter('tokens');

        return $this;
    }

    /**
     * Check a single track for the effects of token completion.
     *
     * @param mixed $respTrackData Data array or a respondent track id
     * @param int $userId Gems user id
     */
    protected function checkTrackTokens($respTrackData, $userId)
    {
        $respTrack = $this->tracker->getRespondentTrack($respTrackData);

        if ($result = $respTrack->checkTrackTokens($userId)) {
            $this->addToCounter('tokenDateCauses');
            $this->addToCounter('tokenDateChanges', $result);
        }
    }

    /**
     * Check for token completion and adds the processTokenCompletion
     * command when the token is indeed completed.
     *
     * NOTE: The reasons to add the extra commands in this process are
     * (1) that we are not sure in advance for which tokens we should
     * process and (2) the processing commands should be executed
     * AFTER all tokens have been checked for completion.
     *
     * @param mixed $tokenData Array or token id
     * @param int $userId Gems user id
     */
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
            $this->setStep('processTokenCompletion', 'tokproc-' . $token->getTokenId(), $tokenData, $userId);
        }
    }

    /**
     * Returns a description of what was changed during this batch.
     *
     * @return array Of message strings
     */
    public function getCounterMessages()
    {
        if ($this->getCounter('checkedRespondentTracks')) {
            $messages[] = sprintf($this->translate->_('Checked %d tracks.'), $this->getCounter('checkedRespondentTracks'));
        }
        if ($this->getCounter('checkedTokens') || (! $this->getCounter('checkedRespondentTracks'))) {
            $messages[] = sprintf($this->translate->_('Checked %d tokens.'), $this->getCounter('checkedTokens'));
        }

        if ($this->hasChanged()) {
            if ($this->getCounter('surveyCompletionChanges')) {
                $messages[] = sprintf($this->translate->_('Answers changed by survey completion event for %d tokens.'), $this->getCounter('surveyCompletionChanges'));
            }
            if ($this->getCounter('resultDataChanges')) {
                $messages[] = sprintf($this->translate->_('Results and timing changed for %d tokens.'), $this->getCounter('resultDataChanges'));
            }
            if ($this->getCounter('roundCompletionChanges')) {
                $messages[] = sprintf($this->translate->_('%d token round completion events caused changed to %d tokens.'), $this->getCounter('roundCompletionCauses'), $this->getCounter('roundCompletionChanges'));
            }
            if ($this->getCounter('tokenDateChanges')) {
                $messages[] = sprintf($this->translate->_('%2$d token date changes in %1$d tracks.'), $this->getCounter('tokenDateCauses'), $this->getCounter('tokenDateChanges'));
            }
            if ($this->getCounter('roundChangeUpdates')) {
                $messages[] = sprintf($this->translate->_('Round changes propagated to %d tokens.'), $this->getCounter('roundChangeUpdates'));
            }
            if ($this->getCounter('deletedTokens')) {
                $messages[] = sprintf($this->translate->_('%d tokens deleted by round changes.'), $this->getCounter('deletedTokens'));
            }
            if ($this->getCounter('createdTokens')) {
                $messages[] = sprintf($this->translate->_('%d tokens created to by round changes.'), $this->getCounter('createdTokens'));
            }
        } else {
            $messages[] = $this->translate->_('No tokens were changed.');
        }

        return $messages;
    }

    /**
     * String of messages from the batch
     *
     * Do not forget to reset() the batch if you're done with it after
     * displaying the report.
     *
     * @param boolean $reset When true the batch is reset afterwards
     * @return array
     */
    public function getMessages($reset = false)
    {
        return array_merge($this->getCounterMessages(), parent::getMessages($reset));
    }

    /**
     * The number of tokens to check
     *
     * @return int
     */
    public function getTokenCount()
    {
        return $this->getCounter('tokens');
    }

    /**
     * True when the batch changed anything.
     *
     * @return boolean
     */
    public function hasChanged()
    {
        return $this->getCounter('resultDataChanges') ||
                $this->getCounter('surveyCompletionChanges') ||
                $this->getCounter('roundCompletionChanges') ||
                $this->getCounter('tokenDateCauses') ||
                $this->getCounter('roundChangeUpdates') ||
                $this->getCounter('createdTokens');
    }

    /**
     * Processes token completion and adds the checkTrackTokens
     * command when the token is indeed completed.
     *
     * NOTE: The reasons we add the checkTrackTokens command are
     * that (1) we do not know in advance which tracks to check
     * and (2) the tracks should be checked AFTER all tokens have
     * been processed.
     *
     * @param mixed $tokenData Array or token id
     * @param int $userId Gems user id
     */
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
            $this->setStep('checkTrackTokens', 'chktrck-' . $trackId, $trackId, $userId);
        }
    }
}
