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
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Engine_SingleSurveyEngine extends Gems_Tracker_Engine_TrackEngineAbstract implements Gems_Tracker_Engine_TrackEngineInterface
{
    /**
     * Checks the first and only token of the respondent track
     * for the startdate.
     *
     * @param Gems_Tracker_RespondentTrack $respTrack
     * @param int $userId
     * @return int 1 if changed, 0 otherwise
     */
    private function _checkToken(Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        $changed = 0;

        if ($token = $respTrack->getFirstToken()) {
            if (! $token->isCompleted()) {
                if (! $token->getValidFrom()) {
                    return $token->setValidFrom($respTrack->getStartDate(), null, $userId);
                }
            }
        }

        return 0;
    }

    /**
     * Check the valid from and until dates in the track starting at a specified token
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param Gems_Tracker_Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(Gems_Tracker_RespondentTrack $respTrack, Gems_Tracker_Token $startToken, $userId)
    {
        if ($startToken->isCompleted()) {
            return 0;
        } else {
            return $this->_checkToken($respTrack, $userId);
        }
    }

    /**
     * Check the valid from and until dates in the track
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFromStart(Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        return $this->_checkToken($respTrack, $userId);
    }

    /**
     * An array of snippet names for displaying the answers to a survey.
     *
     * @return array if string snippet names
     */
    public function getAnswerSnippetNames()
    {
        return array('AnswerModelSnippet');
    }

    /**
     * A longer description of the workings of the engine.
     *
     * @return string Name
     */
    public function getDescription()
    {
        return $this->_('Engine for tracks containing a single survey.');
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->_('Single Survey');
    }

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return MUtil_Model_ModelAbstract
     */
    public function getRoundModel($detailed, $action)
    {
        $model = parent::getRoundModel($detailed, $action);

        $model->del('gro_id_track',          'label');
        $model->set('gro_id_survey',         'elementClass', 'exhibitor');
        $model->del('gro_id_order',          'label');
        $model->del('gro_round_description', 'label');
        $model->setCreate(false);

        if ($action == 'create') {
            // TODO: Remove option from menu
            throw new Gems_Exception($this->_('This track type does not allow the creation of new rounds.'));
        }

        return $model;
    }

    /**
     * An array of snippet names for deleting a token.
     *
     * @param Gems_Tracker_Token $token Allows token status dependent delete snippets
     * @return array of string snippet names
     */
    public function getTokenDeleteSnippetNames(Gems_Tracker_Token $token)
    {
        if ($token->inSource()) {
            return array('DeleteSingleSurveyInSourceTokenSnippet');
        } else {
            return array('DeleteSingleSurveyNotUsedTokenSnippet');
        }
    }

    /**
     * An array of snippet names for editing a token.
     *
     * @param Gems_Tracker_Token $token Allows token status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTokenEditSnippetNames(Gems_Tracker_Token $token)
    {
        return 'EditSingleSurveyTokenSnippet';
    }

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return Gems_Tracker_Model_SingleSurveyTokenModel
     */
    public function getTokenModel()
    {
        $model = $this->tracker->getTokenModel('SingleSurveyTokenModel');

        return $model;
    }

    /**
     * An array of snippet names for displaying a token
     *
     * @param Gems_Tracker_Token $token Allows token status dependent show snippets
     * @return array of string snippet names
     */
    public function getTokenShowSnippetNames(Gems_Tracker_Token $token)
    {
        return 'ShowSingleSurveyTokenSnippet';
    }

    /**
     * An array of snippet names for editing a track.
     *
     * @return array of string snippet names
     */
    public function getTrackCreateSnippetNames()
    {
        return array(
            // 'ShowTrackUsageSnippet',
            'EditSingleSurveyTokenSnippet',
            'TrackUsageTextDetailsSnippet',
            'SurveyQuestionsSnippet');
    }

    /**
     * An array of snippet names for deleting a track.
     *
     * @param Gems_Tracker_RespondentTrack $respTrack Allows track status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTrackDeleteSnippetNames(Gems_Tracker_RespondentTrack $respTrack)
    {
        // Kind of never really needed
        return $this->getTokenDeleteSnippetNames($respTrack->getFirstToken());
    }

    /**
     * An array of snippet names for editing a track.
     *
     * @param Gems_Tracker_RespondentTrack $respTrack Allows track status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTrackEditSnippetNames(Gems_Tracker_RespondentTrack $respTrack)
    {
        // Kind of never really needed
        return $this->getTokenEditSnippetNames($respTrack->getFirstToken());
    }

    /**
     * The track type of this engine
     *
     * @return string 'T' or 'S'
     */
    public function getTrackType()
    {
        return 'S';
    }

    /**
     * True if the user can create this kind of track in TrackMaintenanceAction.
     * False if this type of track is created by specialized user interface actions.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return boolean
     */
    public function isUserCreatable()
    {
        return false;
    }
}
