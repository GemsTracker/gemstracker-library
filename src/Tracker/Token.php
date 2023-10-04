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

use DateTimeImmutable;
use DateTimeInterface;

use Gems\Db\ResultFetcher;
use Gems\Event\Application\TokenEvent;
use Gems\Exception\Coding;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Log\Loggers;
use Gems\Log\LogHelper;
use Gems\Messenger\Message\TokenResponse;
use Gems\Model\RespondentRelationInstance;
use Gems\Model\RespondentRelationModel;
use Gems\Project\ProjectSettings;
use Gems\Repository\ConsentRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\Tracker\Source\SourceAbstract;
use Gems\Tracker\Token\LaminasTokenSelect;
use Gems\User\Mask\MaskRepository;
use Gems\User\Organization;
use Gems\User\User;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use MUtil\Model;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelInterface;


/**
 * Object class for checking and changing tokens.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Token
{    const COMPLETION_NOCHANGE = 0;
    const COMPLETION_DATACHANGE = 1;
    const COMPLETION_EVENTCHANGE = 2;

    protected array $_cache = [];

    /**
     *
     * @var string The token id of the token this one was copied from, null when not loaded, false when does not exist
     */
    protected string|null $_copiedFromTokenId = null;

    /**
     *
     * @var array The token id's of the tokens this one was copied to, null when not loaded, [] when none exist
     */
    protected array|null $_copiedToTokenIds = null;

    /**
     *
     * @var array The gems token data
     */
    protected array $_gemsData = [];

    /**
     * Helper var for preventing infinite loops
     *
     * @var bool
     */
    protected bool $_loopCheck = false;

    /**
     *
     * @var Token
     */
    private Token|bool|null $_nextToken = null;

    /**
     *
     * @var Token
     */
    private Token|null $_previousToken = null;

    /**
     * Holds the relation (if any) for this token
     *
     * @var array
     */
    protected RespondentRelationInstance|null $_relation = null;

    /**
     *
     * @var Respondent
     */
    protected Respondent|null $_respondentObject = null;

    protected RespondentTrack|null $respondentTrack = null;

    /**
     *
     * @var array The answers in raw format
     */
    private array|null $_sourceDataRaw = null;

    /**
     *
     * @var string The id of the token
     */
    protected string $_tokenId;

    protected User $currentUser;

    /**
     * True when the token does exist.
     *
     * @var bool
     */
    public bool $exists = true;

    protected LoggerInterface $logger;

    /**
     * The size of the result field, calculated from meta data when null,
     * but can be set by project specific class to fixed value
     *
     * @var int The maximum character length of the result field
     */
    protected int|null $resultFieldLength = null;

    /**
     * Cache for storing the calculation of the length
     *
     * @var int the character length of the result field
     */
    protected static int|null $staticResultFieldLength = null;

    protected Survey|null $survey = null;

    /**
     * Creates the token object
     *
     * @param mixed $gemsTokenData Token Id or array containing token record
     */
    public function __construct(
        array|string $gemsTokenData,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly MaskRepository $maskRepository,
        protected readonly Tracker $tracker,
        protected readonly ProjectSettings $projectSettings,
        protected readonly ConsentRepository $consentRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly ReceptionCodeRepository $receptionCodeRepository,
        protected readonly RespondentRepository $respondentRepository,
        protected readonly ProjectOverloader $projectOverloader,
        protected readonly Translated $translatedUtil,
        protected readonly Locale $locale,
        protected readonly TokenRepository $tokenRepository,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly TranslatorInterface $translator,
        protected readonly MessageBusInterface $messageBus,
        Loggers $loggers,
        CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->logger = $loggers->getLogger('LegacyLogger');
        if (is_array($gemsTokenData)) {
            $this->_gemsData = $gemsTokenData;
            $this->_tokenId  = $gemsTokenData['gto_id_token'];
        } else {
            $this->_tokenId  = $gemsTokenData;
            // loading occurs in checkRegistryRequestAnswers
        }

        if ($this->_gemsData) {
            $this->_gemsData = $this->maskRepository->applyMaskToRow($this->_gemsData);
        } else {
            $this->refresh();
        }
    }

    /**
     * Add relation to the select statement
     *
     * @param \Gems\Tracker\Token\TokenSelect $select
     */
    protected function _addRelation(LaminasTokenSelect $select): void
    {
        if (!is_null($this->_gemsData['gto_id_relation'])) {
            $select->forWhere(['gto_id_relation' => $this->_gemsData['gto_id_relation']]);
        } else {
            $select->forWhere(['gto_id_relation IS NULL']);
        }
    }

    /**
     * Makes sure the respondent data is part of the $this->_gemsData
     */
    protected function _ensureRespondentData(): void
    {
        if (! isset($this->_gemsData['grs_id_user'], $this->_gemsData['gr2o_id_user'], $this->_gemsData['gco_code'])) {

            $select = $this->resultFetcher->getSelect('gems__respondents')
                ->join('gems__respondent2org', 'grs_id_user = gr2o_id_user')
                ->join('gems__consents', 'gr2o_consent = gco_description')
                ->where([
                    'gr2o_id_user' => $this->_gemsData['gto_id_respondent'],
                    'gr2o_id_organization' => $this->_gemsData['gto_id_organization'],
                ])
                ->limit(1);

            if ($row = $this->resultFetcher->fetchRow($select)) {
                $this->_gemsData = $this->_gemsData + $row;
            } else {
                $token = $this->_tokenId;
                throw new \Gems\Exception("Respondent data missing for token $token.");
            }
        }
    }

    /**
     * The maximum length of the result field
     *
     * @return int
     */
    protected function _getResultFieldLength(): int
    {
        if (null !== $this->resultFieldLength) {
            return $this->resultFieldLength;
        }

        if (null !== self::$staticResultFieldLength) {
            $this->resultFieldLength = self::$staticResultFieldLength;
            return $this->resultFieldLength;
        }

        $model = new \MUtil\Model\TableModel('gems__tokens');
        self::$staticResultFieldLength = $model->get('gto_result', 'maxlength');
        $this->resultFieldLength = self::$staticResultFieldLength;

        return $this->resultFieldLength;
    }

    /**
     * Update the token, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    protected function _updateToken(array $values, int $userId): int
    {
        if (!$this->tracker->filterChangesOnly($this->_gemsData, $values)) {
            return 0;   // No changes
        }

        if (Tracker::$verbose) {
            $echo = '';
            foreach ($values as $key => $val) {
                $echo .= $key . ': ' . $this->_gemsData[$key] . ' => ' . $val . "\n";
            }
            \MUtil\EchoOut\EchoOut::r($echo, 'Updated values for ' . $this->_tokenId);
        }

        $defaults = [
            'gto_changed'    => new Expression('CURRENT_TIMESTAMP'),
            'gto_changed_by' => $userId
        ];

        // Update values in this object
        $this->_gemsData = $values + $defaults + (array) $this->_gemsData;

        $table = new TableGateway('gems__tokens', $this->resultFetcher->getAdapter());
        return $table->update($values, ['gto_id_token' => $this->_tokenId]);
    }

    /**
     * Assign this token to a specific relation
     *
     * @param int $respondentRelationId
     * @param int $relationFieldId
     * @return int 1 if data changed, 0 otherwise
     */
    public function assignTo(int $respondentRelationId, int $relationFieldId): int
    {
        if (($this->getRelationFieldId() == $relationFieldId) && ($this->getRelationId() == $respondentRelationId)) {
            return 0;
        }

        return $this->_updateToken([
            'gto_id_relation'      => $respondentRelationId,
            'gto_id_relationfield' => $relationFieldId,
            ], $this->currentUser->getUserId());
    }

    /**
     * Retrieve a certain $key from the local cache
     *
     * For speeding up things the token can hold a local cache, living as long as the
     * token object exists in memory. Sources can use this to store reusable information.
     *
     * To reset the cache on an update, the source can use the cacheReset method or the
     * setCache method to update the changed value.
     *
     * @param string $key             The key used in the cache
     * @param mixed  $defaultValue    The optional default value to use when it is not present
     * @return mixed
     */
    public function cacheGet(string $key, mixed $defaultValue = null): mixed
    {
        if ($this->cacheHas($key)) {
            return $this->_cache[$key];
        } else {
            return $defaultValue;
        }
    }

    /**
     * find out if a certain key is present in the cache
     *
     * @param string $key
     * @return bool
     */
    public function cacheHas(string $key): bool
    {
        return isset($this->_cache[$key]);
    }

    /**
     * Reset the local cache for this token
     *
     * You can pass in an optional $key parameter to reset just that key, otherwise all
     * the cache will be reset
     *
     * @param string|null $key The key to reset
     */
    public function cacheReset(string|null $key = null): void
    {
        if (is_null($key)) {
            $this->_cache = array();
        } else {
            unset($this->_cache[$key]);
        }
    }

    /**
     * Set a $key in the local cache
     *
     * @param string $key
     * @param mixed  $value
     */
    public function cacheSet(string $key, mixed $value): void
    {
        $this->_cache[$key] = $value;
    }

    public function canBeEmailed(): bool
    {
        //CASE WHEN grc_success = 1 AND "
        //                . "((gr2o_email IS NOT NULL AND gr2o_email != '' AND (gto_id_relationfield IS NULL OR gto_id_relationfield < 1) AND gr2o_mailable >= gsu_mail_code) OR "
        //                . "(grr_email IS NOT NULL AND grr_email != '' AND gto_id_relationfield > 0 AND grr_mailable >= gsu_mail_code))"
        //                . " AND ggp_member_type = 'respondent' AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gr2t_mailable >= gsu_mail_code THEN 1 ELSE 0 END

        return (bool)$this->_gemsData['can_email'];
    }

    /**
     * Checks whether the survey for this token was completed and processes the result
     *
     * @param int $userId The id of the gems user
     * @return int self::COMPLETION_NOCHANGE || (self::COMPLETION_DATACHANGE | self::COMPLETION_EVENTCHANGE)
     */
    public function checkTokenCompletion(int $userId): int
    {
        $result = self::COMPLETION_NOCHANGE;

        // Some defaults
        $values['gto_completion_time'] = null;
        $values['gto_duration_in_sec'] = null;
        $values['gto_result']          = null;

        if ($this->inSource()) {
            $values['gto_in_source'] = 1;
            $survey                  = $this->getSurvey();
            $startTime               = $survey->getStartTime($this);
            if ($startTime instanceof DateTimeInterface) {
                // Value from source overrules any set date time
                $values['gto_start_time'] = $startTime->format(\Gems\Tracker::DB_DATETIME_FORMAT);

            } else {
                // Otherwise use the time kept by \Gems.
                $startTime = $this->getDateTime('gto_start_time');

                //What if we have older tokens... where no gto_start_time was set??
                if (is_null($startTime)) {
                    $startTime = new DateTimeImmutable();
                }

                // No need to set $values['gto_start_time'], it does not change
            }

            if ($survey->isCompleted($this)) {
                $complTime         = $survey->getCompletionTime($this);
                $setCompletionTime = true;

                if (! $complTime instanceof DateTimeInterface) {
                    // Token is completed but the source cannot tell the time
                    //
                    // Try to see it was stored already
                    $complTime = $this->getDateTime('gto_completion_time');

                    if ($complTime instanceof DateTimeInterface) {
                        // Again no need to change a time that did not change
                        unset($values['gto_completion_time']);
                        $setCompletionTime = false;
                    } else {
                        // Well anyhow it was completed now or earlier. Get the current moment.
                        $complTime = new DateTimeImmutable();
                    }
                }

                //Save the old completiontime
                $oldCompletionTime = $this->_gemsData['gto_completion_time'];
                //Set completion time for completion event
                if ($setCompletionTime) {
                    $values['gto_completion_time']          = $complTime->format(\Gems\Tracker::DB_DATETIME_FORMAT);
                    $this->_gemsData['gto_completion_time'] = $values['gto_completion_time'];
                }

                // Process any \Gems side survey dependent changes
                if ($changed = $this->handleAfterCompletion()) {
                    // Communicate change
                    $result += self::COMPLETION_EVENTCHANGE;

                    if (\Gems\Tracker::$verbose) {
                        \MUtil\EchoOut\EchoOut::r($changed, 'Source values for ' . $this->_tokenId . ' changed by event.');
                    }
                }

                //Reset completiontime to old value, so changes will be picked up
                $this->_gemsData['gto_completion_time'] = $oldCompletionTime;
                $values['gto_duration_in_sec']          = max($complTime->getTimestamp() - $startTime->getTimestamp(), 0);

                //If the survey has a resultfield, store it
                if ($resultField = $survey->getResultField()) {
                    $rawAnswers = $this->getRawAnswers();
                    if (isset($rawAnswers[$resultField])) {
                        // Cast to string, because that is the way the result is stored in the db
                        // not casting to strings means e.g. float results always result in
                        // an update, even when they did not change.
                        $values['gto_result'] = (string) $rawAnswers[$resultField];

                        // Chunk of text that is too long
                        if ($len = $this->_getResultFieldLength()) {
                            $values['gto_result'] = substr($values['gto_result'], 0, $len);
                        }
                    }
                }

                if ($this->projectSettings->hasResponseDatabase()) {
                    $this->toResponseDatabase($userId);
                }
            }
        } else {
            $values['gto_in_source']  = 0;
            $values['gto_start_time'] = null;
        }

        if ($this->_updateToken($values, $userId)) {
            // Communicate change
            $result += self::COMPLETION_DATACHANGE;
        }

        return $result;
    }

    /**
     * Creates an almost exact copy of this token at the same place in the track,
     * only without answers and other source data
     *
     * Returns the new token id
     *
     * @param string $newComment Description of why the token was replaced
     * @param int $userId The current user
     * @param array $otherValues Other values to set in the token
     * @return string The new token
     */
    public function createReplacement(string $newComment, int $userId, array $otherValues = []): string|null
    {
        $values['gto_id_respondent_track'] = $this->_gemsData['gto_id_respondent_track'];
        $values['gto_id_round']            = $this->_gemsData['gto_id_round'];
        $values['gto_id_respondent']       = $this->_gemsData['gto_id_respondent'];
        $values['gto_id_organization']     = $this->_gemsData['gto_id_organization'];
        $values['gto_id_track']            = $this->_gemsData['gto_id_track'];
        $values['gto_id_survey']           = $this->_gemsData['gto_id_survey'];
        $values['gto_round_order']         = $this->_gemsData['gto_round_order'];
        $values['gto_round_description']   = $this->_gemsData['gto_round_description'];
        $values['gto_valid_from']          = $this->_gemsData['gto_valid_from'];
        $values['gto_valid_from_manual']   = $this->_gemsData['gto_valid_from_manual'];
        $values['gto_valid_until']         = $this->_gemsData['gto_valid_until'];
        $values['gto_valid_until_manual']  = $this->_gemsData['gto_valid_until_manual'];
        $values['gto_mail_sent_date']      = $this->_gemsData['gto_mail_sent_date'];
        $values['gto_comment']             = $newComment;

        $newValues = $otherValues + $values;
        // Now make sure there are no more date objects
        foreach($newValues as &$value)
        {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(Tracker::DB_DATETIME_FORMAT);
            }
        }

        $tokenId = $this->tracker->createToken($newValues, $userId);

        $replacementLog['gtrp_id_token_new'] = $tokenId;
        $replacementLog['gtrp_id_token_old'] = $this->_tokenId;
        $replacementLog['gtrp_created']      = new Expression('CURRENT_TIMESTAMP');
        $replacementLog['gtrp_created_by']   = $userId;

        $table = new TableGateway('gems__token_replacements', $this->resultFetcher->getAdapter());
        $table->insert($replacementLog);

        return $tokenId;
    }

    /**
     * Get all unanswered tokens for the person answering this token
     *
     * Similar to @see $this->getNextUnansweredToken()
     * Similar to @see $this->getTokenCountUnanswered()
     *
     * @return array of tokendata
     */
    public function getAllUnansweredTokens(array|null $where = null): array
    {
        $select = new LaminasTokenSelect($this->resultFetcher);
        $select->andReceptionCodes()
                ->andRespondentTracks()
                ->andRounds([])
                ->andSurveys([])
                ->andTracks()
                ->forGroupId($this->getSurvey()->getGroupId())
                ->forRespondent($this->getRespondentId(), $this->getOrganizationId())
                ->onlySucces()
                ->forWhere([
                    'gsu_active' => 1,
                    'gro_active = 1 OR gro_active IS NULL',
                ])
                ->order([
                    'gtr_track_name',
                    'gr2t_track_info',
                    'gto_valid_until',
                    'gto_valid_from',
                    'gto_round_order',
                ]);

        $this->_addRelation($select);

        if (!empty($where)) {
            $select->forWhere($where);
        }

        return $select->fetchAll();
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * @param string $fieldName Name of answer field
     * @return ?DateTimeInterface date time or null
     */
    public function getAnswerDateTime(string $fieldName): DateTimeInterface|null
    {
        $survey = $this->getSurvey();
        return $survey->getAnswerDateTime($fieldName, $this);
    }

    /**
     * Returns an array of snippetnames that can be used to display the answers to this token.
     *
     * @return array Of snippet names
     */
    public function getAnswerSnippetNames(): array
    {
        if (! $this->exists) {
            return ['Token\\TokenNotFoundSnippet'];
        }

        if (! $this->_loopCheck) {
            // Events should not call $this->getAnswerSnippetNames() but
            // $this->getTrackEngine()->getAnswerSnippetNames(). Just in
            // case the code writer made a mistake we have a guard here.
            $this->_loopCheck = true;

            $snippets = $this->getTrackEngine()->getRoundAnswerSnippets($this);

            if (! $snippets) {
                $snippets = $this->getSurvey()->getAnswerSnippetNames($this);
            }

            if ($snippets) {
                $this->_loopCheck = false;
                return $snippets;
            }
        }

        return $this->getTrackEngine()->getAnswerSnippetNames();
    }

    /**
     * A copy of the data array
     *
     * @return array
     */
    public function getArrayCopy(): array
    {
        return $this->_gemsData;
    }

    /**
     * Returns the staff or respondent id of the person
     * who last changed this token.
     *
     * @return int
     */
    public function getChangedBy(): int
    {
        return $this->_gemsData['gto_changed_by'];
    }

    /**
     * Return the comment for this token
     *
     * @return string
     */
    public function getComment(): string|null
    {
        return $this->_gemsData['gto_comment'];
    }

    /**
     *
     * @return ?DateTimeInterface Completion time as a date or null
     */
    public function getCompletionTime(): DateTimeInterface|null
    {
        if (isset($this->_gemsData['gto_completion_time']) && $this->_gemsData['gto_completion_time']) {
            if ($this->_gemsData['gto_completion_time'] instanceof DateTimeInterface) {
                return $this->_gemsData['gto_completion_time'];
            }
            return Model::getDateTimeInterface($this->_gemsData['gto_completion_time'], Tracker::DB_DATETIME_FORMAT);
        }
        return null;
    }

    /**
     *
     * @return string
     */
    public function getConsentCode(): string
    {
        if ($this->getReceptionCode()->isSuccess()) {
            if (! isset($this->_gemsData['gco_code'])) {
                $this->_ensureRespondentData();
            }

            return $this->_gemsData['gco_code'];
        } else {
            return $this->consentRepository->getConsentRejected();
        }
    }

    /**
     * Get the token id of the token this one was copied from, null when not loaded, false when does not exist
     *
     * @return string
     */
    public function getCopiedFrom(): string|null
    {
        if (null === $this->_copiedFromTokenId) {
            $this->_copiedFromTokenId = $this->resultFetcher->fetchOne(
                    "SELECT gtrp_id_token_old FROM gems__token_replacements WHERE gtrp_id_token_new = ?",
                    [$this->_tokenId]
                    );
        }

        return $this->_copiedFromTokenId;
    }

    /**
     * The token id's of the tokens this one was copied to, null when not loaded, [] when none exist
     *
     * @return array tokenId => tokenId
     */
    public function getCopiedTo(): array|null
    {
        if (null === $this->_copiedToTokenIds) {
            $this->_copiedToTokenIds = $this->resultFetcher->fetchPairs(
                    "SELECT gtrp_id_token_new, gtrp_id_token_new
                        FROM gems__token_replacements
                        WHERE gtrp_id_token_old = ?",
                    [$this->_tokenId]
                    );

            if (! $this->_copiedToTokenIds) {
                $this->_copiedToTokenIds = [];
            }
        }

        return $this->_copiedToTokenIds;
    }

    /**
     * Returns the staff or respondent id of the person
     * who created this token.
     *
     * @return int
     */
    public function getCreatedBy(): int
    {
        return $this->_gemsData['gto_created_by'];
    }

    /**
     * Returns the creation date of this token.
     *
     * @return string
     */
    public function getCreationDate(): String
    {
        return $this->_gemsData['gto_created'];
    }

    /**
     *
     * @param string $fieldName
     * @return ?DateTimeInterface
     */
    public function getDateTime($fieldName): ?DateTimeInterface
    {
        if (isset($this->_gemsData[$fieldName])) {
            if ($this->_gemsData[$fieldName] instanceof DateTimeInterface) {
                return $this->_gemsData[$fieldName];
            }

            return Model::getDateTimeInterface($this->_gemsData[$fieldName]);
        }
        return null;
    }

    /**
     * Returns an array of snippet names that can be used to delete this token.
     *
     * @return array of strings
     */
    public function getDeleteSnippetNames(): array
    {
        if ($this->exists) {
            return $this->getTrackEngine()->getTokenDeleteSnippetNames($this);
        } else {
            return ['Token\\TokenNotFoundSnippet'];
        }
    }

    /**
     * Returns an array of snippet names that can be used to edit this token.
     *
     * @return array of strings
     */
    public function getEditSnippetNames(): array
    {
        if ($this->exists) {
            return $this->getTrackEngine()->getTokenEditSnippetNames($this);
        } else {
            return ['Token\\TokenNotFoundSnippet'];
        }
    }

    /**
     * Get the email address of the person who needs to fill out this survey.
     *
     * This method will return null when no address available
     *
     * @return string|null Email address of the person who needs to fill out the survey or null
     */
    public function getEmail(): string|null
    {
        // If staff, return null, we don't know who to email
        if ($this->getSurvey()->isTakenByStaff()) {
            return null;
        }

        // If we have a relation, return that address
        if ($this->hasRelation()) {
            if ($relation = $this->getRelation()) {
                return $relation->getEmail();
            }

            return null;
        }

        // It can only be the respondent
        return $this->getRespondent()->getEmailAddress();
    }

    /**
     *
     * @return string Last mail sent date
     */
    public function getMailSentDate(): string|null
    {
        return $this->_gemsData['gto_mail_sent_date'];
    }

    /**
     * @return array Url array for token routes
     */
    public function getMenuUrlParameters(): array
    {
        $params[\MUtil\Model::REQUEST_ID] = $this->getTokenId();
        $params[\MUtil\Model::REQUEST_ID1] = $this->getPatientNumber();
        $params[\MUtil\Model::REQUEST_ID2] = $this->getOrganizationId();
        $params[\Gems\Model::RESPONDENT_TRACK] = $this->getRespondentTrackId();

        return $params;
    }

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return StandardTokenModel
     */
    public function getModel(): StandardTokenModel
    {
        if ($this->exists) {
            return $this->getTrackEngine()->getTokenModel();
        } else {
            return $this->tracker->getTokenModel();
        }
    }

    /**
     * Returns the next token in this track
     *
     * @return \Gems\Tracker\Token
     */
    public function getNextToken(): ?Token
    {
        if (null === $this->_nextToken) {
            $tokenSelect = new LaminasTokenSelect($this->resultFetcher);
            $tokenSelect
                    ->andReceptionCodes()
                    ->forPreviousToken($this);

            if ($tokenData = $tokenSelect->fetchRow()) {
                $this->_nextToken = $this->tracker->getToken($tokenData);
                $this->_nextToken->_previousToken = $this;
            } else {
                $this->_nextToken = false;
            }
        }

        if (false === $this->_nextToken) {
            return null;
        }
        return $this->_nextToken;
    }

    /**
     * Returns the next unanswered token for the person answering this token
     *
     * @return ?Token
     */
    public function getNextUnansweredToken(): Token|null
    {
        $tokenSelect = new LaminasTokenSelect($this->resultFetcher);
        $tokenSelect
                ->andReceptionCodes()
                // ->andRespondents()
                // ->andRespondentOrganizations()
                // ->andConsents
                ->andRounds([])
                ->andSurveys([])
                ->forRespondent($this->getRespondentId())
                ->forGroupId($this->getSurvey()->getGroupId())
                ->onlySucces()
                ->onlyValid()
                ->forWhere([
                    'gsu_active' => 1,
                    'gro_active = 1 OR gro_active IS NULL',
                ])
                ->order(['gto_valid_from', 'gto_round_order']);

        $this->_addRelation($tokenSelect);

        if ($tokenData = $tokenSelect->fetchRow()) {
            return $this->tracker->getToken($tokenData);
        }
        return null;
    }

    /**
     *
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organizationRepository->getOrganization($this->getOrganizationId());
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->_gemsData['gto_id_organization'];
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber(): string
    {
        if (! isset($this->_gemsData['gr2o_patient_nr'])) {
            $this->_ensureRespondentData();
        }

        return $this->_gemsData['gr2o_patient_nr'];
    }

    /**
     * Get the phone number of the person who needs to fill out this survey.
     *
     * This method will return null when no number is available
     *
     * @return string|null phone number of the person who needs to fill out the survey or null
     */
    public function getPhoneNumber(): string|null
    {
        // If staff, return null, we don't know who to message
        if ($this->getSurvey()->isTakenByStaff()) {
            return null;
        }

        // If we have a relation, return that address
        if ($this->hasRelation()) {
            if ($relation = $this->getRelation()) {
                return $relation->getPhoneNumber();
            }

            return null;
        }

        // It can only be the respondent
        return $this->getRespondent()->getMobilePhoneNumber();
    }

    /**
     * Returns the previous token that has succes in this track
     *
     * @return \Gems\Tracker\Token|null
     */
    public function getPreviousSuccessToken(): Token|null
    {
        $prev = $this->getPreviousToken();

        while ($prev && (! $prev->getReceptionCode()->isSuccess())) {
            $prev = $prev->getPreviousToken();
        }

        return $prev;
    }

    /**
     * Returns the previous token in this track
     *
     * @return \Gems\Tracker\Token|null
     */
    public function getPreviousToken(): Token|null
    {
        if (null === $this->_previousToken) {
            $tokenSelect = new LaminasTokenSelect($this->resultFetcher);
            $tokenSelect
                    ->andReceptionCodes()
                    ->forNextToken($this);

            if ($tokenData = $tokenSelect->fetchRow()) {
                $this->_previousToken = $this->tracker->getToken($tokenData);
                $this->_previousToken->_nextToken = $this;
            } else {
                $this->_previousToken = null;
            }
        }

        return $this->_previousToken;
    }

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @return array Field => Value array
     */
    public function getRawAnswers(): array
    {
        if (! is_array($this->_sourceDataRaw)) {
            $this->_sourceDataRaw = $this->getSurvey()->getRawTokenAnswerRow($this->_tokenId);
        }
        return $this->_sourceDataRaw;
    }

    /**
     * Return the \Gems\Util\ReceptionCode object
     *
     * @return ReceptionCode reception code
     */
    public function getReceptionCode(): ReceptionCode
    {
        return $this->receptionCodeRepository->getReceptionCode($this->_gemsData['gto_reception_code']);
    }

    /**
     * Get the relation object if any
     *
     * @return \Gems\Model\RespondentRelationInstance
     */
    public function getRelation(): RespondentRelationInstance
    {
        if (is_null($this->_relation) || $this->_relation->getRelationId() !== $this->getRelationId()) {
            /**
             * @var RespondentRelationModel $model
             */
            $model = $this->projectOverloader->create('Model\\RespondentRelationModel');
            $relationObject = $model->getRelation($this->getRespondentId(), $this->getRelationId());
            $this->_relation = $relationObject;
        }

        return $this->_relation;
    }

    /**
     * Return the id of the relation field
     *
     * This is not the id of the relation, but the id of the trackfield that defines
     * the relation.
     *
     * @return int
     */
    public function getRelationFieldId(): int|null
    {
        return $this->hasRelation() ? (int) $this->_gemsData['gto_id_relationfield'] : null;
    }

    /**
     * Get the name of the relationfield for this token
     *
     * @return string
     */
    public function getRelationFieldName(): string|null
    {
        if ($relationFieldId = $this->getRelationFieldId()) {
            $names = $this->getRespondentTrack()->getTrackEngine()->getFieldNames();
            $fieldPrefix = FieldMaintenanceModel::FIELDS_NAME . FieldsDefinition::FIELD_KEY_SEPARATOR;
            $key = $fieldPrefix . $relationFieldId;

            return array_key_exists($key, $names) ? lcfirst($names[$key]) : null;
        }

        return null;
    }

    /**
     * Return the id of the relation currently assigned to this token
     *
     * @return int
     */
    public function getRelationId(): int|null
    {
        return $this->hasRelation() ? $this->_gemsData['gto_id_relation'] : null;
    }

    /**
     * Get the respondent linked to this token
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent(): Respondent
    {
        $patientNumber  = $this->getPatientNumber();
        $organizationId = $this->getOrganizationId();

        if (! ($this->_respondentObject instanceof Respondent)
                || $this->_respondentObject->getPatientNumber()  !== $patientNumber
                || $this->_respondentObject->getOrganizationId() !== $organizationId) {
            $this->_respondentObject = $this->respondentRepository->getRespondent($patientNumber, $organizationId);
        }

        return $this->_respondentObject;
    }

    /**
     * Returns the gender as a letter code
     *
     * @return string
     */
    public function getRespondentGender(): string
    {
        return $this->getRespondent()->getGender();
    }

    /**
     * Returns the gender for use as part of a sentence, e.g. Dear Mr/Mrs
     *
     * @return string|null
     */
    public function getRespondentGenderHello(): string|null
    {
        $greetings = $this->translatedUtil->getGenderGreeting();
        $gender    = $this->getRespondentGender();

        if (isset($greetings[$gender])) {
            return $greetings[$gender];
        }
        return null;
    }

    /**
     *
     * @return int
     */
    public function getRespondentId(): int
    {
        if (array_key_exists('gto_id_respondent', $this->_gemsData)) {
            return $this->_gemsData['gto_id_respondent'];
        }
        throw new \Gems\Exception(sprintf('Token %s not loaded correctly', $this->getTokenId()));
    }

    /**
     * Return the default language for the respondent
     *
     * @return string Two letter language code
     */
    public function getRespondentLanguage(): string
    {
        if (! isset($this->_gemsData['grs_iso_lang'])) {
            $this->_ensureRespondentData();

            if (! isset($this->_gemsData['grs_iso_lang'])) {
                // Still not set in a project? The it is single language
                $this->_gemsData['grs_iso_lang'] = $this->locale->getLanguage();
            }
        }

        return $this->_gemsData['grs_iso_lang'];
    }

    /**
     *
     * @return string
     */
    public function getRespondentLastName(): string
    {
        return $this->getRespondent()->getLastName();
    }

    /**
     * Get the name of the person answering this token
     *
     * Could be the patient or the relation when assigned to one
     *
     * @return string
     */
    public function getRespondentName(): string|null
    {
        if ($this->hasRelation()) {
            if ($relation = $this->getRelation()) {
                return $relation->getName();
            } else {
                return null;
            }
        }

        return $this->getRespondent()->getName();
    }

    /**
     *
     * @return \Gems\Tracker\RespondentTrack
     */
    public function getRespondentTrack(): RespondentTrack
    {
        if (! $this->respondentTrack) {
            $this->respondentTrack = $this->tracker->getRespondentTrack($this->_gemsData['gto_id_respondent_track']);
        }

        return $this->respondentTrack;
    }

    /**
     *
     * @return int
     */
    public function getRespondentTrackId(): int
    {
        return $this->_gemsData['gto_id_respondent_track'];
    }

    /**
     * The result value
     *
     * @return string
     */
    public function getResult(): string
    {
        return $this->_gemsData['gto_result'] ?: '';
    }

    /**
     * The full return url for a redirect
     *
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->_gemsData['gto_return_url'];
    }

    /**
     * Get the round code for this token
     *
     * @return string|null Null when no round id is present or round no longer exists
     */
    public function getRoundCode(): string|null
    {
        $roundCode = null;
        $roundId = $this->getRoundId();
        if ($roundId > 0) {
            $roundCode = $this->getRespondentTrack()->getRoundCode($roundId);
        }

        return $roundCode;
    }

    /**
     *
     * @return string Round description
     */
    public function getRoundDescription(): string
    {
        return $this->_gemsData['gto_round_description'] ?: '';
    }

    /**
     *
     * @return int round id
     */
    public function getRoundId(): int
    {
        return (int)$this->_gemsData['gto_id_round'];
    }

    /**
     *
     * @return int round order
     */
    public function getRoundOrder(): int
    {
        return (int)$this->_gemsData['gto_round_order'];
    }

    /**
     * Return the name of the respondent
     *
     * To be used when there is a relation and you need to know the name of the respondent
     *
     * @return string
     */
    public function getSubjectname(): string
    {
        return $this->getRespondent()->getName();
    }

    /**
     * Returns a snippet name that can be used to display this token.
     *
     * @return string
     */
    public function getShowSnippetNames(): array
    {
        if ($this->exists) {
            return $this->getTrackEngine()->getTokenShowSnippetNames($this);
        } else {
            return ['Token\\TokenNotFoundSnippet'];
        }
    }

    /**
     * Returns a string that tells if the token is open, completed or any other
     * status you might like. This will not be interpreted by the tracker it is
     * for display purposes only
     *
     * @return string Token status description
     */
    public function getStatus(): string
    {
        return $this->tokenRepository->getStatusDescription($this->getStatusCode());
    }

    /**
     * Returns token status code
     *
     * @return string Token status code in one letter
     */
    public function getStatusCode(): string
    {
        return $this->_gemsData['token_status'];
    }

    /**
     *
     * @return \Gems\Tracker\Survey
     */
    public function getSurvey(): Survey
    {
        if (! $this->survey) {
            $this->survey = $this->tracker->getSurvey($this->_gemsData['gto_id_survey']);
        }

        return $this->survey;
    }

    /**
     *
     * @return int \Gems survey id
     */
    public function getSurveyId(): int
    {
        return (int)$this->_gemsData['gto_id_survey'];
    }

    /**
     *
     * @param string $language (ISO) language string
     * @return \MUtil\Model\ModelAbstract
     */
    public function getSurveyAnswerModel(string $language): MetaModelInterface
    {
        $survey = $this->getSurvey();
        return $survey->getAnswerModel($language);
    }

    /**
     *
     * @return string Name of the survey
     */
    public function getSurveyName(): string
    {
        $survey = $this->getSurvey();
        return $survey->getName();
    }

    /**
     * Returns the number of unanswered tokens for the person answering this token,
     * minus this token itself
     *
     * @return int
     */
    public function getTokenCountUnanswered(): int
    {
        $tokenSelect = new LaminasTokenSelect($this->resultFetcher);
        $tokenSelect
                ->columns([new Expression('COUNT(*)')])
                ->andReceptionCodes([], false)
                ->andSurveys([])
                ->andRounds([])
                ->forRespondent($this->getRespondentId())
                ->forGroupId($this->getSurvey()->getGroupId())
                ->onlySucces()
                ->onlyValid()
                ->forWhere([
                    'gsu_active' => 1,
                    'gro_active = 1 OR gro_active IS NULL',
                ])
                ->withoutToken($this->_tokenId);

        $this->_addRelation($tokenSelect);

        return $tokenSelect->fetchOne();
    }

    /**
     *
     * @return string token
     */
    public function getTokenId(): string
    {
        return $this->_tokenId;
    }

    /**
     * Get the track engine that generated this token
     *
     * @return TrackEngineInterface
     */
    public function getTrackEngine(): TrackEngineInterface
    {
        if ($this->exists) {
            return $this->tracker->getTrackEngine($this->_gemsData['gto_id_track']);
        }

        throw new Coding('Coding error: requesting track engine for non existing token.');
    }

    /**
     *
     * @return int gems_tracks track id
     */
    public function getTrackId(): int
    {
        return (int)$this->_gemsData['gto_id_track'];
    }

    public function getTrackName(): string
    {
        $trackData = $this->resultFetcher->fetchRow("SELECT gtr_track_name FROM gems__tracks WHERE gtr_id_track = ?", [$this->getTrackId()]);
        return $trackData['gtr_track_name'];
    }

    /**
     *
     * @param string $language The language currently used by the user
     * @param int $userId The id of the gems user
     * @throws \Gems\Tracker\Source\SurveyNotFoundException
     */
    public function getUrl(string $language, int $userId, ?string $returnUrl = null): string
    {
        $this->setTokenStart($language, $userId, $returnUrl);

        $this->handleBeforeAnswering();

        $survey = $this->getSurvey();
        return $survey->getTokenUrl($this, $language);
    }

    /**
     *
     * @return DateTimeInterface|null Valid from as a date or null
     */
    public function getValidFrom(): DateTimeInterface|null
    {
        return $this->getDateTime('gto_valid_from');
    }

    /**
     *
     * @return ?DateTimeInterface Valid until as a date or null
     */
    public function getValidUntil(): DateTimeInterface|null
    {
        return $this->getDateTime('gto_valid_until');
    }

    /**
     * Survey dependent calculations / answer changes that must occur after a survey is completed
     *
     * @return array The changed values
     */
    public function handleAfterCompletion(): array|null
    {
        $survey = $this->getSurvey();
        $completedEvent = $survey->getSurveyCompletedEvent();

        $eventName = 'gems.survey.completed';

        if ($this->eventDispatcher->hasListeners($eventName)) {
            // Remove previous gems survey completed events if set
            $listeners = $this->eventDispatcher->getListeners($eventName);
            foreach($listeners as $listener) {
                $order = $this->eventDispatcher->getListenerPriority($eventName, $listener);
                if ($order === 100) {
                    $this->eventDispatcher->removeListener($eventName, $listener);
                }
            }
        }

        if (! $completedEvent && !$this->eventDispatcher->hasListeners($eventName)) {
            return null;
        }

        if ($completedEvent) {
            $eventFunction = function (TokenEvent $event) use ($completedEvent) {
                $token = $event->getToken();
                try {
                    $changed = $completedEvent->processTokenData($token);
                    if (is_array($changed)) {
                        $event->addChanged($changed);
                    }
                } catch (\Exception $e) {
                    throw new \Exception('Event: ' . $completedEvent->getEventName() . '. ' . $e->getMessage());
                }
            };
            $this->eventDispatcher->addListener($eventName, $eventFunction, 100);
        }

        $tokenEvent = new TokenEvent($this);
        try {
            $this->eventDispatcher->dispatch($tokenEvent, $eventName);
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                "After completion event error for token %s on survey '%s': %s",
                $this->_tokenId,
                $this->getSurveyName(),
                $e->getMessage()
            ));
        }
        if ($completedEvent) {
            // Remove this event to prevent double triggering
            $this->eventDispatcher->removeListener($eventName, $eventFunction);
        }

        $changed = $tokenEvent->getChanged();
        if ($changed && is_array($changed)) {

            $this->setRawAnswers($changed);
        }

        return $changed;
    }

    /**
     * Survey dependent calculations / answer changes that must occur after a survey is completed
     *
     * @param string $tokenId The tokend the answers are for
     * @param array $tokenAnswers Array with answers. May be changed in process
     * @return array The changed values
     */
    public function handleBeforeAnswering(): array|null
    {
        $survey = $this->getSurvey();
        $beforeAnswerEvent  = $survey->getSurveyBeforeAnsweringEvent();

        $eventName = 'gems.survey.before-answering';

        if (! $beforeAnswerEvent && !$this->eventDispatcher->hasListeners($eventName)) {
            return null;
        }

        if ($beforeAnswerEvent) {
            $eventFunction = function (TokenEvent $event) use ($beforeAnswerEvent) {
                $token = $event->getToken();
                try {
                    $changed = $beforeAnswerEvent->processTokenInsertion($token);
                    if (is_array($changed) && $changed) {
                        $event->addChanged($changed);
                    }
                } catch (\Exception $e) {
                    throw new \Exception('Event: ' . $beforeAnswerEvent->getEventName() . '. ' . $e->getMessage());
                }
            };
            $this->eventDispatcher->addListener($eventName, $eventFunction, 100);
        }

        $tokenEvent = new TokenEvent($this);

        try {
            $this->eventDispatcher->dispatch($tokenEvent, $eventName);
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                "Before answering before event error for token %s on survey '%s': %s",
                $this->_tokenId,
                $this->getSurveyName(),
                $e->getMessage()
            ));
        }
        if ($beforeAnswerEvent) {
            // Remove this event to prevent double triggering
            $this->eventDispatcher->removeListener($eventName, $eventFunction);
        }

        $changed = $tokenEvent->getChanged();
        if ($changed && is_array($changed)) {

            $this->setRawAnswers($changed);

            if (Tracker::$verbose) {
                \MUtil\EchoOut\EchoOut::r($changed, 'Source values for ' . $this->_tokenId . ' changed by event.');
            }
        }

        return $changed;
    }

    /**
     * Returns true when the answers are loaded.
     *
     * There may not be any answers, but the attemt to retrieve them was made.
     *
     * @return bool
     */
    public function hasAnswersLoaded(): bool
    {
        return (bool) isset($this->_sourceDataRaw);
    }

    /**
     *
     * @deprecated Use the ReceptionCode->hasRedoCode
     * @return bool
     */
    public function hasRedoCode(): bool
    {
        return $this->getReceptionCode()->hasRedoCode();
    }

    /**
     * True if the reception code is a redo survey copy.
     *
     * @deprecated Use the ReceptionCode->hasRedoCopyCode
     * @return bool
     */
    public function hasRedoCopyCode(): bool
    {
        return $this->getReceptionCode()->hasRedoCopyCode();
    }

    /**
     * Is this token linked to a relation?
     *
     * @return bool
     */
    public function hasRelation(): bool
    {
        if (array_key_exists('gto_id_relationfield', $this->_gemsData) && $this->_gemsData['gto_id_relationfield'] > 0) {
            // We have a relation
            return true;
        }

        // no relation
        return false;
    }

    /**
     *
     * @return bool
     */
    public function hasResult(): bool
    {
        if (isset($this->_gemsData['gto_result'])) {
            return (bool)$this->_gemsData['gto_result'];
        }
        return false;
    }

    /**
     *
     * @deprecated Use the ReceptionCode->isSuccess
     * @return bool
     */
    public function hasSuccesCode(): bool
    {
        return $this->getReceptionCode()->isSuccess();
    }

    /**
     * True is this token was exported to the source.
     *
     * @return bool
     */
    public function inSource(): bool
    {
        if ($this->exists) {
            $survey = $this->getSurvey();

            return $survey->inSource($this);
        } else {
            return false;
        }
    }

    /**
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return isset($this->_gemsData['gto_completion_time']) && $this->_gemsData['gto_completion_time'];
    }

    /**
     * True when the valid from is set and in the past and the valid until is not set or is in the future
     *
     * @return bool
     */
    public function isCurrentlyValid(): bool
    {
        if ($this->isNotYetValid()) {
            return false;
        }
        if ($this->isExpired()) {
            return false;
        }
        return true;
    }

    /**
     * True when the valid until is set and is in the past
     * @return bool
     */
    public function isExpired(): bool
    {
        $date = $this->getValidUntil();

        if ($date instanceof DateTimeInterface) {
            return time() > $date->getTimestamp();
        }

        return false;
    }

    /**
     * Can mails be sent for this token?
     *
     * Cascades to track and respondent level mailable setting
     * also checks is the email field for respondent or relation is not null
     *
     * @return bool
     */
    public function isMailable(): bool
    {
        $email = $this->getEmail();
        if ($this->hasRelation()) {
            $filler = $this->getRelation();
        } else {
            $filler = $this->getRespondent();
        }
        $mailable = !empty($email) && $this->getRespondentTrack()->isMailable() && $filler->isMailable();

        return $mailable;
    }

    /**
     * True when the valid from is in the future or not yet set
     *
     * @return bool
     */
    public function isNotYetValid(): bool
    {
        $date = $this->getValidFrom();

        if ($date instanceof DateTimeInterface) {
            return time() < $date->getTimestamp();
        }

        return true;
    }

    public function isStarted(): bool
    {
        return isset($this->_gemsData['gto_start_time']) && $this->_gemsData['gto_start_time'];
    }

    /**
     *
     * @return bool True when this date was set by user input
     */
    public function isValidFromManual(): bool
    {
        return isset($this->_gemsData['gto_valid_from_manual']) && $this->_gemsData['gto_valid_from_manual'];
    }

    /**
     *
     * @return bool True when this date was set by user input
     */
    public function isValidUntilManual(): bool
    {
        return isset($this->_gemsData['gto_valid_until_manual']) && $this->_gemsData['gto_valid_until_manual'];
    }

    /**
     *
     * @return bool True when this user has the right to view these answers
     */
    public function isViewable(): bool
    {
        if (isset($this->_gemsData['show_answers']) && $this->_gemsData['show_answers']) {
            return $this->currentUser->isAllowedOrganization($this->getOrganizationId());
        }

        return false;
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return self (continuation pattern)
     */
    public function refresh(array|null $gemsData = null): self
    {
        if (is_array($gemsData)) {
            $this->_gemsData = $gemsData + $this->_gemsData;
        } else {
            $tokenSelect = new LaminasTokenSelect($this->resultFetcher);

            $groupId = $this->currentUser instanceof User ? $this->currentUser->getGroupId() : 0;

            $tokenSelect
                    ->andReceptionCodes()
                    ->andRespondents()
                    ->andRespondentOrganizations()
                    ->addStatus()
                    ->addShowAnswers($groupId)
                    ->forTokenId($this->_tokenId);

            $this->_gemsData = $tokenSelect->fetchRow();
            if (! $this->_gemsData) {
                // on failure, reset to empty array
                $this->_gemsData = [];
            }
        }
        $this->_gemsData = $this->maskRepository->applyMaskToRow($this->_gemsData);
        $this->exists = isset($this->_gemsData['gto_id_token']);

        return $this;
    }

    /**
     * Refresh the consent Code
     *
     * @param string $consentCode
     */
    public function refreshConsent(): string|null
    {
        if (isset($this->_gemsData['gco_code'])) {
            // Setting the gco_code to false will make sure the data is reloaded
            $this->_gemsData['gco_code'] = false;
            $this->getConsentCode();
        }
        return null;
    }

    public function setBy(int $userId): int
    {
        $values['gto_by'] = $userId;
        $changed = $this->_updateToken($values, $userId);

        return $changed;
    }

    /**
     *
     * @param string|DateTimeInterface|null $completionTime Completion time as a date or null
     * @param int $userId The current user
     * @return self (continuation pattern)
     */
    public function setCompletionTime(string|DateTimeInterface|null $completionTime, int $userId): self
    {
        $values['gto_completion_time'] = null;
        if (!is_null($completionTime)) {
            if (! $completionTime instanceof DateTimeInterface) {
                $completionTime = Model::getDateTimeInterface($completionTime);
            }
            if ($completionTime instanceof DateTimeInterface) {
                $values['gto_completion_time'] = $completionTime->format(Tracker::DB_DATETIME_FORMAT);
            }
        }
        $this->_updateToken($values, $userId);

        $survey = $this->getSurvey();
        $source = $survey->getSource();
        $source->setTokenCompletionTime($this, $completionTime, $survey->getSurveyId(), $survey->getSourceSurveyId());

        $this->refresh();
        $this->checkTokenCompletion($userId);

        return $this;
    }

    /**
     * Add 1 to the number of messages sent and change the sent date
     */
    public function setMessageSent(): void
    {
        $values = [
            'gto_mail_sent_num' => new \Zend_Db_Expr('gto_mail_sent_num + 1'),
            'gto_mail_sent_date' => (new DateTimeImmutable())->format('Y-m-d'),
        ];

        $this->_updateToken($values, $this->currentUser->getUserId());
    }

    /**
     * Sets the next token in this track
     *
     * @param Token|bool $token
     * @return self (continuation pattern)
     */
    public function setNextToken(Token|bool $token): self
    {
        $this->_nextToken = $token;

        if ($token instanceof Token) {
            $token->_previousToken = $this;
        }

        return $this;
    }

    /**
     * Sets answers for this token to the values defined in the $answers array. Also handles updating the
     * internal answercache if present
     *
     * @param array $answers
     */
    public function setRawAnswers(array $answers): void
    {
        $survey = $this->getSurvey();
        $source = $survey->getSource();

        $source->setRawTokenAnswers($this, $answers, $survey->getSurveyId(), $survey->getSourceSurveyId());

        // They are not always loaded
        if ($this->hasAnswersLoaded()) {
            //Now update internal answer cache
            $this->_sourceDataRaw = $answers + $this->_sourceDataRaw;
        }
    }

    /**
     * Set the reception code for this token and make sure the necessary
     * cascade to the source takes place.
     *
     * @param string $code The new (non-success) reception code or a \Gems\Util\ReceptionCode object
     * @param string $comment Comment False values leave value unchanged
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setReceptionCode(ReceptionCode|string $code, string|bool|null $comment, int $userId): int
    {
        // Make sure it is a ReceptionCode object
        if (! $code instanceof ReceptionCode) {
            $code = $this->receptionCodeRepository->getReceptionCode($code);
        }
        $values['gto_reception_code'] = $code->getCode();
        if ($comment) {
            $values['gto_comment'] = $comment;
        }
        // \MUtil\EchoOut\EchoOut::track($values);

        $changed = $this->_updateToken($values, $userId);

        if ($changed) {
            if ($code->isOverwriter() || (! $code->isSuccess())) {
                $survey = $this->getSurvey();

                // Update the consent code in the source
                if ($survey->inSource($this)) {
                    $survey->updateConsent($this);
                }
            }
        }

        return $changed;
    }

    /**
     * Set a round description for the token
     *
     * @param  string The new round description
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function setRoundDescription(string $description, int $userId): int
    {
        $values = $this->_gemsData;
        $values['gto_round_description'] = $description;
        return $this->_updateToken($values, $userId);
    }


    public function setTokenStart(string $language, int $userId, string|null $returnUrl = null): void
    {
        $survey = $this->getSurvey();
        $survey->copyTokenToSource($this, $language);

        if (! $this->_gemsData['gto_in_source']) {
            $values['gto_start_time'] = new DateTimeImmutable();
            $values['gto_in_source']  = 1;

            $oldTokenId = $this->getCopiedFrom();
            if ($oldTokenId) {
                $oldToken = $this->tracker->getToken($oldTokenId);
                if ($oldToken->getReceptionCode()->hasRedoCopyCode()) {
                    $this->setRawAnswers($oldToken->getRawAnswers());
                }
            }
        }
        $values['gto_by'] = $userId;
        $values['gto_return_url'] = $returnUrl;

        $this->_updateToken($values, $userId);
    }

    /**
     *
     * @param mixed $validFrom \DateTimeInterface or string
     * @param mixed $validUntil null, \DateTimeInterface or string. False values leave values unchangeds
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setValidFrom(DateTimeInterface|string|null $validFrom, DateTimeInterface|string|null $validUntil, int $userId): int
    {
        $mailSentDate = $this->getMailSentDate();
        if (! $mailSentDate instanceof DateTimeInterface) {
            $mailSentDate = Model::getDateTimeInterface($mailSentDate, [Tracker::DB_DATE_FORMAT, Tracker::DB_DATETIME_FORMAT]);
        }
        if ($validFrom && $mailSentDate) {
            // Check for newerness

            if ($validFrom instanceof DateTimeInterface) {
                $start = $validFrom;
            } else {
                $start = DateTimeImmutable::createFromFormat(Tracker::DB_DATETIME_FORMAT, $validFrom);
            }

            if ($start < $mailSentDate) {
                $values['gto_mail_sent_date'] = null;
                $values['gto_mail_sent_num']  = 0;

                $format = Model::getTypeDefault(Model::TYPE_DATETIME, 'storageFormat');

                $now = new DateTimeImmutable();
                $newComment = sprintf(
                    $this->translator->_('%s: Reset number of contact moments because new start date %s is later than last contact date (%s).'),
                    $now->format($format),
                    $start->format($format),
                    $mailSentDate->format($format)
                );
                $comment = $this->getComment();
                if (!empty($comment)) {
                    $comment .= "\n";
                }
                $values['gto_comment'] = $comment .= $newComment;
            }
        }

        if ($validFrom instanceof DateTimeInterface) {
            $validFrom = $validFrom->format(Tracker::DB_DATETIME_FORMAT);
        } elseif ('' === $validFrom) {
            $validFrom = null;
        }
        if ($validUntil instanceof DateTimeInterface) {
            $validUntil = $validUntil->format(Tracker::DB_DATETIME_FORMAT);
        } elseif ('' === $validUntil) {
            $validUntil = null;
        }

        $values['gto_valid_from'] = $validFrom;
        $values['gto_valid_until'] = $validUntil;

        return $this->_updateToken($values, $userId);
    }

    /**
     * Handle sending responses to the response database (if used)
     *
     * Triggered by checkTokenCompletion
     *
     * @param int $userId The id of the gems user
     */
    protected function toResponseDatabase(int $userId): void
    {
        $responses = $this->getRawAnswers();

        $source = $this->getSurvey()->getSource();
        if ($source instanceof SourceAbstract) {
            $metaFields = $source::$metaFields;
            foreach ($metaFields as $field) {
                if (array_key_exists($field, $responses)) {
                    unset($responses[$field]);
                }
            }
        }

        $message = new TokenResponse($this->getTokenId(), $responses, $userId);

        $this->messageBus->dispatch($message);

        //$responseDb = $this->project->getResponseDatabase();

        // WHY EXPLANATION!!
        //
        // For some reason mysql prepared parameters do nothing with a \Zend_Db_Expr
        // object and that causes an error when using CURRENT_TIMESTAMP
        /*$current = (new DateTimeImmutable())->format(Tracker::DB_DATETIME_FORMAT);
        $rValues = array(
            'gdr_id_token'   => $this->_tokenId,
            'gdr_changed'    => $current,
            'gdr_changed_by' => $userId,
            'gdr_created'    => $current,
            'gdr_created_by' => $userId,
        );
        $responses = $this->getRawAnswers();

        $source = $this->getSurvey()->getSource();
        if ($source instanceof SourceAbstract) {
            $metaFields = $source::$metaFields;
            foreach ($metaFields as $field) {
                if (array_key_exists($field, $responses)) {
                    unset($responses[$field]);
                }
            }
        }

        // first read current responses to differentiate between insert and update
        $responseSelect = $responseDb->select()->from('gemsdata__responses', array('gdr_answer_id', 'gdr_response'))
                ->where('gdr_id_token = ?', $this->_tokenId);
        $currentResponses = $responseDb->fetchPairs($responseSelect);

        if (! $currentResponses) {
            $currentResponses = array();
        }
        // \MUtil\EchoOut\EchoOut::track($currentResponses, $responses);

        // Prepare sql
        $sql = "UPDATE gemsdata__responses
            SET `gdr_response` = ?, `gdr_changed` = ?, `gdr_changed_by` = ?
            WHERE gdr_id_token = ? AND gdr_answer_id = ? AND gdr_answer_row = 1";
        $statement = $responseDb->prepare($sql);

        $inserts = array();
        foreach ($responses as $fieldName => $response) {
            $rValues['gdr_answer_id']  = $fieldName;
            if (is_array($response)) {
                $response = join('|', $response);
            }
            $rValues['gdr_response']  = $response;

            if (array_key_exists($fieldName, $currentResponses)) {    // Already exists, do update
                // But only if value changed
                if ($currentResponses[$fieldName] != $response) {
                    try {
                        // \MUtil\EchoOut\EchoOut::track($sql, $rValues['gdr_id_token'], $fieldName, $response);
                        $statement->execute(array(
                            $response,
                            $rValues['gdr_changed'],
                            $rValues['gdr_changed_by'],
                            $rValues['gdr_id_token'],
                            $fieldName
                        ));
                    } catch (\Zend_Db_Statement_Exception $e) {
                        error_log($e->getMessage());

                        $this->logger->error(LogHelper::getMessageFromException($e));
                    }
                }
            } else {
                // We add the inserts together in the next prepared statement to improve speed
                $inserts[$fieldName] = $rValues;
            }
        }

        if (count($inserts)>0) {
            // \MUtil\EchoOut\EchoOut::track($inserts);
            try {
                $fields = array_keys(reset($inserts));
                $fields = array_map(array($responseDb, 'quoteIdentifier'), $fields);
                $sql = 'INSERT INTO gemsdata__responses (' .
                        implode(', ', $fields) . ') VALUES (' .
                        implode(', ', array_fill(1, count($fields), '?')) . ')';

                // \MUtil\EchoOut\EchoOut::track($sql);
                $statement = $responseDb->prepare($sql);

                foreach($inserts as $insert) {
                    // \MUtil\EchoOut\EchoOut::track($insert);
                    $statement->execute($insert);
                }

            } catch (\Zend_Db_Statement_Exception $e) {
                error_log($e->getMessage());
                $this->logger->error(LogHelper::getMessageFromException($e));
            }
        }*/
    }
}
