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
    public function execute($row = null, $noToken = 'error', $tokenCompletion = 'error')
    {
        // MUtil_Echo::track($row);
        if ($row) {
            $answers = $row;
            $double  = false;
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

                if ($token->isCompleted()) {
                    $currentAnswers = $token->getRawAnswers();
                    $usedAnswers    = array_intersect_key($answers, $answers);
                    // MUtil_Echo::track($currentAnswers, $answers, $answers);

                    if ($usedAnswers) {
                        foreach ($usedAnswers as $name => $value) {
                            if ((null === $answers[$name]) || ($answers[$name] == $this->targetModel->get($name, 'default'))) {
                                // There is no value to set, so do not set the value
                                unset($answers[$name]);
                            } elseif (! ((null === $value) || ($value == $this->targetModel->get($name, 'default')))) {
                                // The value was already set
                                $double = true;
                            }
                        }
                    }
                }
            } else {
                // create token?
            }

            if ($double) {

            } elseif ($answers) {
                // There are still answers left to save

                // Make sure the token is known
                $token->getUrl($this->locale->getLanguage(), $userId);

                $token->setRawAnswers($answers);

                if (isset($row['completion_date']) && $row['completion_date']) {
                    $token->setCompletionTime($row['completion_date'], $userId);
                } elseif (! $token->isCompleted()) {
                    $token->setCompletionTime(new MUtil_Date(), $userId);
                }
            }

            // $this->targetModel->save($row);

            $batch->addToCounter('changed', 1);
        }
    }
}
