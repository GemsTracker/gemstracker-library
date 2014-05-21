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
 * @package    Gems
 * @subpackage SaveAnswerTask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SaveAnswerTask.php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage SaveAnswerTask
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Task_Import_SaveAnswerTask extends MUtil_Task_TaskAbstract
{
    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $targetModel;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->targetModel instanceof MUtil_Model_ModelAbstract) &&
            parent::checkRegistryRequestsAnswers();
    }

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param array $row Row to save
     */
    public function execute($row = null,
            $noToken = Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR,
            $tokenCompletion = Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR)
    {
        // MUtil_Echo::track($row);
        if ($row) {
            $answers = $row;
            $double  = false;
            $token   = null;
            $tracker = $this->loader->getTracker();
            $userId  = $this->loader->getCurrentUser()->getUserId();

            // Gems_Tracker::$verbose = true;

            $batch   = $this->getBatch();
            $batch->addToCounter('imported');

            // Remove all "non-answer" fields
            unset($answers['token'], $answers['patient_id'], $answers['organization_id'], $answers['track_id'],
                    $answers['completion_date'], $answers['gto_id_token']);

            // MUtil_Echo::track($row);
            if (isset($row['token']) && $row['token']) {
                $token = $tracker->getToken($row['token']);

                if ($token->exists && $token->isCompleted() && $token->getReceptionCode()->isSuccess()) {
                    $currentAnswers = $token->getRawAnswers();
                    $usedAnswers    = array_intersect_key($answers, $currentAnswers);
                    // MUtil_Echo::track($currentAnswers, $answers, $answers);

                    if ($usedAnswers) {
                        foreach ($usedAnswers as $name => $value) {
                            if ((null === $answers[$name]) || ($answers[$name] == $this->targetModel->get($name, 'default'))) {
                                // There is no value to set, so do not set the value
                                unset($answers[$name]);
                            } elseif (! ((null === $value) || ($value == $this->targetModel->get($name, 'default')))) {
                                // The value was already set
                                $double = true;
                                // But no break because of previous unsets
                                // break;
                            }
                        }
                    }
                }
            }
            if (! ($token && $token->exists)) {
                if (! (isset($row['track_id']) && $row['track_id'])) {

                }
                // create token?
            }

            if ($answers) {
                if ($double) {
                    if (Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_OVERWRITE == $tokenCompletion) {
                        $code = $this->util->getReceptionCode('redo');

                        $oldComment = "";
                        if ($token->getComment()) {
                            $oldComment .= "\n\n";
                            $oldComment .= $this->_('Previous comments:');
                            $oldComment .= "\n";
                            $oldComment .= $token->getComment();
                        }
                        $newComment = sprintf($this->_('Token %s overwritten by import.'), $token->getTokenId());

                        $replacementTokenId = $token->createReplacement($newComment . $oldComment, $userId);

                        $count = $batch->addToCounter('overwritten', 1);
                        $batch->setMessage('overwritten', sprintf(
                                $this->plural('%d token overwrote an existing token.',
                                        '%d tokens overwrote existing tokens.',
                                        $count),
                                $count));

                        $oldToken = $token;
                        $token = $tracker->getToken($replacementTokenId);

                        // Add the old answers to the new answer set as the new answers OVERWRITE the old data
                        $answers = $answers + $currentAnswers;

                        // Make sure the Next token is set right
                        $oldToken->setNextToken($token);

                        $oldToken->setReceptionCode(
                                $code,
                                sprintf($this->_('Token %s overwritten by import.'), $token->getTokenId()) . $oldComment,
                                $userId
                                );
                    } else {
                        $oldComment = "";
                        if ($token->getComment()) {
                            $oldComment .= "\n\n";
                            $oldComment .= $this->_('Previous comments:');
                            $oldComment .= "\n";
                            $oldComment .= $token->getComment();
                        }
                        $newComment = sprintf($this->_('More answers for token %s by import.'), $token->getTokenId());

                        $replacementTokenId = $token->createReplacement($newComment . $oldComment, $userId);

                        $count = $batch->addToCounter('addedAnswers', 1);
                        $batch->setMessage('addedAnswers', sprintf(
                                $this->plural('%d token was imported as a new extra token.',
                                        '%d tokens were imported as a new extra token.',
                                        $count),
                                $count));

                        $oldToken = $token;
                        $token = $tracker->getToken($replacementTokenId);

                        // Make sure the Next token is set right
                        $oldToken->setNextToken($token);
                        $oldToken->setReceptionCode(
                                $oldToken->getReceptionCode(),
                                sprintf($this->_('Additional answers in imported token %s.'), $token->getTokenId()) . $oldComment,
                                $userId
                                );
                    }
                }

                // There are still answers left to save

                // Make sure the token is known
                $token->getUrl($this->locale->getLanguage(), $userId);

                $token->setRawAnswers($answers);

                if (isset($row['completion_date']) && $row['completion_date']) {
                    $token->setCompletionTime($row['completion_date'], $userId);
                } elseif (! $token->isCompleted()) {
                    $token->setCompletionTime(new MUtil_Date(), $userId);
                }

                $count = $batch->addToCounter('changed', 1);
                $batch->setMessage('changed', sprintf(
                        $this->plural('%d token imported.',
                                '%d tokens imported.',
                                $count),
                        $count));
            }
        }
    }
}
