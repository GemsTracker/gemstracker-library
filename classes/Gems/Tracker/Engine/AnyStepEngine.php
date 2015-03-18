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
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Step engine that can select any previous round for begin date and any round for calculating the end date.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Engine_AnyStepEngine extends \Gems_Tracker_Engine_StepEngineAbstract
{

    /**
     * Set the surveys to be listed as valid after choices for this item and the way they are displayed (if at all)
     *
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    protected function applySurveyListValidAfter(\MUtil_Model_ModelAbstract $model, array &$itemData)
    {
        $this->_ensureRounds();

        if ($itemData['gro_id_round']) {
            // Get the earlier rounds
            $rounds = array();
            foreach ($this->_rounds as $roundId => $round) {
                if (($roundId == $itemData['gro_id_round']) || ($round['gro_id_order'] >= $itemData['gro_id_order'])) {
                    break;
                }
                $rounds[$roundId] = $round;
            }

        } else { // New item
            $rounds = $this->_rounds;
        }
        foreach ($rounds as $roundId => $round) {
            $rounds[$roundId] = $this->getRoundDescription($round);
        }
        return $this->_applyOptions($model, 'gro_valid_after_id', $rounds, $itemData);
    }

    /**
     * Set the surveys to be listed as valid for choices for this item and the way they are displayed (if at all)
     *
     * @param \MUtil_Model_ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    protected function applySurveyListValidFor(\MUtil_Model_ModelAbstract $model, array &$itemData)
    {
        $this->_ensureRounds();

        $rounds = array();
        foreach ($this->_rounds as $roundId => $round) {
            $rounds[$roundId] = $this->getRoundDescription($round);
        }

        if (!empty($itemData['gro_id_round'])) {
            $rounds[$itemData['gro_id_round']] = $this->_('This round');
        } else {
            // For new rounds we use 0. The snippet will update this on save
            // to the new roundid
            $rounds['0'] = $this->_('This round');
        }
        return $this->_applyOptions($model, 'gro_valid_for_id', $rounds, $itemData);
    }

    /**
     * The end date of any round can depend on any token so always do a end date check
     * of the previous tokens
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param \Gems_Tracker_Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems_Tracker_Token $skipToken Optional token to skip in the recalculation
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(\Gems_Tracker_RespondentTrack $respTrack, \Gems_Tracker_Token $startToken, $userId, \Gems_Tracker_Token $skipToken = null)
    {
        $changed = parent::checkTokensFrom($respTrack, $startToken, $userId);

        // Now loop back
        $token = $startToken->getPreviousToken();
        while ($token) {
            // Change only not-completed tokens with a positive successcode where the end date is not set manually
            if ($token->hasSuccesCode() &&
                    (! ($token->isCompleted() || $token->isValidUntilManual())) &&
                    ($token !== $skipToken)) {

                // Only process the token when linked to a round
                if(array_key_exists($token->getRoundId(), $this->_rounds)) {
                    $round      = $this->_rounds[$token->getRoundId()];

                    // Do not change valid from
                    $validFrom  = $token->getValidFrom();

                    $untilDate  = $this->getValidUntilDate($round['gro_valid_for_source'], $round['gro_valid_for_field'], $round['gro_valid_for_id'], $token, $respTrack, $validFrom);
                    $validUntil = $this->calculateUntilDate($untilDate, $round['gro_valid_for_unit'], $round['gro_valid_for_length']);

                    $changed    += $token->setValidFrom($validFrom, $validUntil, $userId);
                }
            }
            $token = $token->getPreviousToken();
        }

        return $changed;
    }

    /**
     * A longer description of the workings of the engine.
     *
     * @return string Name
     */
    public function getDescription()
    {
        return $this->_('Engine for tracks where a rounds activation can depend on any previous survey.');
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->_('Previous Survey');
    }

    /**
     * Get the defaults for a new round
     *
     * @return array Of fieldname => default
     */
    public function getRoundDefaults() {
        $defaults = parent::getRoundDefaults();

        // Now check if the valid for depends on the same round
        if(count($defaults)>1) {
            $lastRound = end($this->_rounds);   // We need the ID to compare
            if ($defaults['gro_valid_for_source'] == 'tok'
             && $defaults['gro_valid_for_field']  == 'gto_valid_from'
             && $defaults['gro_valid_for_id']     == $lastRound['gro_id_round']) {
                $defaults['gro_valid_for_id'] = 0;  // Will be updated on save
            }
        }

        return $defaults;
    }

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \MUtil_Model_ModelAbstract
     */
    public function getRoundModel($detailed, $action) {
        $model = parent::getRoundModel($detailed, $action);

        $model->set('gro_valid_for_id',
                'default', '0');

        return $model;
    }

    /**
     * Returns the date to use to calculate the ValidFrom if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems_Tracker_Token $token
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @return \MUtil_Date date time or null
     */
    protected function getValidFromDate($fieldSource, $fieldName, $prevRoundId, \Gems_Tracker_Token $token, \Gems_Tracker_RespondentTrack $respTrack)
    {
        return $this->getValidUntilDate($fieldSource, $fieldName, $prevRoundId, $token, $respTrack, false);
    }

    /**
     * Returns the date to use to calculate the ValidUntil if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems_Tracker_Token $token
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @param \MUtil_Date $validFrom The calculated new valid from value
     * @return \MUtil_Date date time or null
     */
    protected function getValidUntilDate($fieldSource, $fieldName, $prevRoundId, \Gems_Tracker_Token $token, \Gems_Tracker_RespondentTrack $respTrack, $validFrom)
    {
        $date = null;

        switch ($fieldSource) {
            case parent::ANSWER_TABLE:
                if ($prev = $respTrack->getActiveRoundToken($prevRoundId, $token)) {
                    $date = $prev->getAnswerDateTime($fieldName);
                }
                break;

            case parent::TOKEN_TABLE:
                if ($prev = $respTrack->getActiveRoundToken($prevRoundId, $token)) {
                    if ((false !== $validFrom) && ($prev === $token) && ($fieldName == 'gto_valid_from')) {
                        $date = $validFrom;
                    } else {
                        $date = $prev->getDateTime($fieldName);
                    }
                }
                break;

            case parent::APPOINTMENT_TABLE:
            case parent::RESPONDENT_TRACK_TABLE:
                $date = $respTrack->getDate($fieldName);
                break;
        }

        return $date;
    }
}
