<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Tracker\Engine\FieldsDefinition;

/**
 * The tracker is the central access point doing anything with tracks or tokens.
 *
 * Tracker contains a number of getXxx functions to create Token, Survey,
 * RespondentTrack, [Survey]SourceInterface and TrackEngine objects.
 *
 * Tracker also offers \MUtil_Model_ModelAbstract children for RespondentTracks,
 * Surveys, Tokens and Tracks.
 *
 * Other object classes accessible through gems_Tracker are TokenLibrary (defines
 * how tokens are created and checked), TokenSelect (\Gems_Tracker_Token_TokenSelect
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
class Gems_Tracker extends \Gems_Loader_TargetLoaderAbstract implements \Gems_Tracker_TrackerInterface
{
    const DB_DATE_FORMAT = 'yyyy-MM-dd';
    const DB_DATETIME_FORMAT = 'yyyy-MM-dd HH:mm:ss';

    /**
     *
     * @var array of \Gems_Tracker_RespondentTrack
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
        'LimeSurvey2m00Database' => 'Lime Survey 2.00 DB',
        );

    /**
     *
     * @var array of \Gems_Tracker_SourceInterface
     */
    private $_sources = array();

    /**
     *
     * @var array of \Gems_Survey
     */
    private $_surveys = array();

    /**
     *
     * @var \Gems_Tracker_TokenLibrary
     */
    private $_tokenLibrary;

    /**
     *
     * @var array of \Gems_Tracker_Model_StandardTokenModel
     */
    private $_tokenModels = array();

    /**
     *
     * @var array of \Gems_Tracker_Token
     */
    private $_tokens = array();

    /**
     *
     * @var array of \Gems_Tracker_Engine_TrackEngineInterface
     */
    private $_trackEngines = array();

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Tracker';

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Log
     */
    protected $logger;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Zend_Session
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
     * @param type $container A container acting as source fro \MUtil_Registry_Source
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
            $userId = $this->currentUser->getUserId();
            if (0 === $userId) {
                $userId = null;
            }
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
     * Checks tracks for changes to the track and round definitions
     * and corrects them.
     *
     * Does recalculate changed tracks
     *
     * @param string $batchId A unique identifier for the current batch
     * @param int $userId Id of the user who takes the action (for logging)
     * @param string $cond Optional where statement for selecting tracks
     * @return \Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    public function checkTrackRounds($batchId, $userId = null, $cond = null)
    {
        $userId = $this->_checkUserId($userId);

        $batch = $this->loader->getTaskRunnerBatch($batchId);
        //Now set the step duration
        $batch->minimalStepDurationMs = 3000;

        if (! $batch->isLoaded()) {
            $respTrackSelect = $this->db->select();
            $respTrackSelect->from('gems__respondent2track', array('gr2t_id_respondent_track'));
            $respTrackSelect->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code', array());
            $respTrackSelect->join('gems__tracks', 'gr2t_id_track = gtr_id_track', array());

            if ($cond) {
                $respTrackSelect->where($cond);
            }
            $respTrackSelect->where('gr2t_active = 1');
            $respTrackSelect->where('grc_success = 1');
            $respTrackSelect->where('gtr_active = 1');
            // Also recaclulate when track was completed: there may be new rounds!
            // $respTrackSelect->where('gr2t_count != gr2t_completed');

            $statement = $respTrackSelect->query();

            //Process one item at a time to prevent out of memory errors for really big resultsets
            while ($respTrackData = $statement->fetch()) {
                $respTrackId = $respTrackData['gr2t_id_respondent_track'];
                $batch->setTask('Tracker_CheckTrackRounds', 'trkchk-' . $respTrackId, $respTrackId, $userId);
                $batch->addToCounter('resptracks');
            }
        }

        return $batch;
    }

    /**
     *
     * @param int $respondentId    The real patientId (grs_id_user), not the patientnr (gr2o_patient_nr)
     * @param int $organizationId
     * @param int $trackId
     * @param int $userId          Id of the user who takes the action (for logging)
     * @param mixed $respTrackData Optional array containing field values or the start date.
     * @param array $trackFieldsData
     * @return \Gems_Tracker_RespondentTrack The newly created track
     */
    public function createRespondentTrack($respondentId, $organizationId, $trackId, $userId, $respTrackData = array(), array $trackFieldsData = array())
    {
        $trackEngine = $this->getTrackEngine($trackId);
        $fieldsDef   = $trackEngine->getFieldsDefinition();

        // Process $respTrackData and gr2t_start_date values
        if ($respTrackData && (! is_array($respTrackData))) {
            // When single value it contains the start date
            $respTrackData = array('gr2t_start_date' => $respTrackData);
        }
        if (! array_key_exists('gr2t_start_date', $respTrackData)) {
            // The start date has to exist.
            $respTrackData['gr2t_start_date'] = new \MUtil_Date();
        }
        $respTrackData['gr2t_id_user']         = $respondentId;
        $respTrackData['gr2t_id_organization'] = $organizationId;

        // Process track fields.
        $usedFields = $fieldsDef->processBeforeSave($trackFieldsData, $respTrackData);
        if ($usedFields && (! array_key_exists('gr2t_track_info', $respTrackData))) {
            $respTrackData['gr2t_track_info'] = $fieldsDef->calculateFieldsInfo($usedFields);
        }

        // Create the filter values for creating the track
        $filter['gtr_id_track'] = $trackId;

        // Load all other new data
        $respTrackModel = $this->getRespondentTrackModel();
        $respTrackData  = $respTrackData + $respTrackModel->loadNew(null, $filter);
        // \MUtil_Echo::track($respTrackData);

        // Save to gems__respondent2track
        $respTrackData  = $respTrackModel->save($respTrackData);
        // \MUtil_Echo::track($respTrackData);

        // Load the track object using only id (otherwise wrong respondent data is loaded)
        $respTrack      = $this->getRespondentTrack($respTrackData['gr2t_id_respondent_track']);

        // Save the fields
        if ($usedFields) {
            $fieldsDef->saveFields($respTrack->getRespondentTrackId(), $usedFields);
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
     * Dynamically load and create a [Gems|Project]_Tracker class
     *
     * @param string $className
     * @param mixed $param1
     * @param mixed $param2
     * @return object
     */
    public function createTrackClass($className, $param1 = null, $param2 = null)
    {
        $params = func_get_args();
        array_shift($params);

        return $this->_loadClass($className, true, $params);
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
            // \MUtil_Echo::track($newValues);
            // Remove up unchanged values
            foreach ($newValues as $name => $value) {
                if (array_key_exists($name, $oldValues)) {
                    /*
                     * Token->setValidFrom will convert to a string representation
                     * but values read from database will be Date objects. Make
                     * sure we compare string with strings
                     */
                    if ($value instanceof \Zend_Date) {
                        $value = $value->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
                    }
                    if ($oldValues[$name] instanceof \Zend_Date) {
                        $oldValues[$name] = $oldValues[$name]->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
                    }
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
     * Returns an array of all field id's for all tracks that have a code id
     *
     * @return array id => code
     */
    public function getAllCodeFields()
    {
        static $fields = false; // Using static so it will be refreshed once per request

        if ($fields === false) {
            $fields = array();
            $model  = $this->createTrackClass('Model_FieldMaintenanceModel');
            $rows   = $model->load(array('gtf_field_code IS NOT NULL'), array('gtf_field_code' => SORT_ASC));

            if ($rows) {
                foreach ($rows as $row) {
                    $key = FieldsDefinition::makeKey($row['sub'], $row['gtf_id_field']);
                    $fields[$key] = $row['gtf_field_code'];
                }
            }
        }

        return $fields;
    }

    /**
     * Get an appointment object
     *
     * @param mixed $appointmentData Appointment id or array containing appintment data
     * @return \Gems_Agenda_Appointment
     */
    public function getAppointment($appointmentData)
    {
        return $this->loader->getAgenda()->getAppointment($appointmentData);
    }

    /**
     * Returns a form to ask for a token
     *
     * @param mixed $args_array \MUtil_Ra::args array for Form initiation.
     * @return \Gems_Tracker_Form_AskTokenForm
     */
    public function getAskTokenForm($args_array = null)
    {
        $args = \MUtil_Ra::args(func_get_args());

        return $this->_loadClass('Form_AskTokenForm', true, array($args));
    }

    /**
     *
     * @param mixed $respTrackData Track id or array containing trackdata
     * @return \Gems_Tracker_RespondentTrack
     */
    public function getRespondentTrack($respTrackData)
    {
        if (is_array($respTrackData)) {
            $respTracksId = $respTrackData['gr2t_id_respondent_track'];
        } else {
            $respTracksId = $respTrackData;
        }

        if (isset($this->_respTracks[$respTracksId])) {
            if (is_array($respTrackData)) {
                // Refresh the data when called with an array of respTrackData
                $this->_respTracks[$respTracksId]->refresh($respTrackData);
            }
        } else {
            $this->_respTracks[$respTracksId] = $this->_loadClass('respondentTrack', true, array($respTrackData));
        }

        return $this->_respTracks[$respTracksId];
    }

    /**
     * Load project specific model or general Gems model otherwise
     *
     * @return \Gems_Tracker_Model_RespondentTrackModel
     */
    public function getRespondentTrackModel()
    {
        return $this->_loadClass('Model_RespondentTrackModel', true);
    }

    /**
     * Get all tracks for a respondent
     *
     * Specify the optional $order to sort other than on start date
     *
     * @param int $respondentId
     * @param int $organizationId
     * @param mixed $order The column(s) and direction to order by
     * @return array of \Gems_Tracker_RespondentTrack
     */
    public function getRespondentTracks($respondentId, $organizationId, $order = array('gr2t_start_date'))
    {
        $select = $this->db->select()
                ->from('gems__respondent2track')
                ->joinInner('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code')
                ->where('gr2t_id_user = ? AND gr2t_id_organization = ?');
        if (!is_null($order)) {
            $select->order($order);
        }
        $rows   = $this->db->fetchAll($select, array($respondentId, $organizationId));
        $tracks = array();

        foreach ($rows as $row) {
            $tracks[$row['gr2t_id_respondent_track']] = $this->getRespondentTrack($row);
        }

        return $tracks;
    }

    /**
     * Retrieve a SourceInterface with a given id
     *
     * Should only be called by \Gems_Tracker, \Gems_Tracker_Survey or \Gems_Tracker_Token (or should
     * this one use \Gems_Tracker_Survey instead?)
     *
     * @param mixed $sourceData Gems source id or array containing gems source data
     * @return \Gems_Tracker_Source_SourceInterface
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
                throw new \Gems_Exception_Coding('Missing source class for source ID: ' . $sourceId);
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
        // TODO: this should be moved to \Gems_Tracker_Source_SourceInterface,
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
     * @return \Gems_Tracker_Survey
     */
    public function getSurvey($surveyData)
    {
        if (is_array($surveyData)) {
            $surveyId = $surveyData['gsu_id_survey'];
        } else {
            $surveyId = $surveyData;
        }

        if ($surveyId == null || ! isset($this->_surveys[$surveyId])) {
            $this->_surveys[$surveyId] = $this->_loadClass('survey', true, array($surveyData));
        }

        return $this->_surveys[$surveyId];
    }

    /**
     *
     * @param mixed $sourceSurveyId The source survey id
     * @param int $sourceId The gems source id of the source
     * @return \Gems_Tracker_Survey
     */
    public function getSurveyBySourceId($sourceSurveyId, $sourceId)
    {
        $surveyData = $this->db->fetchRow("SELECT * FROM gems__surveys WHERE gsu_id_source = ? AND gsu_surveyor_id = ?", array($sourceId, $sourceSurveyId));

        if (! $surveyData) {
            static $newcount = -1;

            $surveyData['gsu_id_survey']   = $newcount--;
            $surveyData['gsu_surveyor_id'] = $sourceSurveyId;
            $surveyData['gsu_id_source']   = $sourceId;

            // \MUtil_Echo::track($surveyData);
        }

        return $this->getSurvey($surveyData);
    }

    /**
     *
     * @param \Gems_Tracker_Survey $survey
     * @param \Gems_Tracker_Source_SourceInterface $source
     * @return \Gems_Tracker_SurveyModel
     */
    public function getSurveyModel(\Gems_Tracker_Survey $survey, \Gems_Tracker_Source_SourceInterface $source)
    {
        return $this->_loadClass('SurveyModel', true, array($survey, $source));
    }

    /**
     *
     * @param mixed $tokenData Token id or array containing tokendata
     * @return \Gems_Tracker_Token
     */
    public function getToken($tokenData)
    {
        if (! $tokenData) {
            throw new \Gems_Exception_Coding('Provide at least the token when requesting a token');
        }

        if (is_array($tokenData)) {
             if (!isset($tokenData['gto_id_token'])) {
                 throw new \Gems_Exception_Coding(
                         '$tokenData array should at least have a key "gto_id_token" containing the requested token id'
                         );
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
     * @return \Gems_Tracker_Token_TokenFilter
     */
    public function getTokenFilter()
    {
        return $this->_loadClass('Token_TokenFilter', true, array($this->getTokenLibrary()));
    }

    /**
     * Use this function only within \Gems_Tracker!!
     *
     * @return \Gems_Tracker_Token_TokenLibrary
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
     * @return \Gems_Tracker_Model_StandardTokenModel
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
     * @return \Gems_Tracker_Token_TokenSelect
     */
    public function getTokenSelect($fields = '*')
    {
        return $this->_loadClass('Token_TokenSelect', true, array($this->db, $fields));
    }

    /**
     *
     * @return \Gems_Tracker_Token_TokenValidator
     */
    public function getTokenValidator()
    {
        return $this->_loadClass('Token_TokenValidator', true);
    }

    /**
     * Get the allowed display groups for tracks in this project.
     *
     * @return array
     */
    public function getTrackDisplayGroups()
    {
        return array(
            'tracks'      => $this->translate->_('Tracks'),
            'respondents' => $this->translate->_('Respondent'),
            'staff'       => $this->translate->_('Staff'),
        );
    }

    /**
     *
     * @param mixed $trackData Gems track id or array containing gems track data
     * @return \Gems_Tracker_Engine_TrackEngineInterface
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
                $trackData['gtr_track_class'] = 'AnyStepEngine';
            }

            $this->_trackEngines[$trackId] = $this->_loadClass('engine_' . $trackData['gtr_track_class'], true, array($trackData));
        }

        return $this->_trackEngines[$trackId];
    }

    /**
     *
     * @param string $trackCode Track code or whole word part of code to find track by
     * @return \Gems_Tracker_Engine_TrackEngineInterface or null when not found
     */
    public function getTrackEngineByCode($trackCode)
    {
        $trackData = $this->db->fetchRow(
                "SELECT * FROM gems__tracks
                    WHERE CONCAT(' ', gtr_code, ' ') LIKE ? AND
                        gtr_active = 1 AND
                        gtr_date_start <= CURRENT_DATE AND
                        (gtr_date_until IS NULL OR gtr_date_until <= CURRENT_DATE)
                    ORDER BY gtr_date_start",
                "% $trackCode %"
                );

        if ($trackData) {
            return $this->getTrackEngine($trackData);
        }
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
     * @return array Of \Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngineClasses()
    {
        static $dummyClasses;

        if (! $dummyClasses) {
            $dummyTrackData['gtr_id_track'] = 0;

            foreach ($this->getTrackEngineClassNames() as $className) {
                $dummyClasses[$className] = $this->_loadClass('Engine_' . $className, true, array($dummyTrackData));
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
            );
    }

    /**
     * Return the edit snippets for editing or creating a new track
     *
     * @return array of snippet names for creating a new track engine
     */
    public function getTrackEngineEditSnippets()
    {
        return array('Tracker\\EditTrackEngineSnippet');
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
                    $results[$className] = \MUtil_Html::raw(sprintf('<strong>%s</strong> %s', $cls->getName(), $cls->getDescription()));

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
     * @return \Gems_Tracker_Model_TrackModel
     */
    public function getTrackModel()
    {
        return $this->_loadClass('Model_TrackModel', true);
    }

    /**
     * Checks the token table to see if there are any answered surveys to be processed and loads those tasks
     *
     * If the survey was started (and the token was forwarded to limesurvey) we need to check
     * if is was completed. If so, we might want to check the track the survey is in to enable
     * or disable future rounds
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param \Gems_Task_TaskRunnerBatch $batch The batch to load
     * @param int $respondentId   Id of the respondent to check for or NULL
     * @param int $userId         Id of the user who takes the action (for logging)
     * @param int $orgId          Optional Id of the organization to check for
     * @param boolean $quickCheck Check only tokens with recent gto_start_time's
     * @return bool               Did we find new answers?
     */
    public function loadCompletedTokensBatch(\Gems_Task_TaskRunnerBatch $batch, $respondentId = null, $userId = null, $orgId = null, $quickCheck = false)
    {
        $userId = $this->_checkUserId($userId);

        $tokenSelect = $this->getTokenSelect(array('gto_id_token'));
        $tokenSelect->onlyActive($quickCheck)
                    ->forRespondent($respondentId)
                    ->andSurveys(array('gsu_surveyor_id'))
                    ->forWhere('gsu_surveyor_active = 1')
                    ->order('gsu_surveyor_id');

        if (null !== $orgId) {
            $tokenSelect->forWhere('gto_id_organization = ?', $orgId);
        }

        $statement = $tokenSelect->getSelect()->query();
        //Process one row at a time to prevent out of memory errors for really big resultsets
        $tokens = array();
        $tokencount = 0;
        $activeId = 0;
        $maxCount = 100;    // Arbitrary value, change as needed
        while ($tokenData = $statement->fetch()) {
            $tokenId = $tokenData['gto_id_token'];
            $surveyorId = $tokenData['gsu_surveyor_id'];
            if ($activeId <> $surveyorId || count($tokens) > $maxCount) {
                // Flush
                if (count($tokens)> 0) {
                    $batch->addTask('Tracker_BulkCheckTokenCompletion', $tokens, $userId);
                }

                $activeId = $surveyorId;
                $tokens = array();
            }
            $tokens[] = $tokenId;

            $batch->addToCounter('tokens');
        }
        if (count($tokens)> 0) {
            $batch->addTask('Tracker_BulkCheckTokenCompletion', $tokens, $userId);
        }
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
     * @param int $respondentId   Id of the respondent to check for or NULL
     * @param int $userId         Id of the user who takes the action (for logging)
     * @param int $orgId          Optional Id of the organization to check for
     * @param boolean $quickCheck Check only tokens with recent gto_start_time's
     * @return bool               Did we find new answers?
     */
    public function processCompletedTokens($respondentId, $userId = null, $orgId = null, $quickCheck = false)
    {
        $batch = $this->loader->getTaskRunnerBatch('completed');

        if (! $batch->isLoaded()) {
            $this->loadCompletedTokensBatch($batch, $respondentId, $userId, $orgId, $quickCheck);
        }

        $batch->runAll();
        if ($batch->getCounter('resultDataChanges') > 0 || $batch->getCounter('surveyCompletionChanges')>0) {
            $changed = true;
        } else {
            $changed = false;
        }

        $batch->reset();
        return $changed;
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
     * @param \Gems_Tracker_Token_TokenSelect Select statements selecting tokens
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return \Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    protected function processTokensBatch($batch_id, \Gems_Tracker_Token_TokenSelect $tokenSelect, $userId)
    {
        $where = implode(' ', $tokenSelect->getSelect()->getPart(\Zend_Db_Select::WHERE));

        $batch = $this->loader->getTaskRunnerBatch($batch_id);

        //Now set the step duration
        $batch->minimalStepDurationMs = 3000;

        if (! $batch->isLoaded()) {
            $statement = $tokenSelect->getSelect()->query();
            //Process one row at a time to prevent out of memory errors for really big resultsets
            while ($tokenData  = $statement->fetch()) {
                $tokenId = $tokenData['gto_id_token'];
                $batch->setTask('Tracker_CheckTokenCompletion', 'tokchk-' . $tokenId, $tokenId, $userId);
                $batch->addToCounter('tokens');
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
     * @return \Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    public function recalculateTokens($batch_id, $userId = null, $cond = null)
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

    /**
     * Recalculates the fields in tracks.
     *
     * Does recalculate changed tracks
     *
     * @param string $batchId A unique identifier for the current batch
     * @param string $cond Optional where statement for selecting tracks
     * @return \Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    public function recalcTrackFields($batchId, $cond = null)
    {
        $respTrackSelect = $this->db->select();
        $respTrackSelect->from('gems__respondent2track', array('gr2t_id_respondent_track'));
        $respTrackSelect->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code', array());
        $respTrackSelect->join('gems__tracks', 'gr2t_id_track = gtr_id_track', array());

        if ($cond) {
            $respTrackSelect->where($cond);
        }
        $respTrackSelect->where('gr2t_active = 1');
        $respTrackSelect->where('grc_success = 1');
        $respTrackSelect->where('gtr_active = 1');
        // Also recaclulate when track was completed: there may be new rounds!
        // $respTrackSelect->where('gr2t_count != gr2t_completed');

        $batch = $this->loader->getTaskRunnerBatch($batchId);
        //Now set the step duration
        $batch->minimalStepDurationMs = 3000;

        if (! $batch->isLoaded()) {
            $statement = $respTrackSelect->query();
            //Process one item at a time to prevent out of memory errors for really big resultsets
            while ($respTrackData  = $statement->fetch()) {
                $respTrackId = $respTrackData['gr2t_id_respondent_track'];
                $batch->setTask('Tracker_RecalculateFields', 'trkfcalc-' . $respTrackId, $respTrackId);
                $batch->addToCounter('resptracks');
            }
        }

        return $batch;
    }

    /**
     * Refreshes the tokens in the source
     *
     * @param string $batch_id A unique identifier for the current batch
     * @param string $cond An optional where statement
     * @return \Gems_Task_TaskRunnerBatch A batch to process the changes
     */
    public function refreshTokenAttributes($batch_id, $cond = null)
    {
        $batch = $this->loader->getTaskRunnerBatch($batch_id);

        if (! $batch->isLoaded()) {
            $tokenSelect = $this->getTokenSelect(array('gto_id_token'));
            $tokenSelect->andSurveys(array())
                        ->forWhere('gsu_surveyor_active = 1')
                        ->forWhere('gto_in_source = 1');

            if ($cond) {
                // Add all connections for filtering, but select only surveys that are active in the source
                $tokenSelect->andReceptionCodes(array())
                        ->andRespondents(array())
                        ->andRespondentOrganizations(array())
                        ->andConsents(array())
                        ->forWhere($cond);
            }

            foreach ($this->db->fetchCol($tokenSelect->getSelect()) as $token) {
                $batch->addTask('Tracker_RefreshTokenAttributes', $token);
            }
        }
        self::$verbose = true;

        return $batch;
    }

    /**
     * Remove token from cache for saving memory
     *
     * @param string|\Gems_Tracker_Token $token
     * @return \Gems_Tracker (continuation pattern)
     */
    public function removeToken($token)
    {
        if ($token instanceof \Gems_Tracker_Token) {
            $tokenId = $token->getTokenId();
        } else {
            $tokenId = $token;
        }
        unset($this->_tokens[$tokenId]);

        return $this;
    }

    /**
     * Recalculates all token dates, timing and results
     * and outputs text messages.
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param int $sourceId A source identifier
     * @param int $userId Id of the user who takes the action (for logging)
     * @return \Gems_Task_TaskRunnerBatch A batch to process the synchronization
     */
    public function synchronizeSources($sourceId = null, $userId = null)
    {
        $batch_id = 'source_synch' . ($sourceId ? '_' . $sourceId : '');
        $batch = $this->loader->getTaskRunnerBatch($batch_id);

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
                $batch->addTask('Tracker_SourceSyncSurveys', $source, $userId);
                // Reset cache after basic synch
                $batch->addTask('CleanCache');
                // Reset cache after field synch
                $batch->addTask('AddTask', 'CleanCache');
                $batch->addTask('AddTask', 'Tracker\\UpdateSyncDate', $source, $userId);
            }
        }

        return $batch;

    }
}