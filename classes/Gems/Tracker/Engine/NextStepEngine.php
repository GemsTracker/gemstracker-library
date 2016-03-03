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
 * Step engine that uses a begin date from the previous round and calculates the end date using the token itself.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Engine_NextStepEngine extends \Gems_Tracker_Engine_StepEngineAbstract
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

        $previous = $this->getPreviousRoundId($itemData['gro_id_round'], $itemData['gro_id_order']);

        if ($previous) {
            $itemData['gro_valid_after_id'] = $previous;
            $rounds[$previous] = $this->getRound($previous)->getFullDescription();

        } else {
            $itemData['gro_valid_after_id'] = null;
            $rounds = $this->util->getTranslated()->getEmptyDropdownArray();
        }
        $model->set('gro_valid_after_id', 'multiOptions', $rounds, 'elementClass', 'Exhibitor');

        return false;
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
        if (! (isset($itemData['gro_id_round']) && $itemData['gro_id_round'])) {
            $itemData['gro_id_round'] = '';
        }
        // Fixed value
        $itemData['gro_valid_for_id'] = $itemData['gro_id_round'];

        // The options array
        $rounds[$itemData['gro_id_round']] = $this->_('This round');

        $model->set('gro_valid_for_id', 'multiOptions', $rounds, 'elementClass', 'Exhibitor');

        return false;
    }

    /**
     * Convert a TrackEngine instance to a TrackEngine of another type.
     *
     * @see getConversionTargets()
     *
     * @param type $conversionTargetClass
     */
    public function convertTo($conversionTargetClass)
    {
        if ($conversionTargetClass == 'AnyStepEngine') {
            //TODO: Check for right sequence of rounds
        } else {
            parent::convertTo($conversionTargetClass);
        }
    }

    /**
     * Returns a list of classnames this track engine can be converted into.
     *
     * Should always contain at least the class itself.
     *
     * @see convertTo()
     *
     * @param array $options The track engine class options available in as a "track engine class names" => "descriptions" array
     * @return array Filter or adaptation of $options
     */
    public function getConversionTargets(array $options)
    {
        $results = array();

        foreach ($options as $className => $label) {
            switch ($className) {
                case 'AnyStepEngine':
                case 'NextStepEngine':
                    $results[$className] = $label;
                    break;
            }
        }
        return $results;
    }

    /**
     * A longer description of the workings of the engine.
     *
     * @return string Name
     */
    public function getDescription()
    {
        return $this->_('Engine for tracks where the next round is always dependent on the previous step.');
    }


    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->_('Next Step');
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
        $date = null;

        switch ($fieldSource) {
            case parent::TOKEN_TABLE:
                if ($prev = $token->getPreviousSuccessToken()) {
                    $date = $prev->getDateTime($fieldName);
                }
                break;

            case parent::ANSWER_TABLE:
                if ($prev = $token->getPreviousSuccessToken()) {
                    $date = $prev->getAnswerDateTime($fieldName);
                }
                break;

            case parent::APPOINTMENT_TABLE:
            case parent::RESPONDENT_TRACK_TABLE:
                $date = $respTrack->getDate($fieldName);
        }

        return $date;
    }

    /**
     * Returns the date to use to calculate the ValidUntil if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems_Tracker_Token $token
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @param \MUtil_Date $validFrom The calculated new valid from value or null
     * @return \MUtil_Date date time or null
     */
    protected function getValidUntilDate($fieldSource, $fieldName, $prevRoundId, \Gems_Tracker_Token $token, \Gems_Tracker_RespondentTrack $respTrack, $validFrom)
    {
        $date = null;

        switch ($fieldSource) {
            case parent::NO_TABLE:
                break;

            case parent::TOKEN_TABLE:
                // Always uses the current token
                if ($fieldName == 'gto_valid_from') {
                    // May be changed but is not yet stored
                    $date = $validFrom;
                } else {
                    // No previous here, date is always from this tokens date
                    $date = $token->getDateTime($fieldName);
                }
                break;

            case parent::ANSWER_TABLE:
                if ($prev = $token->getPreviousSuccessToken()) {
                    $date = $prev->getAnswerDateTime($fieldName);
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