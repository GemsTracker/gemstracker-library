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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This interface lists all API-level methods in the Tracker class.
 *
 * This interface only exists to prevent the Gems_Loader_TargetLoaderAbstract
 * functions of being accessible when working with a tracker. Do not create
 * a second implementation is this interface but always create a subclass of
 * the Gems_Tracker class.
 *
 *
 * The tracker is the central access point doing anything with tracks or tokens.
 *
 * Tracker contains a number of getXxx functions to create Token, Survey,
 * RespondentTrack, [Survey]SourceInterface and TrackEngine objects.
 *
 * Tracker also offers MUtil_Model_ModelAbstract children for RespondentTracks,
 * Surveys, Tokens and Tracks.
 *
 * Other object classes accessible through gems_Tracker are TokenLibrary (defines
 * how tokens are created and checked), TokenSelect (Gems_Tracker_Token_TokenSelect
 * extension) and TokenValidator.
 *
 * Other functions are general utility functions, e.g. checkTrackRounds(), createToken(),
 * processCompletedTokens() and recalculateTokens().
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
interface Gems_Tracker_TrackerInterface
{
    /**
     * Checks tracks for changes to the the track and round definitions
     * and corrects them.
     *
     * Does recalculate changed tracks
     *
     * @param string $batchId A unique identifier for the current batch
     * @param int $userId Id of the user who takes the action (for logging)
     * @param string $cond Optional where statement for selecting tracks
     * @return Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    public function checkTrackRounds($batchId, $userId = null, $cond = null);

    /**
     * Create a new track for a patient
     *
     * @param int $respondentId   The real patientId (grs_id_user), not the patientnr (gr2o_patient_nr)
     * @param int $organizationId
     * @param int $trackId
     * @param int $userId         Id of the user who takes the action (for logging)
     * @param mixed $respTrackData Optional array containing field values or the start date.
     * @param array $trackFieldsData
     * @return Gems_Tracker_RespondentTrack The newly created track
     */
    public function createRespondentTrack($respondentId, $organizationId, $trackId, $userId, $respTrackData = null, array $trackFieldsData = array());

    /**
     * Dynamically load and create a [Gems|Project]_Tracker class
     *
     * @param string $className
     * @param mixed $param1
     * @param mixed $param2
     * @return object
     */
    public function createTrackClass($className, $param1 = null, $param2 = null);

    /**
     * Creates a new token with a new random token Id
     *
     * @param array $tokenData The other new data for the token
     * @param int $userId Id of the user who takes the action (for logging)
     * @return string
     */
    //public function createToken(array $tokenData, $userId = null);

    /**
     * Utility function for detecting unchanged values.
     *
     * @param array $oldValues
     * @param array $newValues
     * @return array
     */
    public function filterChangesOnly(array $oldValues, array &$newValues);

    /**
     * Removes all unacceptable characters from the input token and inserts any fixed characters left out
     *
     * @param string $tokenId
     * @return string Reformatted token
     */
    public function filterToken($tokenId);

    /**
     * Returns an array of all field id's for all tracks that have a code id
     *
     * @return array id => code
     */
    public function getAllCodeFields();

    /**
     * Returns a form to ask for a token
     *
     * @param mixed $args_array MUtil_Ra::args array for Form initiation.
     * @return Gems_Tracker_Form_AskTokenForm
     */
    public function getAskTokenForm($args_array = null);

    /**
     *
     * @param mixed $respTrackData Track id or array containing trackdata
     * @return Gems_Tracker_RespondentTrack
     */
    public function getRespondentTrack($respTrackData);

    /**
     * Get all tracks for a respondent
     *
     * Specify the optional $order to sort other than on start date
     *
     * @param int $respondentId
     * @param int $organizationId
     * @param mixed $order The column(s) and direction to order by
     * @return array of Gems_Tracker_RespondentTrack
     */
    public function getRespondentTracks($respondentId, $organizationId, $order = array('gr2t_start_date'));

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @return Gems_Tracker_Model_RespondentTrackModel
     */
    public function getRespondentTrackModel();

    /**
     * Retrieve a SourceInterface with a given id
     *
     * Should only be called by Gems_Tracker, Gems_Tracker_Survey or Gems_Tracker_Token (or should
     * this one use Gems_Tracker_Survey instead?)
     *
     * @param mixed $sourceData Gems source id or array containing gems source data
     * @return Gems_Tracker_Source_SourceInterface
     */
    public function getSource($sourceData);

    /**
     * Returns all registered source classes
     *
     * @return array Of classname => description
     */
    public function getSourceClasses();

