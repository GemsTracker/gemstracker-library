<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker;

use Gems\Agenda\Appointment;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Tracker\Form\AskTokenForm;
use Gems\Tracker\Model\RespondentTrackModel;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\Tracker\Model\TrackModel;
use Gems\Tracker\Source\SourceInterface;
use Gems\Tracker\Token\TokenFilter;
use Gems\Tracker\Token\TokenLibrary;
use Gems\Tracker\Token\TokenSelect;
use Gems\Tracker\Token\TokenValidator;
use Laminas\Db\Sql\Expression;
use Mezzio\Session\SessionInterface;

/**
 * This interface lists all API-level methods in the Tracker class.
 *
 * This interface only exists to prevent the \Gems\Loader\TargetLoaderAbstract
 * functions of being accessible when working with a tracker. Do not create
 * a second implementation is this interface but always create a subclass of
 * the \Gems\Tracker class.
 *
 *
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
interface TrackerInterface
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
     * @return \Gems\Task\TaskRunnerBatch A batch to process the changes
     */
    public function checkTrackRounds(SessionInterface $session, string $batchId, ?int $userId = null, ?string $cond = null): TaskRunnerBatch;

    /**
     * Create a new track for a patient
     *
     * @param int $respondentId   The real patientId (grs_id_user), not the patientnr (gr2o_patient_nr)
     * @param int $organizationId
     * @param int $trackId
     * @param int $userId         Id of the user who takes the action (for logging)
     * @param mixed $respTrackData Optional array containing field values or the start date.
     * @param array $trackFieldsData
     * @param SessionInterface $session
     * @return \Gems\Tracker\RespondentTrack The newly created track
     */
    public function createRespondentTrack(int $respondentId, int $organizationId, int $trackId, int $userId, int|array|null $respTrackData = null, array $trackFieldsData = [], SessionInterface $session = null): RespondentTrack;

    /**
     * Dynamically load and create a [Gems|Project]_Tracker class
     *
     * @param string $className
     * @param mixed $param1
     * @param mixed $param2
     * @return object
     */
    public function createTrackClass(string $className, mixed $param1 = null, mixed $param2 = null): object;

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
    public function filterChangesOnly(array $oldValues, array &$newValues): array;

    /**
     * Removes all unacceptable characters from the input token and inserts any fixed characters left out
     *
     * @param string $tokenId
     * @return string Reformatted token
     */
    public function filterToken(string $tokenId): string;

    /**
     * Returns an array of all field id's for all tracks that have a code id
     *
     * @return array id => code
     */
    public function getAllCodeFields(): array;

    /**
     * Get an appointment object
     *
     * @param int|array $appointmentData Appointment id or array containing appintment data
     * @return \Gems\Agenda\Appointment
     */
    public function getAppointment(int|array $appointmentData): Appointment;

    /**
     * Returns a form to ask for a token
     *
     * @param mixed $args_array \MUtil\Ra::args array for Form initiation.
     * @return \Gems\Tracker\Form\AskTokenForm
     */
    public function getAskTokenForm(mixed $args_array = null): AskTokenForm;

    /**
     *
     * @param int|array $respTrackData Track id or array containing trackdata
     * @return \Gems\Tracker\RespondentTrack
     */
    public function getRespondentTrack(int|array $respTrackData): RespondentTrack;

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
    public function getRespondentTracks(int $respondentId, int $organizationId, string|array|Expression $order = ['gr2t_start_date']): array;

    /**
     * Load project specific model or general \Gems model otherwise
     *
     * @return \Gems\Tracker\Model\RespondentTrackModel
     */
    public function getRespondentTrackModel(): RespondentTrackModel;

    /**
     * Retrieve a SourceInterface with a given id
     *
     * Should only be called by \Gems\Tracker, \Gems\Tracker\Survey or \Gems\Tracker\Token (or should
     * this one use Gems\Tracker\Survey instead?)
     *
     * @param int|array $sourceData \Gems source id or array containing gems source data
     * @return \Gems\Tracker\Source\SourceInterface
     */
    public function getSource(int|array $sourceData): SourceInterface;

    /**
     * Returns all registered source classes
     *
     * @return array Of classname => description
     */
    public function getSourceClasses(): array;

    /**
     * Returns all registered database source classes
     *
     * @return array Of classname => description
     */
    public function getSourceDatabaseClasses(): array;

    /**
     *
     * @param int|array $surveyData \Gems survey id or array containing gems survey data
     * @return \Gems\Tracker\Survey
     */
    public function getSurvey(int|array $surveyData): Survey;

    /**
     *
     * @param mixed $sourceSurveyId The source survey id
     * @param int $sourceId The gems source id of the source
     * @return \Gems\Tracker\Survey
     */
    public function getSurveyBySourceId(int|string $sourceSurveyId, int $sourceId): Survey;

    /**
     *
     * @param \Gems\Tracker\Survey $survey
     * @param \Gems\Tracker\Source\SourceInterface $source
     * @return \Gems\Tracker\SurveyModel
     */
    public function getSurveyModel(Survey $survey, SourceInterface $source): SurveyModel;

    /**
     *
     * @param string|array $tokenData Token id or array containing tokendata
     * @return \Gems\Tracker\Token
     */
    public function getToken(string|array $tokenData): Token;

    /**
     *
     * @return \Gems\Tracker\Token\TokenFilter
     */
    public function getTokenFilter(): TokenFilter;

    /**
     * Use this function only within \Gems\Tracker!!
     *
     * @return \Gems\Tracker\Token\TokenLibrary
     */
    public function getTokenLibrary(): TokenLibrary;

    /**
     * Returns a token model of the specified class with full display information
     *
     * @param string $modelClass Optional class to use instead of StandardTokenModel. Must be subclass.
     * @return \Gems\Tracker\Model\StandardTokenModel
     */
    public function getTokenModel(string $modelClass = 'StandardTokenModel'): StandardTokenModel;

    /**
     * Create a select statement on the token table
     *
     * @return \Gems\Tracker\Token\TokenSelect
     */
    public function getTokenSelect(string|array $fields = '*'): TokenSelect;

    /**
     *
     * @return \Gems\Tracker\Token\TokenValidator
     */
    public function getTokenValidator(): TokenValidator;

    /**
     * Get the allowed display groups for tracks in this project.
     *
     * @return array
     */
    public function getTrackDisplayGroups(): array;

    /**
     *
     * @param int|array $trackData \Gems track id or array containing gems track data
     * @return \Gems\Tracker\Engine\TrackEngineInterface
     */
    public function getTrackEngine(int|array $trackData): TrackEngineInterface;

    /**
     *
     * @param string $trackCode Track code or whole word part of code to find track by
     * @return \Gems\Tracker\Engine\TrackEngineInterface or null when not found
     */
    public function getTrackEngineByCode(string $trackCode): TrackEngineInterface|null;

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
     * @return array Of \Gems\Tracker\Engine\TrackEngineInterface
     */
    public function getTrackEngineClasses(): array;

    /**
     * Return the edit snippets for editing or creating a new track
     *
     * @return array of snippet names for creating a new track engine
     */
    public function getTrackEngineEditSnippets(): array;

    /**
     * Returns all registered track engines classes for use in drop down lists.
     *
     * @param boolean $extended When true return a longer name.
     * @param boolean $userCreatableOnly Return only the classes that can be created by the user interface
     * @return array Of classname => description
     */
    public function getTrackEngineList(bool $extended = false, bool $userCreatableOnly = false): array;

    /**
     * Simple function for a default track model.
     *
     * @return \Gems\Tracker\Model\TrackModel
     */
    public function getTrackModel(): TrackModel;

    /**
     * Checks the token table to see if there are any answered surveys to be processed
     *
     * If the survey was started (and the token was forwarded to limesurvey) we need to check
     * if is was completed. If so, we might want to check the track the survey is in to enable
     * or disable future rounds
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param SessionInterface $session
     * @param int $respondentId   Id of the respondent to check for or NULL
     * @param int $userId         Id of the user who takes the action (for logging)
     * @param int $orgId          Optional Id of the organization to check for
     * @param boolean $quickCheck Check only tokens with recent gto_start_time's
     * @return bool               Did we find new answers?
     */
    public function processCompletedTokens(SessionInterface $session, ?int $respondentId, ?int $userId = null, ?int $orgId = null, bool $quickCheck = false): bool;

    /**
     * Recalculates all token dates, timing and results
     * and outputs text messages.
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param SessionInterface $session
     * @param string $batch_id A unique identifier for the current batch
     * @param int $userId    Id of the user who takes the action (for logging)
     * @param string $cond
     * @return \Gems\Task\TaskRunnerBatch A batch to process the changes
     */
    public function recalculateTokens(SessionInterface $session, string $batch_id, ?int $userId = null, string $cond = null, mixed $bind = null): TaskRunnerBatch;

    /**
     * Recalculates the fields in tracks.
     *
     * Does recalculate changed tracks
     *
     * @param SessionInterface $session
     * @param string $batchId A unique identifier for the current batch
     * @param string $cond Optional where statement for selecting tracks
     * @return \Gems\Task\TaskRunnerBatch A batch to process the changes
     */
    public function recalcTrackFields(SessionInterface $session, string $batchId, ?string $cond = null): TaskRunnerBatch;

    /**
     * Refreshes the tokens in the source
     *
     * @param SessionInterface $session
     * @param string $batch_id A unique identifier for the current batch
     * @param string $cond An optional where statement
     * @return \Gems\Task\TaskRunnerBatch A batch to process the changes
     */
    public function refreshTokenAttributes(SessionInterface $session, string $batchId, ?string $cond = null): TaskRunnerBatch;

    /**
     * Remove token from cache for saving memory
     *
     * @param string|\Gems\Tracker\Token $token
     * @return \Gems\Tracker (continuation pattern)
     */
    public function removeToken(Token|string $token): self;

    /**
     * Recalculates all token dates, timing and results
     * and outputs text messages.
     *
     * Does not reflect changes to tracks or rounds.
     *
     * @param SessionInterface $session
     * @param int $sourceId A source identifier
     * @param int $userId Id of the user who takes the action (for logging)
     * @return \Gems\Task\TaskRunnerBatch A batch to process the synchronization
     */
    public function synchronizeSources(SessionInterface $session, ?int $sourceId = null): TaskRunnerBatch;
}
