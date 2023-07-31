<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Db\ResultFetcher;
use Gems\Exception\Coding;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\SurveyRepository;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Tracker\Form\AskTokenForm;
use Gems\Tracker\Model\RespondentTrackModel;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\Tracker\Model\TrackModel;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\Source\SourceInterface;
use Gems\Tracker\Survey;
use Gems\Tracker\SurveyModel;
use Gems\Tracker\Token;
use Gems\Tracker\Token\TokenFilter;
use Gems\Tracker\Token\TokenLibrary;
use Gems\Tracker\Token\LaminasTokenSelect;
use Gems\Tracker\Token\TokenSelect;
use Gems\Tracker\Token\TokenValidator;
use Gems\Tracker\TrackerInterface;
use Gems\User\Mask\MaskRepository;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Mezzio\Session\SessionInterface;
use MUtil\Ra;
use MUtil\Translate\Translator;
use Zalt\Loader\ProjectOverloader;

/**
 * The tracker is the central access point doing anything with tracks or tokens.
 *
 * Tracker contains a number of getXxx functions to create Token, Survey,
 * RespondentTrack, [Survey]SourceInterface and TrackEngine objects.
 *
 * Tracker also offers \MUtil\Model\ModelAbstract children for RespondentTracks,
 * Surveys, Tokens and Tracks.
 *
 * Other object classes accessible through gems_Tracker are TokenLibrary (defines
 * how tokens are created and checked), TokenSelect (\Gems\Tracker\Token\TokenSelect
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
class Tracker implements TrackerInterface
{
    public const DB_DATE_FORMAT = 'Y-m-d';
    public const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     *
     * @var array of \Gems\Tracker\RespondentTrack
     */
    private array $_respTracks = [];

    /**
     * This variable holds all registered source classes, may be changed in derived classes
     *
     * @var array Of classname => description
     */
    protected array $_sourceClasses = [
        'LimeSurvey3m00Database' => 'Lime Survey 3.00 DB',
        'LimeSurvey5m00Database' => 'Lime Survey 5.00 DB',
    ];

    /**
     *
     * @var array of \Gems\Tracker_SourceInterface
     */
    private array $_sources = [];

    /**
     *
     * @var array of \Gems_Survey
     */
    private array $_surveys = [];

    /**
     *
     * @var TokenLibrary
     */
    private ?TokenLibrary $_tokenLibrary = null;

    /**
     *
     * @var array of \Gems\Tracker\Model\StandardTokenModel
     */
    private array $_tokenModels = [];

    /**
     *
     * @var array of \Gems\Tracker\Token
     */
    private array $_tokens = [];

    /**
     *
     * @var array of \Gems\Tracker\Engine\TrackEngineInterface
     */
    private array $_trackEngines = [];

    protected ?int $currentUserId = null;

    /**
     * Set to true to get detailed information on all tracker actions
     *
     * @var boolean
     */
    public static $verbose = false;

    public function __construct(
        protected readonly Translator $translator,
        protected readonly MaskRepository $maskRepository,
        protected readonly ProjectOverloader $overLoader,
        CurrentUserRepository $currentUserRepository,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly Model $modelLoader,
        protected readonly SurveyRepository $surveyRepository,
    )
    {
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    /**
     * Replaces a null or empty userId with that of the current user
     *
     * @param int $userId
     * @return int
     */
    private function _checkUserId(int|null $userId = null): int
    {
        if ($userId) {
            return $userId;
        }
        if ($this->currentUserId) {
            return $this->currentUserId;
        }

        return UserLoader::UNKNOWN_USER_ID;
    }

    /**
     * @inheritdoc
     */
    public function checkTrackRounds(SessionInterface $session, string $batchId, int|null $userId = null, array $cond = []): TaskRunnerBatch
    {
        $userId = $this->_checkUserId($userId);

        $batch = new TaskRunnerBatch($batchId, $this->overLoader, $session);
        //Now set the step duration
        $batch->minimalStepDurationMs = 3000;

        if (! $batch->isLoaded()) {
            $respTrackSelect = $this->resultFetcher->getSelect();
            $respTrackSelect->from('gems__respondent2track')
                ->columns(['gr2t_id_respondent_track'])
                ->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code', [])
                ->join('gems__tracks', 'gr2t_id_track = gtr_id_track', []);

            if ($cond) {
                $respTrackSelect->where($cond);
            }
            $respTrackSelect->where([
                'gr2t_active' => 1,
                'grc_success' => 1,
                'gtr_active' => 1,
            ]);
            // Also recaclulate when track was completed: there may be new rounds!
            // $respTrackSelect->where('gr2t_count != gr2t_completed');

            $resultSet = $this->resultFetcher->query($respTrackSelect);

            if ($resultSet instanceof ResultSet) {
                //Process one item at a time to prevent out of memory errors for really big resultsets
                while ($resultSet->valid()) {
                    $respTrackData = $resultSet->current();
                    $respTrackId = $respTrackData['gr2t_id_respondent_track'];
                    $batch->setTask('Tracker\\CheckTrackRounds', 'trkchk-' . $respTrackId, $respTrackId, $userId);
                    $batch->addToCounter('resptracks');
                    $resultSet->next();
                }
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
     * @param SessionInterface $session
     * @return \Gems\Tracker\RespondentTrack The newly created track
     */
    public function createRespondentTrack(int $respondentId, int $organizationId, int $trackId, int|null $userId = null, int|array|null $respTrackData = [], array $trackFieldsData = [], SessionInterface $session = null): RespondentTrack
    {
        $userId = $this->_checkUserId($userId);
        $trackEngine = $this->getTrackEngine($trackId);

        // Process $respTrackData and gr2t_start_date values
        if ($respTrackData && (! is_array($respTrackData))) {
            // When single value it contains the start date
            $respTrackData = array('gr2t_start_date' => $respTrackData);
        }
        if (! array_key_exists('gr2t_start_date', $respTrackData)) {
            // The start date has to exist.
            $respTrackData['gr2t_start_date'] = new \DateTimeImmutable();
        }
        $respTrackData['gr2t_id_user']         = $respondentId;
        $respTrackData['gr2t_id_organization'] = $organizationId;
        if (! array_key_exists('gr2t_reception_code', $respTrackData)) {
            // The start date has to exist.
            $respTrackData['gr2t_reception_code'] = ReceptionCodeRepository::RECEPTION_OK;
        }

        // Create the filter values for creating the track
        $filter['gtr_id_track']         = $trackId;
        $filter['gr2t_id_user']         = $respondentId;
        $filter['gr2t_id_organization'] = $organizationId;

        // Load all other new data
        $respTrackModel = $this->getRespondentTrackModel();
        // Make sure the default are loaded
        $respTrackModel->applyEditSettings($trackEngine);
        $respTrackData  = $respTrackData + $respTrackModel->loadNew(null, $filter);
        // \MUtil\EchoOut\EchoOut::track($respTrackData);

        // Save to gems__respondent2track
        $respTrackData  = $respTrackModel->save($respTrackData);
        // \MUtil\EchoOut\EchoOut::track($respTrackData);

        // Load the track object using only id (otherwise wrong respondent data is loaded)
        $respTrack      = $this->getRespondentTrack($respTrackData['gr2t_id_respondent_track']);

        // Save the fields, this also updates track info in needed when the fields are empty
        $respTrack->setFieldData($trackFieldsData);

        // Create the actual tokens!!!!
        $trackEngine->checkRoundsFor($respTrack, $session, $userId);

        return $respTrack;
    }

    /**
     * Creates a new token with a new random token Id
     *
     * @param array $tokenData The other new data for the token
     * @param int $userId Id of the user who takes the action (for logging)
     * @return string
     */
    public function createToken(array $tokenData, int|null $userId = null): string
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
    public function createTrackClass(string $className, mixed $param1 = null, mixed $param2 = null): object
    {
        $params = func_get_args();
        array_shift($params);

        if (!class_exists($className)) {
            $className = "Tracker\\$className";
        }

        return $this->overLoader->create($className, ...$params);
    }

    /**
     * Utility function for detecting unchanged values.
     *
     * @param array $oldValues
     * @param array $newValues
     * @return array
     */
    public function filterChangesOnly(array $oldValues, array &$newValues): array
    {
        if ($newValues && $oldValues) {
            // \MUtil\EchoOut\EchoOut::track($newValues);
            // Remove up unchanged values
            foreach ($newValues as $name => $value) {
                if (array_key_exists($name, $oldValues)) {
                    /*
                     * Token->setValidFrom will convert to a string representation
                     * but values read from database will be Date objects. Make
                     * sure we compare string with strings
                     */
                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format(\Gems\Tracker::DB_DATETIME_FORMAT);
                    }
                    if ($oldValues[$name] instanceof \DateTimeInterface) {
                        $oldValues[$name] = $oldValues[$name]->format(\Gems\Tracker::DB_DATETIME_FORMAT);
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
    public function filterToken(string $tokenId): string
    {
        return $this->getTokenLibrary()->filter($tokenId);
    }

    /**
     * Returns an array of all field id's for all tracks that have a code id
     *
     * @return array id => code
     */
    public function getAllCodeFields(): array
    {
        static $fields = false; // Using static so it will be refreshed once per request

        if ($fields === false) {
            $fields = [];
            $model  = $this->createTrackClass('Model\\FieldMaintenanceModel');
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
     * Returns a form to ask for a token
     *
     * @param mixed $args_array \MUtil\Ra::args array for Form initiation.
     * @return \Gems\Tracker\Form\AskTokenForm
     */
    public function getAskTokenForm(mixed $args_array = null): AskTokenForm
    {
        $args = Ra::args(func_get_args());
        /**
         * @var AskTokenForm
         */
        return $this->overLoader->create('Tracker\\Form\\AskTokenForm', $args);
    }

    /**
     *
     * @param mixed $respTrackData Track id or array containing trackdata
     * @return \Gems\Tracker\RespondentTrack
     */
    public function getRespondentTrack(int|array $respTrackData): RespondentTrack
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
            $this->_respTracks[$respTracksId] = $this->overLoader->create('Tracker\\RespondentTrack', $respTrackData, $this->currentUserId);
        }

        return $this->_respTracks[$respTracksId];
    }

    /**
     * Load project specific model or general \Gems model otherwise
     *
     * @return \Gems\Tracker\Model\RespondentTrackModel
     */
    public function getRespondentTrackModel(): RespondentTrackModel
    {
        /**
         * @var RespondentTrackModel $model
         */
        $model = $this->overLoader->create('Tracker\\Model\\RespondentTrackModel');

        $model->setMaskRepository($this->maskRepository);

        return $model;
    }

    /**
     * Get all tracks for a respondent
     *
     * Specify the optional $order to sort other than on start date
     *
     * @param int $respondentId
     * @param int $organizationId
     * @param mixed $order The column(s) and direction to order by
     * @return \Gems\Tracker\RespondentTrack[]
     */
    public function getRespondentTracks(int $respondentId, int $organizationId, string|array|Expression $order = ['gr2t_start_date']): array
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2track')
            ->join('gems__tracks', 'gr2t_id_track = gtr_id_track')
            ->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code')
            ->where([
                'gr2t_id_user' => $respondentId,
                'gr2t_id_organization' => $organizationId,
            ]);



        if (!is_null($order)) {
            $select->order($order);
        }
        $rows   = $this->resultFetcher->fetchAll($select);

        $tracks = [];
        foreach ($rows as $row) {
            $tracks[$row['gr2t_id_respondent_track']] = $this->getRespondentTrack($row);
        }

        return $tracks;
    }

    /**
     * Retrieve a SourceInterface with a given id
     *
     * Should only be called by \Gems\Tracker, \Gems\Tracker\Survey or \Gems\Tracker\Token (or should
     * this one use Gems\Tracker\Survey instead?)
     *
     * @param mixed $sourceData \Gems source id or array containing gems source data
     * @return \Gems\Tracker\Source\SourceInterface
     */
    public function getSource(int|array $sourceData): SourceInterface
    {
        if (is_array($sourceData)) {
            $sourceId = $sourceData['gso_id_source'];
        } else {
            $sourceId   = $sourceData;
            $sourceData = false;
        }

        if (! isset($this->_sources[$sourceId])) {
            if (! $sourceData) {
                $select = $this->resultFetcher->getSelect('gems__sources')
                    ->where([
                       'gso_id_source' => $sourceId,
                    ]);
                $sourceData = $this->resultFetcher->fetchRow($select);
            }

            if (! isset($sourceData['gso_ls_class'])) {
                throw new \Gems\Exception\Coding('Missing source class for source ID: ' . $sourceId);
            }

            $this->_sources[$sourceId] = $this->overLoader->create('Tracker\\Source\\' . $sourceData['gso_ls_class'], $sourceData);
        }

        return $this->_sources[$sourceId];
    }

    /**
     * Returns all registered source classes
     *
     * @return array Of classname => description
     */
    public function getSourceClasses(): array
    {
        return $this->_sourceClasses;
    }

    /**
     * Returns all registered database source classes
     *
     * @return array Of classname => description
     */
    public function getSourceDatabaseClasses(): array
    {
        // TODO: this should be moved to \Gems\Tracker\Source\SourceInterface,
        // but do not have time to implement is of minor importance at this moment.

        // If the project uses Pdo database, use Pdo classes, otherwise MySQL
        if ($this->resultFetcher->getAdapter()->getDriver() instanceof Pdo) {
            return [
                '' => '-- none --',
                'Pdo_Mysql' => 'MySQL (PDO)',
                'Pdo_Mssql' => 'SQL Server (PDO)',
            ];
        } else {
            return [
                '' => '-- none --',
                'Mysqli' => 'MySQL',
                'Sqlsrv' => 'SQL Server',
            ];
        }
    }

    /**
     *
     * @param mixed $surveyData \Gems survey id or array containing gems survey data
     * @return Survey
     */
    public function getSurvey(int|array|null $surveyData): Survey
    {
        if (is_array($surveyData)) {
            $surveyId = $surveyData['gsu_id_survey'];
        } else {
            $surveyId = $surveyData;
        }

        if ($surveyId == null || ! isset($this->_surveys[$surveyId])) {
            $survey = $this->surveyRepository->getSurvey($surveyData);
            $surveyId = $survey->getSurveyId();
            $this->_surveys[$surveyId] = $survey;
        }

        return $this->_surveys[$surveyId];
    }

    /**
     *
     * @param mixed $sourceSurveyId The source survey id
     * @param int $sourceId The gems source id of the source
     * @return Survey
     */
    public function getSurveyBySourceId(int|string $sourceSurveyId, int $sourceId): Survey
    {
        $select = $this->resultFetcher->getSelect('gems__surveys');
        $select->where([
            'gsu_id_source' => $sourceId,
            'gsu_surveyor_id' => $sourceSurveyId,
        ]);

        $surveyData = $this->resultFetcher->fetchRow($select);


        if (!is_array($surveyData)) {
            Survey::$newSurveyCount++;
            $surveyData['gsu_id_survey'] = -Survey::$newSurveyCount;
            $surveyData['gsu_surveyor_id'] = $sourceSurveyId;
            $surveyData['gsu_id_source'] = $sourceId;
            // \MUtil\EchoOut\EchoOut::track($surveyData);
        }

        return $this->getSurvey($surveyData);
    }

    /**
     *
     * @param Survey $survey
     * @param SourceInterface $source
     * @return SurveyModel
     */
    public function getSurveyModel(Survey $survey, SourceInterface $source): SurveyModel
    {
        /**
         * @var SurveyModel
         */
        return $this->overLoader->create('Tracker\\SurveyModel', $survey, $source);
    }

    /**
     *
     * @param int|array $tokenData Token id or array containing tokendata
     * @return Token
     */
    public function getToken(string|array $tokenData): Token
    {
        if (! $tokenData) {
            throw new Coding('Provide at least the token when requesting a token');
        }

        if (is_array($tokenData)) {
             if (!isset($tokenData['gto_id_token'])) {
                 throw new Coding(
                         '$tokenData array should at least have a key "gto_id_token" containing the requested token id'
                         );
             }
            $tokenId = $tokenData['gto_id_token'];
        } else {
            $tokenId = $tokenData;
        }

        if (! isset($this->_tokens[$tokenId])) {
            $this->_tokens[$tokenId] = $this->overLoader->create('Tracker\\Token', $tokenData);
        }

        return $this->_tokens[$tokenId];
    }

    /**
     *
     * @return TokenFilter
     */
    public function getTokenFilter(): TokenFilter
    {
        /**
         * @var TokenFilter
         */
        return $this->overLoader->create('Tracker\\Token\\TokenFilter', $this->getTokenLibrary());
    }

    /**
     * Use this function only within \Gems\Tracker!!
     *
     * @return TokenLibrary
     */
    public function getTokenLibrary(): TokenLibrary
    {
        if (! $this->_tokenLibrary) {
            /**
             * @var $tokenLibrary TokenLibrary
             */
            $tokenLibrary = $this->overLoader->create('Tracker\\Token\\TokenLibrary');
            $this->_tokenLibrary = $tokenLibrary;
        }

        return $this->_tokenLibrary;
    }

    /**
     * Returns a token model of the specified class with full display information
     *
     * @param string $modelClass Optional class to use instead of StandardTokenModel. Must be subclass.
     * @return StandardTokenModel
     */
    public function getTokenModel(string $modelClass = 'StandardTokenModel'): StandardTokenModel
    {
        if (! isset($this->_tokenModels[$modelClass])) {
            /**
             * @var StandardTokenModel $model
             */
            $model = $this->overLoader->create('Tracker\\Model\\' . $modelClass);
            $this->_tokenModels[$modelClass] = $model;
            $this->_tokenModels[$modelClass]->setMaskRepository($this->maskRepository);
            $this->_tokenModels[$modelClass]->applyFormatting();
            $this->modelLoader->addDatabaseTranslations($this->_tokenModels[$modelClass]);
        }

        return $this->_tokenModels[$modelClass];
    }

    /**
     * Create a select statement on the token table
     *
     * @return TokenSelect
     */
    public function getTokenSelect(string|array $fields = '*'): TokenSelect
    {
        /**
         * @var TokenSelect $tokenSelect
         */
        $tokenSelect = $this->overLoader->create('Tracker\\Token\\TokenSelect');

        $tokenSelect->columns($fields);
        return $tokenSelect;
    }

    /**
     *
     * @return TokenValidator
     */
    public function getTokenValidator(string $clientIpAddress = null): TokenValidator
    {
        /**
         * @var $tokenValidator TokenValidator
         */
        $tokenValidator = $this->overLoader->create('Tracker\\Token\\TokenValidator');
        if ($clientIpAddress !== null) {
            $tokenValidator->setClientIp($clientIpAddress);
        }
        return $tokenValidator;
    }

    /**
     * Get the allowed display groups for tracks in this project.
     *
     * @return array
     */
    public function getTrackDisplayGroups(): array
    {
        return [
            'tracks'      => $this->translator->_('Tracks'),
            'respondents' => $this->translator->_('Respondent'),
            'staff'       => $this->translator->_('Staff'),
        ];
    }

    /**
     *
     * @param int|array $trackData \Gems track id or array containing gems track data
     * @return TrackEngineInterface
     */
    public function getTrackEngine(int|array $trackData): TrackEngineInterface
    {
        if (is_array($trackData)) {
            $trackId   = $trackData['gtr_id_track'];
        } else {
            $trackId   = $trackData;
            $trackData = false;
        }

        if (! isset($this->_trackEngines[$trackId])) {
            if (! $trackData) {
                $select = $this->resultFetcher->getSelect('gems__tracks');
                $select->where([
                   'gtr_id_track' => $trackId,
                ]);

                $trackData = $this->resultFetcher->fetchRow($select);
            }

            if (! isset($trackData['gtr_track_class'])) {
                $trackData['gtr_track_class'] = 'AnyStepEngine';
            }

            $this->_trackEngines[$trackId] = $this->overLoader->create('Tracker\\Engine\\' . $trackData['gtr_track_class'], $trackData);
        }

        return $this->_trackEngines[$trackId];
    }

    /**
     *
     * @param string $trackCode Track code or whole word part of code to find track by
     * @return \Gems\Tracker\Engine\TrackEngineInterface|null or null when not found
     */
    public function getTrackEngineByCode(string $trackCode): TrackEngineInterface|null
    {
        $trackData = $this->resultFetcher->fetchRow(
                "SELECT * FROM gems__tracks
                    WHERE CONCAT(' ', gtr_code, ' ') LIKE ? AND
                        gtr_active = 1 AND
                        gtr_date_start <= CURRENT_DATE AND
                        (gtr_date_until IS NULL OR gtr_date_until <= CURRENT_DATE)
                    ORDER BY gtr_date_start",
                ['% $trackCode %']
                );

        if ($trackData) {
            return $this->getTrackEngine($trackData);
        }
        return null;
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
     * @return TrackEngineInterface[]
     */
    public function getTrackEngineClasses(): array
    {
        static $dummyClasses;

        if (! $dummyClasses) {
            $dummyTrackData['gtr_id_track'] = 0;

            foreach ($this->getTrackEngineClassNames() as $className) {
                $dummyClasses[$className] = $this->overLoader->create('Tracker\\Engine\\' . $className, $dummyTrackData);
            }
        }

        return $dummyClasses;
    }

    /**
     * Returns all registered track engines class names
     *
     * @return array Of classname
     */
    protected function getTrackEngineClassNames(): array
    {
        return [
            'AnyStepEngine',
            //'NextStepEngine',
        ];
    }

    /**
     * Return the edit snippets for editing or creating a new track
     *
     * @return array of snippet names for creating a new track engine
     */
    public function getTrackEngineEditSnippets(): array
    {
        return ['Tracker\\EditTrackEngineSnippet'];
    }

    /**
     * Returns all registered track engines classes for use in drop down lists.
     *
     * @param boolean $extended When true return a longer name.
     * @param boolean $userCreatableOnly Return only the classes that can be created by the user interface
     * @return array Of classname => description
     */
    public function getTrackEngineList(bool $extended = false, bool $userCreatableOnly = false): array
    {
        $results = [];

        foreach ($this->getTrackEngineClasses() as $className => $cls) {
            if ((! $userCreatableOnly) || $cls->isUserCreatable()) {
                if ($extended) {
                    $results[$className] = \Zalt\Html\Html::raw(sprintf('<strong>%s</strong> %s', $cls->getName(), $cls->getDescription()));

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
     * @return TrackModel
     */
    public function getTrackModel(): TrackModel
    {
        /**
         * @var TrackModel
         */
        return $this->overLoader->create('Tracker\\Model\\TrackModel');
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
     * @param \Gems\Task\TaskRunnerBatch $batch The batch to load
     * @param int $respondentId   Id of the respondent to check for or NULL
     * @param int $userId         Id of the user who takes the action (for logging)
     * @param int $orgId          Optional Id of the organization to check for
     * @param boolean $quickCheck Check only tokens with recent gto_start_time's
     * @return void
     */
    public function loadCompletedTokensBatch(TaskRunnerBatch $batch, int $respondentId = null, int|null $userId = null, int $orgId = null, bool $quickCheck = false): void
    {
        $userId = $this->_checkUserId($userId);

        $tokenSelect = $this->getTokenSelect(['gto_id_token']);
        $tokenSelect->onlyActive($quickCheck)
                    ->forRespondent($respondentId)
                    ->andSurveys(['gsu_surveyor_id'])
                    ->forWhere('gsu_surveyor_active = 1')
                    ->order('gsu_surveyor_id');

        if (null !== $orgId) {
            $tokenSelect->forWhere('gto_id_organization = ?', $orgId);
        }

        $statement = $tokenSelect->getSelect()->query();
        //Process one row at a time to prevent out of memory errors for really big resultsets
        $tokens = [];
        $tokencount = 0;
        $activeId = 0;
        $maxCount = 100;    // Arbitrary value, change as needed
        while ($tokenData = $statement->fetch()) {
            $tokenId = $tokenData['gto_id_token'];
            $surveyorId = $tokenData['gsu_surveyor_id'];
            if ($activeId <> $surveyorId || count($tokens) > $maxCount) {
                // Flush
                if (count($tokens)> 0) {
                    $batch->addTask('Tracker\\BulkCheckTokenCompletion', $tokens, $userId);
                }

                $activeId = $surveyorId;
                $tokens = [];
            }
            $tokens[] = $tokenId;

            $batch->addToCounter('tokens');
        }
        if (count($tokens)> 0) {
            $batch->addTask('Tracker\\BulkCheckTokenCompletion', $tokens, $userId);
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
    public function processCompletedTokens(SessionInterface $session, ?int $respondentId, int|null $userId = null, ?int $orgId = null, bool $quickCheck = false): bool
    {
        $batch = new TaskRunnerBatch('completed', $this->overLoader, $session);

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
     * @param LaminasTokenSelect Select statements selecting tokens
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return \Gems\Task\TaskRunnerBatch A batch to process the changes
     */
    protected function processTokensBatch(SessionInterface $session, $batchId, LaminasTokenSelect $tokenSelect, $userId): TaskRunnerBatch
    {
        $batch = new TaskRunnerBatch($batchId, $this->overLoader, $session);

        //Now set the step duration
        $batch->minimalStepDurationMs = 3000;

        if (! $batch->isLoaded()) {
            $resultSet = $this->resultFetcher->query($tokenSelect->getSelect());
            if ($resultSet instanceof ResultSet) {
                //Process one row at a time to prevent out of memory errors for really big resultsets
                while ($resultSet->valid()) {
                    $tokenData = $resultSet->current();
                    $tokenId = $tokenData['gto_id_token'];
                    $batch->setTask('Tracker\\CheckTokenCompletion', 'tokchk-' . $tokenId, $tokenId, $userId);
                    $batch->addToCounter('tokens');
                    $resultSet->next();
                }
            }
        }

        return $batch;
    }

    /**
     * @inheritdoc
     */
    public function recalculateTokens(SessionInterface $session, string $batch_id, int $userId = null, array $cond = []): TaskRunnerBatch
    {
        $userId = $this->_checkUserId($userId);
        $tokenSelect = new LaminasTokenSelect($this->resultFetcher);
        $tokenSelect->andReceptionCodes([])
                    ->andRespondents([])
                    ->andRespondentOrganizations([])
                    ->andConsents([]);
        if ($cond) {
            $tokenSelect->forWhere($cond);
        }
        //Only select surveys that are active in the source (so we can recalculate inactive in \Gems)
        $tokenSelect->andSurveys([]);
        $tokenSelect->forWhere(['gsu_surveyor_active' => 1]);

        self::$verbose = true;
        return $this->processTokensBatch($session, $batch_id, $tokenSelect, $userId);
    }

    /**
     * Recalculates the fields in tracks.
     *
     * Does recalculate changed tracks
     *
     * @param string $batchId A unique identifier for the current batch
     * @param array $cond Optional where statement for selecting tracks
     * @return TaskRunnerBatch A batch to process the changes
     */
    public function recalcTrackFields(SessionInterface $session, string $batchId, array $cond = []): TaskRunnerBatch
    {
        $respTrackSelect = $this->resultFetcher->getSelect();
        $respTrackSelect->from('gems__respondent2track');
        $respTrackSelect->columns(['gr2t_id_respondent_track'])
            ->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code', [])
            ->join('gems__tracks', 'gr2t_id_track = gtr_id_track', []);

        $cond['gr2t_active'] = 1;
        $cond['grc_success'] = 1;
        $cond['gtr_active'] = 1;
        $respTrackSelect->where($cond);

        $batch = new TaskRunnerBatch($batchId, $this->overLoader, $session);
        //Now set the step duration
        $batch->minimalStepDurationMs = 3000;

        if (! $batch->isLoaded()) {
            $resultSet = $this->resultFetcher->query($respTrackSelect);

            $count = 1;
            if ($resultSet instanceof ResultSet) {
                // Process one item at a time to prevent out of memory errors for really big resultsets
                while ($resultSet->valid()) {
                    $respTrackData = $resultSet->current();
                    $respTrackId   = $respTrackData['gr2t_id_respondent_track'];
                    $batch->setTask('Tracker\\RecalculateFields', 'trkfcalc-' . $respTrackId, $respTrackId);
                    $batch->addToCounter('resptracks');
                    $resultSet->next();
                }
            }
        }

        return $batch;
    }

    /**
     * Refreshes the tokens in the source
     *
     * @param string $batch_id A unique identifier for the current batch
     * @param string $cond An optional where statement
     * @return TaskRunnerBatch A batch to process the changes
     */
    public function refreshTokenAttributes(SessionInterface $session, string $batchId, ?string $cond = null, mixed $bind = null): TaskRunnerBatch
    {
        $batch = new TaskRunnerBatch($batchId, $this->overLoader, $session);

        if (! $batch->isLoaded()) {

            $tokenSelect = $this->getTokenSelect(array('gto_id_token'));
            $tokenSelect->andSurveys([])
                        ->forWhere('gsu_surveyor_active = 1')
                        ->forWhere('gto_in_source = 1');

            if ($cond) {
                // Add all connections for filtering, but select only surveys that are active in the source
                $tokenSelect->andReceptionCodes([])
                        ->andRespondents([])
                        ->andRespondentOrganizations([])
                        ->andConsents([])
                        ->forWhere($cond, $bind);
            }

            $tokens = $tokenSelect->fetchAll();
            foreach ($tokens as $token) {
                $batch->addTask('Tracker\\RefreshTokenAttributes', $token['gto_id_token']);
            }
        }
        self::$verbose = true;

        return $batch;
    }

    /**
     * Remove token from cache for saving memory
     *
     * @param string|Token $token
     * @return self (continuation pattern)
     */
    public function removeToken(string|Token $token): self
    {
        if ($token instanceof Token) {
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
     * @return \Gems\Task\TaskRunnerBatch A batch to process the synchronization
     */
    public function synchronizeSources(SessionInterface $session, ?int $sourceId = null): TaskRunnerBatch
    {
        $batchId = 'source_sync' . ($sourceId ? '_' . $sourceId : '');
        $batch = new TaskRunnerBatch($batchId, $this->overLoader, $session);

        if (! $batch->isLoaded()) {
            if ($sourceId) {
                $sources = array($sourceId);
            } else {
                $select = $this->resultFetcher->getSelect('gems__sources');
                $select->columns(['gso_id_source'])
                    ->where(['gso_active' => 1]);

                $sources = $this->resultFetcher->fetchCol($select);
            }

            foreach ($sources as $source) {
                $batch->addTask('Tracker\\SourceSyncSurveys', $source);
                // Reset cache after basic sync
                $batch->addTask('CleanCache');
                // Reset cache after field sync
                $batch->addTask('AddTask', 'CleanCache');
                $batch->addTask('AddTask', 'Tracker\\UpdateSyncDate', $source);
            }
        }

        return $batch;

    }
}
