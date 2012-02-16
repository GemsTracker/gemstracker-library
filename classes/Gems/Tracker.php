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
 * processCompletedTokens() and recalculateTokensBatch().
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker extends Gems_Loader_TargetLoaderAbstract implements Gems_Tracker_TrackerInterface
{
    const DB_DATE_FORMAT = 'yyyy-MM-dd';
    const DB_DATETIME_FORMAT = 'yyyy-MM-dd HH:mm:ss';

    /**
     *
     * @var array of Gems_Tracker_RespondentTrack
     */
    private $_respTracks = array();

    /**
     * This variable holds all registered source classes, may be changed in derived classes
     *
     * @var array Of classname => description
     */
    protected $_sourceClasses = array(
        'LimeSurvey1m9Database'  => 'Lime Survey 1.90 DB',
        'LimeSurvey1m91Database' => 'Lime Survey 1.91+ DB',
        );

    /**
     *
     * @var array of Gems_Tracker_SourceInterface
     */
    private $_sources = array();

    /**
     *
     * @var array of Gems_Survey
     */
    private $_surveys = array();

    /**
     *
     * @var Gems_Tracker_TokenLibrary
     */
    private $_tokenLibrary;

    /**
     *
     * @var array of Gems_Tracker_Model_StandardTokenModel
     */
    private $_tokenModels = array();

    /**
     *
     * @var array of Gems_Tracker_Token
     */
    private $_tokens = array();

    /**
     *
     * @var array of Gems_Tracker_Engine_TrackEngineInterface
     */
    private $_trackEngines = array();

    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Tracker';

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var Zend_Session
     */
    protected $session;

    /**
     * Set to true to get detailed information on all tracker actions
     *
     * @var boolean
     */
    public static $verbose = false;

    /**
     *
     * @param type $container A container acting as source fro MUtil_Registry_Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct($container, array $dirs)
    {
        parent::__construct($container, $dirs);

        $events = $container->loader->getEvents();

        // Make sure the tracker is known
        $this->addRegistryContainer(array('tracker' => $this, 'events' => $events));
    }

    /**
     * Replaces a null or empty userId with that of the current user
     *
     * @param int $userId
     * @return int
     */
    private function _checkUserId($userId = null) {
        if (empty($userId)) {
            $userId = isset($this->session->user_id) ? $this->session->user_id : 0;
        }
        return $userId;
    }

    /**
     * Add one or more survey sourceclasses
     *
     * @param array $stack classname / description array of sourceclasses
     */
    public function addSourceClasses($stack) {
        $this->_sourceClasses = array_merge($this->_sourceClasses, $stack);
    }

    /**
     * Checks tracks for changes to the the track and round definitions
     * and corrects them.
     *
     * Does recalculate changed tracks
     *
     * @param int $userId
     * @param string $cond
     * @return array of translated messages
     */
    public function checkTrackRounds($userId = null, $cond = null)
    {
        $userId = $this->_checkUserId($userId);
        $respTrackSelect = $this->db->select();
        $respTrackSelect->from('gems__respondent2track');
        $respTrackSelect->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code');
        $respTrackSelect->join('gems__tracks', 'gr2t_id_track = gtr_id_track');

        if ($cond) {
            $respTrackSelect->where($cond);
        }
        $respTrackSelect->where('gr2t_active = 1');
        $respTrackSelect->where('grc_success = 1');
        $respTrackSelect->where('gtr_active = 1');
        $respTrackSelect->where('gr2t_count != gr2t_completed');

        self::$verbose = true;

        $changes    = new Gems_Tracker_ChangeTracker();
        $respTracks = $respTrackSelect->query()->fetchAll();

        foreach ($respTracks as $respTrackData) {
            $respTrack = $this->getRespondentTrack($respTrackData);

            $respTrack->checkRounds($userId, $changes);
            $changes->checkedRespondentTracks++;

            unset($respTrack);
        }

        return $changes->getMessages($this->translate);
    }

    /**
     *
     * @param int $patientId    The real patientId (grs_id_user), not the patientnr (gr2o_patient_nr)
     * @param int $organizationId
     * @param int $trackId
     * @param int $userId    Id of the user who takes the action (for logging)
     * @param mixed $respTrackData Optional array containing field values or the start date.
     * @param array $trackFieldsData
     * @return Gems_Tracker_RespondentTrack The newly created track
     */
    public function createRespondentTrack($patientId, $organizationId, $trackId, $userId, $respTrackData = array(), array $trackFieldsData = array())
    {
        $trackEngine = $this->getTrackEngine($trackId);

        // Process $respTrackData and gr2t_start_date values
        if ($respTrackData && (! is_array($respTrackData))) {
            // When single value it contains the start date
            $respTrackData = array('gr2t_start_date' => $respTrackData);
        }
        if (! array_key_exists('gr2t_start_date', $respTrackData)) {
            // The start date has to exist.
            $respTrackData['gr2t_start_date'] = new MUtil_Date();
        }
        $respTrackData['gr2t_id_user']         = $patientId;
        $respTrackData['gr2t_id_organization'] = $organizationId;

        // Process track fields.
        if ($trackFieldsData && (! array_key_exists('gr2t_track_info', $respTrackData))) {
            $respTrackData['gr2t_track_info'] = $trackEngine->calculateFieldsInfo(null, $trackFieldsData);
        }

        // Create the filter values for creating the track
        $filter['gtr_id_track']         = $trackId;

        // Load all other new data
        $respTrackModel = $this->getRespondentTrackModel();
        $respTrackData  = $respTrackData + $respTrackModel->loadNew(null, $filter);
        // MUtil_Echo::track($respTrackData);

        // Save to gems__respondent2track
        $respTrackData  = $respTrackModel->save($respTrackData);
        // MUtil_Echo::track($respTrackData);

        // Load the track object
        $respTrack      = $this->getRespondentTrack($respTrackData);

        // Save the fields
        if ($trackFieldsData) {
            $trackEngine->setFieldsData($respTrack->getRespondentTrackId(), $trackFieldsData);
        }

        // Create the actual tokens!!!!
        $trackEngine->checkRoundsFor($respTrack, $userId);

        return $respTrack;
    }

    /**
     * Creates a new token with a new random token Id
     *
     * @param array $tokenData The other new data for the token
     * @param int $userId Id of the user who takes the action (for logging)
     * @return string
     */
    public function createToken(array $tokenData, $userId = null)
    {
        $userId = $this->_checkUserId($userId);
        return $this->getTokenLibrary()->createToken($tokenData, $userId);
    }

    /**
     * Utility function for detecting unchanged values.
     *
     * @param array $oldValues
     * @param array $newValues
     * @return array
     */
    public function filterChangesOnly(array $oldValues, array &$newValues)
    {
        if ($newValues && $oldValues) {
            // MUtil_Echo::track($newValues);
            // Remove up unchanged values
            foreach ($newValues as $name => $value) {
                if (array_key_exists($name, $oldValues)) {
                    // Extra condition for empty time in date values
                    if (($value === $oldValues[$name]) || ($value === $oldValues[$name] . ' 00:00:00')) {
                        unset($newValues[$name]);
                    }
                }
            }
        }

        return $newValues;
    }

    /**
     * Removes all unacceptable characters from the input token and inserts any fixed characters left out
     *
     * @param string $tokenId
     * @return string Reformatted token
     */
    public function filterToken($tokenId)
    {
        return $this->getTokenLibrary()->filter($tokenId);
    }

    /**
     *
     * @param int $userId
     * @param int $organizationId
     * @return array of Gems_Tracker_RespondentTrack
     */
    public function getRespondentTracks($userId, $organizationId)
    {
        $sql    = "SELECT *
                    FROM gems__respondent2track INNER JOIN gems__reception_codes ON gr2t_reception_code = grc_id_reception_code
                    WHERE gr2t_id_user = ? AND gr2t_id_organization = ?";
        $rows   = $this->db->fetchAll($sql, array($userId, $organizationId));
        $tracks = array();

        foreach ($rows as $row) {
            $tracks[$row['gr2t_id_respondent_track']] = $this->getRespondentTrack($row);
        }

        return $tracks;
    }

    /**
     *
     * @param mixed $respTrackData Track id or array containing trackdata
     * @return Gems_Tracker_RespondentTrack
     */
    public function getRespondentTrack($respTrackData)
    {
        if (is_array($respTrackData)) {
            $respTracksId = $respTrackData['gr2t_id_respondent_track'];
        } else {
            $respTracksId = $respTrackData;
        }

        if (! isset($this->_respTracks[$respTracksId])) {
            $this->_respTracks[$respTracksId] = $this->_loadClass('respondentTrack', true, array($respTrackData));
        }

        return $this->_respTracks[$respTracksId];
    }

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @return Gems_Tracker_Model_RespondentTrackModel
     */
    public function getRespondentTrackModel()
    {
        return $this->_loadClass('Model_RespondentTrackModel', true);
    }

    /**
     * Retrieve a SourceInterface with a given id
     *
     * Should only be called by Gems_Tracker, Gems_Tracker_Survey or Gems_Tracker_Token (or should
     * this one use Gems_Tracker_Survey instead?)
     *
     * @param mixed $sourceData Gems source id or array containing gems source data
     * @return Gems_Tracker_Source_SourceInterface
     */
    public function getSource($sourceData)
    {
        if (is_array($sourceData)) {
            $sourceId = $sourceData['gso_id_source'];
        } else {
            $sourceId   = $sourceData;
            $sourceData = false;
        }

        if (! isset($this->_sources[$sourceId])) {
            if (! $sourceData) {

                $sourceData = $this->db->fetchRow("SELECT * FROM gems__sources WHERE gso_id_source = ?", $sourceId);
            }

            if (! isset($sourceData['gso_ls_class'])) {
                throw new Gems_Exception_Coding('Missing source class for source ID: ' . $sourceId);
            }

            $this->_sources[$sourceId] = $this->_loadClass('source_' . $sourceData['gso_ls_class'], true, array($sourceData, $this->db));
        }

        return $this->_sources[$sourceId];
    }

    /**
     * Returns all registered source classes
     *
     * @return array Of classname => description
     */
    public function getSourceClasses()
    {
        return $this->_sourceClasses;
    }

    /**
     * Returns all registered database source classes
     *
     * @return array Of classname => description
     */
    public function getSourceDatabaseClasses()
    {
        // TODO: this should be moved to Gems_Tracker_Source_SourceInterface,
        // but do not have time to implement is of minor importance at this moment.

        // If the project uses Pdo database, use Pdo classes, otherwise MySQL
        if (stripos(get_class($this->db), '_Pdo_')) {
            return array(
                '' => '-- none --',
                'Pdo_Mysql' => 'MySQL (PDO)',
                'Pdo_Mssql' => 'SQL Server (PDO)');
        } else {
            return array(
                '' => '-- none --',
                'Mysqli' => 'MySQL',
                'Sqlsrv' => 'SQL Server');
        }
    }

    /**
     *
     * @param mixed $surveyData Gems survey id or array containing gems survey data
     * @return Gems_Tracker_Survey
     */
    public function getSurvey($surveyData)
    {
        if (is_array($surveyData)) {
            $surveyId = $surveyData['gsu_id_survey'];
        } else {
            $surveyId = $surveyData;
        }

        if (! isset($this->_surveys[$surveyId])) {
            $this->_surveys[$surveyId] = $this->_loadClass('survey', true, array($surveyData));
        }

        return $this->_surveys[$surveyId];
    }

    /**
     *
     * @param mixed $sourceSurveyId The source survey id
     * @param int $sourceId The gems source id of the source
     * @return Gems_Tracker_Survey
     */
    public function getSurveyBySourceId($sourceSurveyId, $sourceId)
    {
        $surveyData = $this->db->fetchRow("SELECT * FROM gems__surveys WHERE gsu_id_source = ? AND gsu_surveyor_id = ?", array($sourceId, $sourceSurveyId));

        if (! $surveyData) {
            static $newcount = -1;

            $surveyData['gsu_id_survey']   = $newcount--;
            $surveyData['gsu_surveyor_id'] = $sourceSurveyId;
            $surveyData['gsu_id_source']   = $sourceId;

            // MUtil_Echo::track($surveyData);
        }

        return $this->getSurvey($surveyData);
    }

    /**
     *
     * @param Gems_Tracker_Survey $survey
     * @param Gems_Tracker_Source_SourceInterface $source
     * @return Gems_Tracker_SurveyModel
     */
    public function getSurveyModel(Gems_Tracker_Survey $survey, Gems_Tracker_Source_SourceInterface $source)
    {
        return $this->_loadClass('SurveyModel', true, array($survey, $source));
    }

    /**
     *
     * @param mixed $tokenData Token id or array containing tokendata
     * @return Gems_Tracker_Token
     */
    public function getToken($tokenData)
    {
        if (! $tokenData) {
            throw new Gems_Exception_Coding('Provide at least the token when requesting a token');
        }

        if (is_array($tokenData)) {
             if (!isset($tokenData['gto_id_token'])) {
                 throw new Gems_Exception_Coding('$tokenData array should atleast have a key "gto_id_token" containing the requested token');
             }
            $tokenId = $tokenData['gto_id_token'];
        } else {
            $tokenId = $tokenData;
        }

        if (! isset($this->_tokens[$tokenId])) {
            $this->_tokens[$tokenId] = $this->_loadClass('token', true, array($tokenData));
        }

        return $this->_tokens[$tokenId];
    }

    /**
     *
     * @return type Gems_Tracker_Token_TokenFilter
     */
    public function getTokenFilter()
    {
        return $this->_loadClass('Token_TokenFilter', true, array($this->getTokenLibrary()));
    }

    /**
     * Use this function only within Gems_Tracker!!
     *
     * @return Gems_Tracker_Token_TokenLibrary
     */
    public function getTokenLibrary()
    {
        if (! $this->_tokenLibrary) {
            $this->_tokenLibrary = $this->_loadClass('Token_TokenLibrary', true);
        }

        return $this->_tokenLibrary;
    }

    /**
     * Returns a token model of the specified class with full display information
     *
     * @param string $modelClass Optional class to use instead of StandardTokenModel. Must be subclass.
     * @return Gems_Tracker_Model_StandardTokenModel
     */
    public function getTokenModel($modelClass = 'StandardTokenModel')
    {
        if (! isset($this->_tokenModels[$modelClass])) {
            $this->_tokenModels[$modelClass] = $this->_loadClass('Model_' . $modelClass, true);
            $this->_tokenModels[$modelClass]->applyFormatting();
        }

        return $this->_tokenModels[$modelClass];
    }

    /**
     * Create a select statement on the token table
     *
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function getTokenSelect($fields = '*')
    {
        return $this->_loadClass('Token_TokenSelect', true, array($this->db, $fields));
    }

    /**
     *
     * @return type Gems_Tracker_TokenFilter
     */
    public function getTokenValidator()
    {
        $library = $this->getTokenLibrary();
        $reuse   = $library->hasReuse() ? $library->getReuse() : -1;

        return $this->_loadClass('Token_TokenValidator', true, array($this, $library->getFormat(), $reuse));
    }

    /**
     *
     * @param mixed $trackData Gems track id or array containing gems track data
     * @return Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngine($trackData)
    {
        if (is_array($trackData)) {
            $trackId = $trackData['gtr_id_track'];
        } else {
            $trackId   = $trackData;
            $trackData = false;
        }

        if (! isset($this->_trackEngines[$trackId])) {
            if (! $trackData) {
                $trackData = $this->db->fetchRow("SELECT * FROM gems__tracks WHERE gtr_id_track = ?", $trackId);
            }

            // TODO: patch en extend later
            if (! isset($trackData['gtr_track_class'])) {
                if ($trackData['gtr_track_type'] == 'S') {
                    $trackData['gtr_track_class'] = 'SingleSurveyEngine';
                } else {
                    switch ($trackData['gtr_track_model']) {
                        case 'NewTrackModel':
                            $trackData['gtr_track_class'] = 'AnyStepEngine';
                            break;

                        case 'TrackModel':
                            $trackData['gtr_track_class'] = 'NextStepEngine';
                            break;

                    }
                }
            }
            if (! isset($trackData['gtr_track_class'])) {
                throw new Gems_Exception_Coding('Missing engine class for track ID: ' . $trackId);
            }

            $this->_trackEngines[$trackId] = $this->_loadClass('engine_' . $trackData['gtr_track_class'], true, array($trackData));
        }

        return $this->_trackEngines[$trackId];
    }

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
    public function getTrackEngineClasses()
    {
        static $dummyClasses;

        if (! $dummyClasses) {
            $dummyTrackData['gtr_id_track'] = 0;

            foreach ($this->getTrackEngineClassNames() as $className) {
                $dummyClasses[$className] = $this->_loadClass('engine_' . $className, true, array($dummyTrackData));
            }
        }

        return $dummyClasses;
    }

    /**
     * Returns all registered track engines class names
     *
     * @return array Of classname
     */
    protected function getTrackEngineClassNames()
    {
        return array(
                'AnyStepEngine',
                'NextStepEngine',
                'SingleSurveyEngine',
            );
    }

    /**
     * Return the edit snippets for editing or creating a new track
     *
     * @return array of snippet names for creating a new track engine
     */
    public function getTrackEngineEditSnippets()
    {
        return array('EditTrackEngineSnippet');
    }

    /**
     * Returns all registered track engines classes for use in drop down lists.
     *
     * @param boolean $extended When true return a longer name.
     * @param boolean $userCreatableOnly Return only the classes that can be created by the user interface
     * @return array Of classname => description
     */
    public function getTrackEngineList($extended = false, $userCreatableOnly = false)
    {
        $results = array();
        $dummyTrackData['gtr_id_track'] = 0;

        foreach ($this->getTrackEngineClasses() as $className => $cls) {
            if ((! $userCreatableOnly) || $cls->isUserCreatable()) {
                if ($extended) {
                    $results[$className] = MUtil_Html::raw(sprintf('<strong>%s</strong> %s', $cls->getName(), $cls->getDescription()));

                } else {
                    $results[$className] = $cls->getName();
                }
            }
        }

        return $results;
    }

    /**
     * Simple function for a default track model.
     *
     * @return Gems_Tracker_Model_TrackModel
     */
    public function getTrackModel()
    {
        return $this->_loadClass('Model_TrackModel', true);
    }

    /**
     * Checks the token table to see if there are any answered surveys to be processed
     *
     * If the survey was started (and the token was forwarded to limesurvey) we need to check
     * if is was completed. If so, we might want to check the track the survey is in to enable
     * or disable future rounds
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param int $respondentId  Id of the respondent to check for or NULL
     * @param int $userId        Id of the user who takes the action (for logging)
     * @return bool              Did we find new answers?
     */
    public function processCompletedTokens($respondentId, $userId = null)
    {
        $userId = $this->_checkUserId($userId);
        $tokenSelect = $this->getTokenSelect(true)
                ->onlyActive()
                ->forRespondent($respondentId)
                ->andReceptionCodes()
                ->order('gto_round_order DESC');

        $changes = $this->processTokens($tokenSelect, $userId);

        if (self::$verbose) {
            if ($t = Zend_Registry::get('Zend_Translate')) {
                MUtil_Echo::r($changes->getMessages($t), $t->_('Checks performed'));
            }
        }

        return $changes->hasChanged();
    }

    /**
     * Checks the token table to see if there are any answered surveys to be processed
     *
     * If the survey was started (and the token was forwarded to limesurvey) we need to check
     * if is was completed. If so, we might want to check the track the survey is in to enable
     * or disable future rounds
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param Gems_Tracker_Token_TokenSelect Select statements selecting tokens
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return Gems_Tracker_ChangeTracker What changes have taken places
     */
    protected function processTokens(Gems_Tracker_Token_TokenSelect $tokenSelect, $userId)
    {
        $tokenRows = $tokenSelect->fetchAll();
        $changes   = new Gems_Tracker_ChangeTracker();
        $tokens    = array();

        // FIRST: process each individual token
        foreach ($tokenRows as $tokenData) {

            $changes->checkedTokens++;
            $token = $this->getToken($tokenData);
            $tokens[] = $token;

            if ($result = $token->checkTokenCompletion($userId)) {
                if ($result & Gems_Tracker_Token::COMPLETION_DATACHANGE) {
                    $changes->resultDataChanges++;
                }
                if ($result & Gems_Tracker_Token::COMPLETION_EVENTCHANGE) {
                    $changes->surveyCompletionChanges++;
                }
            }
        }

        $respTracks = array();

        // SECOND: Process the completed rounds (all tokens now have any new values from answered surveys)
        foreach ($tokens as $token) {
            if ($token->isCompleted()) {
                $respTrack = $token->getRespondentTrack();
                $respTracks[$respTrack->getRespondentTrackId()] = $respTrack;

                if ($result = $respTrack->handleRoundCompletion($token, $userId)) {
                    $changes->roundCompletionCauses++;
                    $changes->roundCompletionChanges += $result;
                }
            }
        }

        if ($respTracks) {
            // THIRD: Process date changes
            foreach ($respTracks as $respTrackId => $respTrack) {
                if ($result = $respTrack->checkTrackTokens($userId)) {
                    $changes->tokenDateCauses++;
                    $changes->tokenDateChanges += $result;
                }
            }
        }

        return $changes;
    }

    /**
     * Checks the token table to see if there are any answered surveys to be processed
     *
     * If the survey was started (and the token was forwarded to limesurvey) we need to check
     * if is was completed. If so, we might want to check the track the survey is in to enable
     * or disable future rounds
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param string $batch_id A unique identifier for the current batch
     * @param Gems_Tracker_Token_TokenSelect Select statements selecting tokens
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return Gems_Tracker_Batch_ProcessTokensBatch A batch to process the changes
     */
    protected function processTokensBatch($batch_id, Gems_Tracker_Token_TokenSelect $tokenSelect, $userId)
    {
        $where = implode(' ', $tokenSelect->getSelect()->getPart(Zend_Db_Select::WHERE));

        $batch = $this->_loadClass('Batch_ProcessTokensBatch', true, array($batch_id));

        if (! $batch->isLoaded()) {
            $statement = $tokenSelect->getSelect()->query();
            //Process one row at a time to prevent out of memory errors for really big resultsets
            while ($tokenData  = $statement->fetch()) {
                $batch->addToken($tokenData['gto_id_token'], $userId);
            }
        }

        return $batch;
    }

    /**
     * Recalculates all token dates, timing and results
     * and outputs text messages.
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param int $sourceId A source identifier
     * @param int $userId Id of the user who takes the action (for logging)
     * @param boolean $updateTokens When true each individual token must be synchronized as well
     * @return Gems_Tracker_Batch_SynchronizeSourcesBatch A batch to process the synchronization
     */
    public function synchronizeSourcesBatch($sourceId = null, $userId = null, $updateTokens = false)
    {
        $batch_id = 'source_synch' . ($sourceId ? '_' . $sourceId : '');
        $batch = $this->_loadClass('Batch_SynchronizeSourcesBatch', true, array($batch_id));

        if ($updateTokens  != $batch->getTokenUpdate()) {
            $batch->reset();
        }
        $batch->setTokenUpdate($updateTokens);

        if (! $batch->isLoaded()) {
            if ($sourceId) {
                $sources = array($sourceId);
            } else {
                $select = $this->db->select();
                $select->from('gems__sources', array('gso_id_source'))
                        ->where('gso_active = 1');
                $sources = $this->db->fetchCol($select);
            }

            foreach ($sources as $source) {
                $batch->addSource($source, $userId);
            }
        }

        return $batch;

    }
    /**
     * Recalculates all token dates, timing and results
     * and outputs text messages.
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param string $batch_id A unique identifier for the current batch
     * @param int $userId Id of the user who takes the action (for logging)
     * @param string $cond
     * @return Gems_Tracker_Batch_ProcessTokensBatch A batch to process the changes
     */
    public function recalculateTokensBatch($batch_id, $userId = null, $cond = null)
    {
        $userId = $this->_checkUserId($userId);
        $tokenSelect = $this->getTokenSelect(array('gto_id_token'));
        $tokenSelect->andReceptionCodes(array())
                    ->andRespondents(array())
                    ->andRespondentOrganizations(array())
                    ->andConsents(array());
        if ($cond) {
            $tokenSelect->forWhere($cond);
        }
        //Only select surveys that are active in the source (so we can recalculate inactive in Gems)
        $tokenSelect->andSurveys(array());
        $tokenSelect->forWhere('gsu_surveyor_active = 1');

        self::$verbose = true;
        return $this->processTokensBatch($batch_id, $tokenSelect, $userId);
    }
}
