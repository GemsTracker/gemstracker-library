<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker;

use DateTimeInterface;

use Gems\Date\Period;
use Gems\Db\ResultFetcher;
use Gems\Tracker;
use Gems\Tracker\Source\SourceInterface;
use Gems\Tracker\TrackEvent\SurveyBeforeAnsweringEventInterface;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use Zalt\Model\MetaModelInterface;

/**
 * Object representing a specific Survey
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Survey
{
    /**
     * @var array
     */
    protected array $defaultData = [
        'gsu_active' => 0,
        'gsu_code' => null,
        'gsu_valid_for_length' => 6,
        'gsu_valid_for_unit' => 'M',
    ];

    public bool $exists = false;

    protected int $id;

    /**
     * @var int Counter for new surveys, negative value used as temp survey id
     */
    public static int $newSurveyCount = 0;

    /**
     *
     * @var SourceInterface
     */
    private SourceInterface|null $_source = null;

    /**
     * Set in child classes
     *
     * @var string Name of table used in gtrs_table
     */
    protected string $translationTable = 'gems__surveys';

    /**
     *
     * @param mixed $gemsSurveyData Token Id or array containing token record
     */
    public function __construct(
        protected array $data,
        protected readonly Tracker $tracker,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly TrackEvents $trackEvents,
    )
    {
        $this->id = $this->data['gsu_id_survey'];
        if ($this->id > 0) {
            $this->exists = true;
        }
    }

    /**
     * Update the survey, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    private function _updateSurvey(array $values, int $userId): int
    {
        // If loaded using tracker->getSurveyBySourceId the id can be negative if survey not found in GT
        if ($this->id <= 0) {
            if (is_array($this->data)) {
                $values = $values + $this->data;
            } else {
                \MUtil\EchoOut\EchoOut::track($this->data);
            }
            $this->data = [];
        }
        if ($this->tracker->filterChangesOnly($this->data, $values)) {

            if (Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $old = isset($this->data[$key]) ? $this->data[$key] : null;
                    $echo .= $key . ': ' . $old . ' => ' . $val . "\n";
                }
                \MUtil\EchoOut\EchoOut::r($echo, 'Updated values for ' . $this->id);
            }

            if (! isset($values['gsu_changed'])) {
                $values['gsu_changed'] = new Expression('CURRENT_TIMESTAMP');
            }
            if (! isset($values['gsu_changed_by'])) {
                $values['gsu_changed_by'] = $userId;
            }

            $table = new TableGateway('gems__surveys', $this->resultFetcher->getAdapter());
            if ($this->exists) {
                // Update values in this object
                $this->data = $values + $this->data;

                return $table->update($values, ['gsu_id_survey' => $this->id]);
            } else {
                if (! isset($values['gsu_created'])) {
                    $values['gsu_created'] = new Expression('CURRENT_TIMESTAMP');
                }
                if (! isset($values['gsu_created_by'])) {
                    $values['gsu_created_by'] = $userId;
                }

                // Update values in this object
                $this->data = $values + $this->data;

                // Remove the \Gems survey id
                unset($this->data['gsu_id_survey']);

                $table->insert($this->data);
                $this->id = $this->resultFetcher->getAdapter()->getDriver()->getLastGeneratedValue();

                $this->data['gsu_id_survey'] = $this->id;
                $this->exists = true;

                return 1;
            }

        } else {
            return 0;
        }
    }

    /**
     * Calculate a hash for this survey, taking into account the questions and answers
     *
     * @return string
     */
    public function calculateHash(): string
    {
        $answerModel = $this->getAnswerModel('en');
        $items       = [];
        foreach($answerModel->getItemsOrdered() as $item) {
                $result = $answerModel->get($item, ['label', 'type', 'multiOptions', 'parent_question', 'thClass', 'group', 'description']);
                if (array_key_exists('label', $result)) {
                    $items[$item] = $result;
                }
        }

        $hash = md5(serialize($items));

        return $hash;
    }

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param \Gems\Tracker\Token $token
     * @param string $language
     * @return int 1 of the token was inserted or changed, 0 otherwise
     * @throws \Gems\Tracker\Source\SurveyNotFoundException
     */
    public function copyTokenToSource(Token $token, string $language): int
    {
        $source = $this->getSource();
        return $source->copyTokenToSource($token, $language, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * @param string $fieldName Name of answer field
     * @param \Gems\Tracker\Token  $token \Gems token object
     * @return ?DateTimeInterface date time or null
     */
    public function getAnswerDateTime($fieldName, Token $token)
    {
        $source = $this->getSource();
        return $source->getAnswerDateTime($fieldName, $token, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of snippet names
     */
    public function getAnswerSnippetNames(Token $token): array
    {
        if (isset($this->data['gsu_display_event']) && $this->data['gsu_display_event']) {
            $event = $this->trackEvents->loadSurveyDisplayEvent($this->data['gsu_display_event']);

            return $event->getAnswerDisplaySnippets($token);
        }
        return [];
    }

    /**
     * Returns a model for displaying the answers to this survey in the requested language.
     *
     * @param string $language (ISO) language string
     * @return \MUtil\Model\ModelAbstract
     */
    public function getAnswerModel(string $language): MetaModelInterface
    {
        $source = $this->getSource();
        return $source->getSurveyAnswerModel($this, $language, $this->getSourceSurveyId());
    }

    /**
     *
     * @return string Internal code of the survey
     */
    public function getCode(): string|null
    {
        return $this->data['gsu_code'] ?? null;
    }

    /**
     * The time the survey was completed according to the source
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @return ?DateTimeInterface date time or null
     */
    public function getCompletionTime(Token $token): DateTimeInterface|null
    {
        $source = $this->getSource();
        return $source->getCompletionTime($token, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Returns an array containing fieldname => label for each date field in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language (ISO) language string
     * @return array Returns an array of the strings datename => label
     */
    public function getDatesList(string $language): array
    {
        $source = $this->getSource();
        return $source->getDatesList($language, $this->id, $this->getSourceSurveyId());
    }

    /**
     *
     * @return string Description of the survey
     */
    public function getDescription(): string|null
    {
        return $this->data['gsu_survey_description'] ?? null;
    }

    /**
     *
     * @return string Available languages of the survey
     */
    public function getAvailableLanguages(): string|null
    {
        return $this->data['gsu_survey_languages'] ?? null;
    }

    /**
     *
     * @return string Warning messages of the survey
     */
    public function getSurveyWarnings(): string|null
    {
        return $this->data['gsu_survey_warnings'] ?? null;
    }

    /**
     *
     * @return string The (manually entered) normal duration for taking this survey
     */
    public function getDuration(): string|null
    {
        return $this->data['gsu_duration'] ?? null;
    }

    /**
     *
     * @return string Export code of the survey
     */
    public function getExportCode(): string|null
    {
        return $this->data['gsu_export_code'] ?? null;
    }

    /**
     *
     * @return string External description of the survey
     */
    public function getExternalName(): string|null
    {
        if ($this->data['gsu_external_description']) {
            return $this->data['gsu_external_description'] ?? null;
        }

        return $this->getName();
    }

    /**
     *
     * @return int \Gems group id for survey
     */
    public function getGroupId(): int|null
    {
        return $this->data['gsu_id_primary_group'] ?? null;
    }

    /**
     *
     * @return string The hash of survey questions/answers
     */
    public function getHash(): string|null
    {
        return array_key_exists('gsu_hash', $this->_data) ? $this->_data['gsu_hash'] : null;
    }

    /**
     * Calculate the until date for single survey insertion
     *
     * @param DateTimeInterface $from
     * @return ?DateTimeInterface
     */
    public function getInsertDateUntil(DateTimeInterface $from): DateTimeInterface|null
    {
        $validForUnit = $this->data['gsu_valid_for_unit'] ?? null;
        $validForLength = null;
        if (isset($this->data['gsu_valid_for_length'])) {
            $validForLength = (int)$this->data['gsu_valid_for_length'];
        }

        return Period::applyPeriod(
            $from,
            $validForUnit,
            $validForLength
        );
    }

    /**
     *
     * @return string Name of the survey
     */
    public function getName(): string|null
    {
        return $this->data['gsu_survey_name'] ?? null;
    }

    /**
     * @return int
     */
    public function getMailCode(): string|null
    {
        return $this->data['gsu_mail_code'] ?? null;
    }

    /**
     * Returns an array of array with the structure:
     *      question => string,
     *      class    => question|question_sub
     *      group    => is for grouping
     *      type     => (optional) source specific type
     *      answers  => string for single types,
     *                  array for selection of,
     *                  nothing for no answer
     *
     * @param string $language   (ISO) language string
     * @return array Nested array
     */
    public function getQuestionInformation(string|null $language = null): array
    {
        return $this->getSource()->getQuestionInformation($language, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Returns a fieldlist with the field names as key and labels as array.
     *
     * @param string $language (ISO) language string
     * @return array of fieldname => label type
     */
    public function getQuestionList(string|null $language = null): array
    {
        return $this->getSource()->getQuestionList($language, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param string $tokenId \Gems Token Id
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow(string $tokenId): array
    {
        $source = $this->getSource();
        return $source->getRawTokenAnswerRow($tokenId, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Returns the answers of multiple tokens in simple raw nested array format,
     * without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param array $filter XXX
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter = []): array
    {
        $source = $this->getSource();
        return $source->getRawTokenAnswerRows($filter, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Returns the number of answers of multiple tokens
     *
     * @param array $filter XXX
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRowsCount(array $filter = []): int
    {
        $source = $this->getSource();
        return $source->getRawTokenAnswerRowsCount((array) $filter, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Retrieve the name of the resultfield
     *
     * The resultfield should be present in this surveys answers.
     *
     * @return string
     */
    public function getResultField(): string|null
    {
        return $this->data['gsu_result_field'] ?? null;
    }

    /**
     * The time the survey was started according to the source
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @return ?DateTimeInterface date time or null
     */
    public function getStartTime(Token $token): DateTimeInterface|null
    {
        $source = $this->getSource();
        return $source->getStartTime($token, $this->id, $this->getSourceSurveyId());
    }

    /**
     *
     * @return \Gems\Tracker\Source\SourceInterface
     */
    public function getSource(): SourceInterface
    {
        if (! $this->_source && isset($this->data['gsu_id_source'])) {
            $this->_source = $this->tracker->getSource($this->data['gsu_id_source']);

            if (! $this->_source) {
                throw new \Gems\Exception('No source for exists for source ' . $this->data['gsu_id_source'] . '.');
            }
        }

        return $this->_source;
    }

    /**
     *
     * @return int \Gems survey ID
     */
    public function getSourceSurveyId(): int|null
    {
        return $this->data['gsu_surveyor_id'] ?? null;
    }

    /**
     *
     * @return string Survey status
     */
    public function getStatus(): string|null
    {
        return $this->data['gsu_status'] ?? null;
    }

    /**
     * Return the Survey Before Answering event (if any)
     *
     * @return SurveyBeforeAnsweringEventInterface|null event instance or null
     */
    public function getSurveyBeforeAnsweringEvent(): SurveyBeforeAnsweringEventInterface|null
    {
        if (isset($this->data['gsu_beforeanswering_event']) && $this->data['gsu_beforeanswering_event']) {
            return $this->trackEvents->loadSurveyBeforeAnsweringEvent($this->data['gsu_beforeanswering_event']);
        }
        return null;
    }

    /**
     * Return the Survey Completed event
     *
     * @return SurveyCompletedEventInterface|null event instance or null
     */
    public function getSurveyCompletedEvent(): SurveyCompletedEventInterface|null
    {
        if (isset($this->data['gsu_completed_event']) && $this->data['gsu_completed_event']) {
            return $this->trackEvents->loadSurveyCompletionEvent($this->data['gsu_completed_event']);
        }
        return null;
    }

    /**
     *
     * @return int \Gems survey ID
     */
    public function getSurveyId(): int
    {
        return $this->id;
    }

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @param string $language
     * @return string The url to start the survey
     */
    public function getTokenUrl(Token $token, string $language): string
    {
        $source = $this->getSource();
        return $source->getTokenUrl($token, $language, $this->id, $this->getSourceSurveyId());
    }

    /**
     *
     * @return boolean True if the survey has a pdf
     */
    public function hasPdf(): bool
    {
        return isset($this->data['gsu_survey_pdf']);
    }

    /**
     * Checks whether the token is in the source.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @return boolean
     */
    public function inSource(Token $token): bool
    {
        $source = $this->getSource();
        return $source->inSource($token, $this->id, $this->getSourceSurveyId());
    }

    /**
     *
     * @return boolean True if the survey is active
     */
    public function isActive(): bool
    {
        return $this->exists && isset($this->data['gsu_active']);
    }

    /**
     *
     * @return boolean True if the survey is active in the source
     */
    public function isActiveInSource(): bool
    {
        return isset($this->data['gsu_surveyor_active']);
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param Token $token \Gems token object
     * @return bool True if the token has completed
     */
    public function isCompleted(Token $token): bool
    {
        $source = $this->getSource();
        return $source->isCompleted($token, $this->id, $this->getSourceSurveyId());
    }

    /**
     * Should this survey be filled in by staff members.
     *
     * @return bool
     */
    public function isTakenByStaff(): bool
    {
        return $this->data['ggp_member_type'] === 'staff';
    }

    /**
     * Update the survey, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function saveSurvey(array $values, int $userId): int
    {
        // Keep the pattern of this object identical to that of others,
        // i.e. use an _update function
        return $this->_updateSurvey($values, $userId);
    }

    /**
     *
     * @param string $hash The hash for this survey
     * @param int $userId The current user
     */
    public function setHash(string $hash, int $userId): void
    {
        if ($this->getHash() !== $hash && array_key_exists('gsu_hash', $this->data)) {
            $values['gsu_hash'] = $hash;
            $this->_updateSurvey($values, $userId);
        }
    }

    /**
     * Updates the consent code of the token in the source (if needed)
     *
     * @param Token $token
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(Token $token, string|null $consentCode = null): int
    {
        $source = $this->getSource();
        return $source->updateConsent($token, $this->id, $this->getSourceSurveyId(), $consentCode);
    }
}
