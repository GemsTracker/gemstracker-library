<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\Mock;

use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Log\Loggers;
use Gems\Project\ProjectSettings;
use Gems\Repository\ConsentRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\Tracker\ReceptionCode;
use Gems\Tracker\Token;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use MUtil\Translate\Translator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Gems
 * @subpackage Tracker\Token
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class TokenReadonly extends Token
{
    /**
     * @var array function => [changes]
     */
    protected array $_changes = [];

    /**
     * @var array|null The local current answers
     */
    private array|null $_localDataRaw = null;

    public function __construct(
        protected readonly Token $parentToken,
        ResultFetcher $resultFetcher,
        MaskRepository $maskRepository,
        Tracker $tracker,
        ProjectSettings $projectSettings,
        ConsentRepository $consentRepository,
        OrganizationRepository $organizationRepository,
        ReceptionCodeRepository $receptionCodeRepository,
        RespondentRepository $respondentRepository,
        ProjectOverloader $projectOverloader,
        Translated $translatedUtil,
        Locale $locale,
        TokenRepository $tokenRepository,
        EventDispatcherInterface $eventDispatcher,
        Translator $translator,
        MessageBusInterface $messageBus,
        Loggers $loggers,
        CurrentUserRepository $currentUserRepository
    ) {
        parent::__construct(
            $this->parentToken->_gemsData,
            $resultFetcher,
            $maskRepository,
            $tracker,
            $projectSettings,
            $consentRepository,
            $organizationRepository,
            $receptionCodeRepository,
            $respondentRepository,
            $projectOverloader,
            $translatedUtil,
            $locale,
            $tokenRepository,
            $eventDispatcher,
            $translator,
            $messageBus,
            $loggers,
            $currentUserRepository
        );
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
        $functionName = debug_backtrace()[1]['function'];
        $this->_changes[$functionName]['oldValues'] = [];
        $this->_changes[$functionName]['newValues'] = [];
        
        $this->tracker->filterChangesOnly($this->_gemsData, $values);

        foreach ($values as $key => $val) {
            $this->_changes[$functionName]['oldValues'] = $this->_gemsData[$key];
            $this->_changes[$functionName]['newValues'] = $val;
            $this->_gemsData[$key] = $val;
        }

        $this->_changes[$functionName]['changes'] = $values ? 1 : 0;

        return $this->_changes[$functionName]['changes'];
    }

    /**
     * @param $log mixed Optional action log, set from outside the token
     */
    public function addLog($log): void
    {
        foreach ((array) $log as $item) {
            $this->_changes['log'][] = $item;
        }
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
        // Do not try to imitate this action
        return null;
    }

    /**
     * @param null $function Optional function name
     * @param null $param Optional paramter name
     * @return array
     */
    public function getMockChanges(string|null $function = null, string|null $param = null): array
    {
        if ($param) {
            if (isset($this->_changes[$function][$param])) {
                return $this->_changes[$function][$param];
            }
            return [];
        }
        if ($function) {
            if (isset($this->_changes[$function])) {
                return $this->_changes[$function];
            }
            return [];
        }
        return $this->_changes;
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
        if (! is_array($this->_localDataRaw)) {
            $this->_localDataRaw = parent::getRawAnswers();
        }
        return $this->_localDataRaw;
    }

    /**
     *
     * @param string $language The language currently used by the user
     * @param int $userId The id of the gems user
     * @throws \Gems\Tracker\Source\SurveyNotFoundException
     */
    public function getUrl(string $language, int $userId, string $returnUrl): string
    {
        $this->_changes[__FUNCTION__] = ['language' => $language, 'userId' => $userId];
        
        $survey = $this->getSurvey();

        // $survey->copyTokenToSource($this, $language);

        if (! $this->_gemsData['gto_in_source']) {
            $values['gto_start_time'] = new Expression('CURRENT_TIMESTAMP');
            $values['gto_in_source']  = 1;

            $oldTokenId = $this->getCopiedFrom();
            if ($oldTokenId) {
                $oldToken = $this->tracker->getToken($oldTokenId);
                if ($oldToken->getReceptionCode()->hasRedoCopyCode()) {
                    $this->setRawAnswers($oldToken->getRawAnswers());
                }
            }
        }
        $values['gto_by']         = $userId;
        $values['gto_return_url'] = $returnUrl;

        $this->_updateToken($values, $userId);

        $this->handleBeforeAnswering();

        return $survey->getTokenUrl($this, $language);
    }

    /**
     * Returns true when the answers are loaded.
     *
     * There may not be any answers, but the attemt to retrieve them was made.
     *
     * @return boolean
     */
    public function hasAnswersLoaded(): bool
    {
        return (bool) $this->_localDataRaw;
    }

    /**
     *
     * @param string|\DateTimeInterface $completionTime Completion time as a date or null
     * @param int $userId The current user
     * @return \Gems\Tracker\Token (continuation pattern)
     */
    public function setCompletionTime(string|null $completionTime, int $userId): self
    {
        $this->_changes[__FUNCTION__] = ['completionTime' => $completionTime, 'userId' => $userId];
        
        return parent::setCompletionTime($completionTime, $userId);
    }

    /**
     * Sets answers for this token to the values defined in the $answers array. Also handles updating the
     * internal answercache if present
     *
     * @param array $answers
     */
    public function setRawAnswers(array $answers): void
    {
        $this->_changes[__FUNCTION__] = ['answers' => $answers];        
        
        $this->_localDataRaw = $this->_localDataRaw + $answers;
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
    public function setReceptionCode(ReceptionCode|string $code, string $comment, int $userId): int
    {
        $this->_changes[__FUNCTION__] = ['code' => $code, 'comment' => $comment, 'userId' => $userId];

        return parent::setRoundDescription($code, $comment, $userId);
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
        $this->_changes[__FUNCTION__] = ['description' => $description, 'userId' => $userId];

        return parent::setRoundDescription($description, $userId);
    }

    /**
     *
     * @param mixed $validFrom DateTimeInterface or string
     * @param mixed $validUntil null, DateTimeInterface or string. False values leave values unchangeds
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setValidFrom(DateTimeInterface|string|null $validFrom, DateTimeInterface|string|null $validUntil, int $userId): int
    {
        $this->_changes[__FUNCTION__] = ['validFrom' => $validFrom, 'validUntil' => $validUntil, 'userId' => $userId];

        return parent::setValidFrom($validFrom, $validUntil, $userId);
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
        // do nothing
    }

    /**
     * Clean up existing values
     */
    public function unsetRawAnswers(): void
    {
        $this->_localDataRaw                    = [];
        $this->_changes                         = [];
        $this->_gemsData['gto_in_source']       = 0;
        $this->_gemsData['gto_completion_time'] = null;
        $this->_gemsData['gto_reception_code']  = ReceptionCodeRepository::RECEPTION_OK;
    }
}