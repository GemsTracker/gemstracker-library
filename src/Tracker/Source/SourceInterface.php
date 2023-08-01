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
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker\Survey;
use Gems\Tracker\Token;
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
interface SourceInterface
{
    /**
     * @return bool When true can export when survey inactive in source
     */
    public function canExportInactive(): bool;
    
    /**
     * Checks whether this particular source is active or not and should handle updating the gems-db
     * with the right information about this source
     *
     * @param int $userId    ID of the user who takes the action (for logging)
     * @return bool
     */
    public function checkSourceActive(int $userId): bool;

    /**
     * Survey source synchronization check function
     *
     * @param int|string|null $sourceSurveyId
     * @param int $surveyId
     * @param int $userId
     * @return array message string or array of messages
     */
    public function checkSurvey(int|string|null $sourceSurveyId, int $surveyId, int $userId): array;

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param Token $token
     * @param string $language
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws SurveyNotFoundException
     */
    public function copyTokenToSource(Token $token, string $language, int $surveyId, int|string|null $sourceSurveyId = null): int;


    /**
     * Returns a field from the raw answers as a date object.
     *
     * A separate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getAnswerDateTime(string $fieldName, Token $token, int $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface;

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
     * it well enough). In the case Token will do it's best to keep
     * track by itself.
     *
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getCompletionTime(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface;

    /**
     * Returns an array containing field-name => label for each date field in the survey.
     *
     * Used in dropdown list etc.
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array field-name => label
     */
    public function getDatesList(string $language, int $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     *
     * @return int The source ID of this source
     */
    public function getId(): int;

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
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array Nested array
     */
    public function getQuestionInformation(string $language, int $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     * Returns an array containing field-name => label for dropdown list etc.
     *

     * @param int $surveyId \Gems Survey ID
     * @param string|null $language   (ISO) language string
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array field-name => label
     */
    public function getQuestionList(int $surveyId, string|null $language = null, int|string|null $sourceSurveyId = null): array;

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param string $tokenId \Gems Token ID
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow(string $tokenId, int $surveyId, int|string|null $sourceSurveyId = null): array;

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
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter, int $surveyId, int|string|null $sourceSurveyId = null): array;

    /**
     * Returns the record-count for a given filter
     *
     * @param array $filter filter array
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return int
     */
    public function getRawTokenAnswerRowsCount(array $filter, int $surveyId, int|string|null $sourceSurveyId = null): int;

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
     * it well enough). In the case Token will do it's best to keep
     * track by itself.
     *
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return ?DateTimeInterface date time or null
     */
    public function getStartTime(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): ?DateTimeInterface;

    /**
     * Returns a model for the survey answers
     *
     * @param Survey $survey
     * @param ?string $language Optional (ISO) language string
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return ModelAbstract
     */
    public function getSurveyAnswerModel(Survey $survey, string $language = null, int|string|null $sourceSurveyId = null): ModelAbstract;

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param Token $token \Gems token object
     * @param string $language
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(Token $token, string $language, int $surveyId, int|string|null $sourceSurveyId = null): string;

    /**
     * Checks whether the token is in the source.
     *
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return bool
     */
    public function inSource(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): bool;

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param Token $token \Gems token object
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return bool True if the token has completed
     */
    public function isCompleted(Token $token, int $surveyId, int|string|null $sourceSurveyId = null): bool;

    /**
     * Sets the answers passed on.
     *
     * @param Token $token \Gems token object
     * @param $answers array Field => Value array
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @return true When answers changed
     */
    public function setRawTokenAnswers(Token $token, array $answers, int $surveyId, int|string|null $sourceSurveyId = null): bool;

    /**
     * Sets the completion time.
     *
     * @param Token $token \Gems token object
     * @param DateTimeInterface|null $completionTime \DateTimeInterface or null
     * @param int $surveyId \Gems Survey ID (actually required)
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     */
    public function setTokenCompletionTime(Token $token, ?DateTimeInterface $completionTime, int $surveyId, int|string|null $sourceSurveyId = null);

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param TaskRunnerBatch $batch
     * @param int $userId    ID of the user who takes the action (for logging)
     * @return array Returns an array of messages
     */
    public function synchronizeSurveyBatch(TaskRunnerBatch $batch, int $userId): array;

    /**
     * Updates the consent code of the token in the source (if needed)
     *
     * @param Token $token
     * @param int $surveyId \Gems Survey ID
     * @param int|string|null $sourceSurveyId Optional Survey ID used by source
     * @param ?string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(Token $token, int $surveyId, int|string|null $sourceSurveyId = null, ?string $consentCode = null): int;
}
