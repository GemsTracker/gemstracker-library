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
interface Gems_Tracker_Engine_TrackEngineInterface
{
    /**
     *
     * @param array $trackData array containing track record
     */
    public function __construct($trackData);

    /**
     * Set menu parameters from this track engine
     *
     * @param Gems_Menu_ParameterSource $source
     * @return Gems_Tracker_Engine_TrackEngineInterface (continuation pattern)
     */
    public function applyToMenuSource(Gems_Menu_ParameterSource $source);

    /**
     * Calculate the track info from the fields
     *
     * @param int $respTrackId Gems respondent track id or null when new
     * @param array $data The values to save
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo($respTrackId, array $data);

    /**
     * Calculate the number of active rounds in this track from the database.
     *
     * @return int The number of rounds in this track.
     */
    public function calculateRoundCount();

    /**
     * Check for the existence of all tokens and create them otherwise
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @param Gems_Task_TaskRunnerBatch $changes batch for counters
     */
    public function checkRoundsFor(Gems_Tracker_RespondentTrack $respTrack, $userId, Gems_Task_TaskRunnerBatch $batch = null);

    /**
     * Check the valid from and until dates in the track starting at a specified token
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param Gems_Tracker_Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(Gems_Tracker_RespondentTrack $respTrack, Gems_Tracker_Token $startToken, $userId);

    /**
     * Check the valid from and until dates in the track
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return Gems_Tracker_ChangeTracker detailed info on changes
     */
    public function checkTokensFromStart(Gems_Tracker_RespondentTrack $respTrack, $userId);

    /**
     * Convert a TrackEngine instance to a TrackEngine of another type.
     *
     * @see getConversionTargets()
     *
     * @param type $conversionTargetClass
     */
    public function convertTo($conversionTargetClass);

    /**
     * An array of snippet names for displaying the answers to a survey.
     *
     * @return array if string snippet names
     */
    public function getAnswerSnippetNames();

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
    public function getConversionTargets(array $options);

    /**
     * A longer description of the workings of the engine.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return string Name
     */
    public function getDescription();

    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsData($respTrackId);

    /**
     * Returns the fields required for editing a track of this type.
     *
     * @return array of Zend_Form_Element
     */
    public function getFieldsElements();

    /**
     * Get the round id of the first round
     *
     * @return int Gems id of first round
     */
    public function getFirstRoundId();

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return string Name
     */
    public function getName();

    /**
     * Look up the round id for the next round
     *
     * @param int $roundId  Gems round id
     * @return int Gems round id
     */
    public function getNextRoundId($roundId);

    /**
     * Look up the round id for the previous round
     *
     * @param int $roundId  Gems round id
     * @param int $roundOrder Optional extra round order, for when the current round may have changed.
     * @return int Gems round id
     */
    public function getPreviousRoundId($roundId, $roundOrder = null);

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param Gems_Tracker_Token $token
     * @return array Of snippet names
     */
    public function getRoundAnswerSnippets(Gems_Tracker_Token $token);
    
    /**
     * Return the Round Changed event name for this round
     *
     * @param int $roundId
     * @return Gems_Event_RoundChangedEventInterface event instance or null
     */
    public function getRoundChangedEvent($roundId);

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundEditSnippetNames();

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return Gems_Model_JoinModel
     */
    public function getRoundModel($detailed, $action);

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundShowSnippetNames();

    /**
     * An array of snippet names for deleting a token.
     *
     * @param Gems_Tracker_Token $token Allows token status dependent delete snippets
     * @return array of string snippet names
     */
    public function getTokenDeleteSnippetNames(Gems_Tracker_Token $token);

    /**
     * An array of snippet names for editing a token.
     *
     * @param Gems_Tracker_Token $token Allows token status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTokenEditSnippetNames(Gems_Tracker_Token $token);

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return Gems_Tracker_Model_StandardTokenModel
     */
    public function getTokenModel();

    /**
     * An array of snippet names for displaying a token
     *
     * @param Gems_Tracker_Token $token Allows token status dependent show snippets
     * @return array of string snippet names
     */
    public function getTokenShowSnippetNames(Gems_Tracker_Token $token);

    /**
     * An array of snippet names for creating a track.
     *
     * @return array of string snippet names
     */
    public function getTrackCreateSnippetNames();

    /**
     * An array of snippet names for deleting a track.
     *
     * @param Gems_Tracker_RespondentTrack $respTrack Allows track status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTrackDeleteSnippetNames(Gems_Tracker_RespondentTrack $respTrack);

    /**
     * An array of snippet names for editing a track.
     *
     * @param Gems_Tracker_RespondentTrack $respTrack Allows track status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTrackEditSnippetNames(Gems_Tracker_RespondentTrack $respTrack);

    /**
     *
     * @return int The track id
     */
    public function getTrackId();

    /**
     *
     * @return string The gems track name
     */
    public function getTrackName();

    /**
     * The track type of this engine
     *
     * @return string 'T' or 'S'
     */
    public function getTrackType();

    /**
     * True if the user can create this kind of track in TrackMaintenanceAction.
     * False if this type of track is created by specialized user interface actions.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return boolean
     */
    public function isUserCreatable();

    /**
     * Saves the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id
     * @param array $data The values to save
     * @return int The number of changed fields
     */
    public function setFieldsData($respTrackId, array $data);

    /**
     * Updates the number of rounds in this track.
     *
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function updateRoundCount($userId);
}
