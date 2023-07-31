<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Source;

use DateTimeInterface;
use Laminas\Db\Adapter\Adapter;
use MUtil\Model\ModelAbstract;

/**
 * Interface description of SourceInterface for (external) survey sources.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
interface SourceInterface extends \MUtil\Registry\TargetInterface
{
    /**
     * Standard constructor for sources
     * We do not want to copy db using registry because that is public and
     * this should be private.
     *
     * @param array $_sourceData The information from gems__sources for this source.
     * @param Adapter $_gemsDb   The database connection to \Gems itself
     */
    public function __construct(array $_sourceData, Adapter $_gemsDb);

    /**
     * @return bool When true can export when survey inactive in source
     */
    public function canExportInactive(): bool;
    
    /**
     * Checks wether this particular source is active or not and should handle updating the gems-db
     * with the right information about this source
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return bool
     */
    public function checkSourceActive(int $userId): bool;

    /**
     * Survey source synchronization check function
     *
     * @param int|string|null $sourceSurveyId
     * @param int|string $surveyId
     * @param int $userId
     * @return array message string or array of messages
     */
    public function checkSurvey(int|string|null $sourceSurveyId, int|string $surveyId, int $userId): array;

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param \Gems\Tracker\Token $token
     * @param string $language
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws \Gems\Tracker\Source\SurveyNotFoundException
     */
    public function copyTokenToSource(\Gems\Tracker\Token $token, string $language, int|string $surveyId, int|string|null $sourceSurveyId = null): int;


    /**
     * Returns a field from the raw answers as a date object.
     *
     * A seperate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getAnswerDateTime(string $fieldName, \Gems\Tracker\Token $token, int|string $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface;

    /**
     * Returns all the gemstracker names for attributes stored in source for a token
     *
     * @return array<int, string>
     */
    public function getAttributes(): array;

    /**
     * Gets the time the survey was completed according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems\Tracker\Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getCompletionTime(\Gems\Tracker\Token $token, int|string $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface;

    /**
     * Returns an array containing fieldname => label for each date field in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getDatesList(string $language, int|string $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     *
     * @return int The source Id of this source
     */
    public function getId();

    /**
     * Returns an array of arrays with the structure:
     *      question => string,
     *      class    => question|question_sub
     *      group    => is for grouping
     *      type     => (optional) source specific type
     *      answers  => string for single types,
     *                  array for selection of,
     *                  nothing for no answer
     *
     * @param string $language   (ISO) language string
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return array Nested array
     */
    public function getQuestionInformation(string $language, int|string $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     * Returns an array containing fieldname => label for dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getQuestionList(string $language, int|string $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param string $tokenId \Gems Token Id
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow(string $tokenId, int|string $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     * Returns the answers of multiple tokens in simple raw nested array format,
     * without value processing etc.
     *
     * Function may return more fields than just the answers.
     * The $filter param is an array of filters to apply to the selection, it has
     * some special formatting rules. The key is the db-field to filter on and the
     * value could be a value or an array of values to filter on.
     *
     * Special keys that should be mapped to the right field by the source are:
     *  respondentid
     *  organizationid
     *  consentcode
     *  token
     *
     * So a filter of [token]=>[abc-def][def-abc] will return the results for these two tokens
     * while a filter of [organizationid] => 70 will return all results for this organization.
     *
     * @param array $filter filter array
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter, int|string $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     * Returns the recordcount for a given filter
     *
     * @param array $filter filter array
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return int
     */
    public function getRawTokenAnswerRowsCount(array $filter, int|string $surveyId, int|string|null $sourceSurveyId = null): int;

    /**
     * Get the db adapter for this source
     *
     * @return Adapter
     */
    public function getSourceDatabase(): Adapter;

    /**
     * Gets the time the survey was started according to the source.
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case \Gems\Tracker\Token will do it's best to keep
     * track by itself.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getStartTime(\Gems\Tracker\Token $token, int|string $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface;

    /**
     * Returns a model for the survey answers
     *
     * @param \Gems\Tracker\Survey $survey
     * @param ?string $language Optional (ISO) language string
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return \MUtil\Model\ModelAbstract
     */
    public function getSurveyAnswerModel(\Gems\Tracker\Survey $survey, string $language = null, int|string|null $sourceSurveyId = null): ModelAbstract;

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param string $language
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(\Gems\Tracker\Token $token, string $language, int|string $surveyId, int|string|null $sourceSurveyId = null): string;

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return boolean
     */
    public function inSource(\Gems\Tracker\Token $token, int|string $surveyId, null|int|string $sourceSurveyId = null): bool;

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return boolean True if the token has completed
     */
    public function isCompleted(\Gems\Tracker\Token $token, int|string $surveyId, null|int|string $sourceSurveyId = null): bool;

    /**
     * Sets the answers passed on.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param $answers array Field => Value array
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @return true When answers changed
     */
    public function setRawTokenAnswers(\Gems\Tracker\Token $token, array $answers, int|string $surveyId, null|int|string $sourceSurveyId = null): bool;

    /**
     * Sets the completion time.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param \DateTimeInterface|null $completionTime \DateTimeInterface or null
     * @param int $surveyId \Gems Survey Id (actually required)
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setTokenCompletionTime(\Gems\Tracker\Token $token, ?DateTimeInterface $completionTime, int|string $surveyId, int|string|null $sourceSurveyId = null);

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param \Gems\Task\TaskRunnerBatch $batch
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return array Returns an array of messages
     */
    public function synchronizeSurveyBatch(\Gems\Task\TaskRunnerBatch $batch, int $userId);

    /**
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param \Gems\Tracker\Token $token
     * @param int|string $surveyId \Gems Survey Id
     * @param int|string|null $sourceSurveyId Optional Survey Id used by source
     * @param ?string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(\Gems\Tracker\Token $token, int|string $surveyId, int|string|null $sourceSurveyId = null, ?string $consentCode = null): int;
}