    /**
     * Returns all registered database source classes
     *
     * @return array Of classname => description
     */
    public function getSourceDatabaseClasses();

    /**
     *
     * @param mixed $surveyData Gems survey id or array containing gems survey data
     * @return Gems_Tracker_Survey
     */
    public function getSurvey($surveyData);

    /**
     *
     * @param mixed $sourceSurveyId The source survey id
     * @param int $sourceId The gems source id of the source
     * @return Gems_Tracker_Survey
     */
    public function getSurveyBySourceId($sourceSurveyId, $sourceId);

    /**
     *
     * @param Gems_Tracker_Survey $survey
     * @param Gems_Tracker_Source_SourceInterface $source
     * @return Gems_Tracker_SurveyModel
     */
    public function getSurveyModel(Gems_Tracker_Survey $survey, Gems_Tracker_Source_SourceInterface $source);

    /**
     *
     * @param mixed $tokenData Token id or array containing tokendata
     * @return Gems_Tracker_Token
     */
    public function getToken($tokenData);

    /**
     *
     * @return type Gems_Tracker_Token_TokenFilter
     */
    public function getTokenFilter();

    /**
     * Use this function only within Gems_Tracker!!
     *
     * @return Gems_Tracker_Token_TokenLibrary
     */
    public function getTokenLibrary();

    /**
     * Returns a token model of the specified class with full display information
     *
     * @param string $modelClass Optional class to use instead of StandardTokenModel. Must be subclass.
     * @return Gems_Tracker_Model_StandardTokenModel
     */
    public function getTokenModel($modelClass = 'StandardTokenModel');

    /**
     * Create a select statement on the token table
     *
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function getTokenSelect($fields = '*');

    /**
     *
     * @return type Gems_Tracker_TokenFilter
     */
    public function getTokenValidator();

    /**
     *
     * @param mixed $trackData Gems track id or array containing gems track data
     * @return Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngine($trackData);

    /**
     * Returns dummy objects for all registered track engines class names
     *
     * Instead of creating another object layer all classes defined by
     * getTrackEngineClassNames() are loaded with dummy data so that the
     * TrackEngineInterface functions containing general class information
     * can be used.
     *
     * @see getTrackEngineClassNames()
     *
     * @static $dummyClasses Cache array
     * @return array Of Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngineClasses();

    /**
     * Return the edit snippets for editing or creating a new track
     *
     * @return array of snippet names for creating a new track engine
     */
    public function getTrackEngineEditSnippets();

    /**
     * Returns all registered track engines classes for use in drop down lists.
     *
     * @param boolean $extended When true return a longer name.
     * @param boolean $userCreatableOnly Return only the classes that can be created by the user interface
     * @return array Of classname => description
     */
    public function getTrackEngineList($extended = false, $userCreatableOnly = false);

    /**
     * Simple function for a default track model.
     *
     * @return Gems_Tracker_Model_TrackModel
     */
    public function getTrackModel();

    /**
     * Checks the token table to see if there are any answered surveys to be processed
     *
     * If the survey was started (and the token was forwarded to limesurvey) we need to check
     * if is was completed. If so, we might want to check the track the survey is in to enable
     * or disable future rounds
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param int $respondentId   Id of the respondent to check for or NULL
     * @param int $userId         Id of the user who takes the action (for logging)
     * @param int $orgId          Optional Id of the organization to check for
     * @param boolean $quickCheck Check only tokens with recent gto_start_time's
     * @return bool               Did we find new answers?
     */
    public function processCompletedTokens($respondentId, $userId = null, $orgId = null, $quickCheck = false);

    /**
     * Recalculates all token dates, timing and results
     * and outputs text messages.
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param string $batch_id A unique identifier for the current batch
     * @param int $userId    Id of the user who takes the action (for logging)
     * @param string $cond
     * @return Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    public function recalculateTokens($batch_id, $userId = null, $cond = null);

    /**
     * Refreshes the tokens in the source
     *
     * @param string $batch_id A unique identifier for the current batch
     * @param string $cond An optional where statement
     * @return Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    public function refreshTokenAttributes($batch_id, $cond = null);

    /**
     * Remove token from cache for saving memory
     *
     * @param string|Gems_Tracker_Token $token
     * @return \Gems_Tracker (continuation pattern)
     */
    public function removeToken($token);

    /**
     * Recalculates all token dates, timing and results
     * and outputs text messages.
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param int $sourceId A source identifier
     * @param int $userId Id of the user who takes the action (for logging)
     * @return Gems_Task_TaskRunnerBatchs A batch to process the synchronization
     */
    public function synchronizeSources($sourceId = null, $userId = null);
}
