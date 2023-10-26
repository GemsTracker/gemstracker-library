<?php

namespace Gems\Tracker\Token;

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Menno Dekker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Token;

use Gems\Db\ResultFetcher;
use Gems\Repository\TokenRepository;
use Gems\Tracker\Token;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;

/**
 * Helps building select statements for the Token model
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class LaminasTokenSelect
{
    protected array $columns = [];

    protected Select $select;

    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
    )
    {
        $this->select = $this->resultFetcher->getSelect('gems__tokens');
        $this->columns();
    }

    /**
     *
     * @return string SQL Select statement
     */
    public function __toString(): string
    {
        return $this->select->getSqlString($this->resultFetcher->getPlatform());
    }

    protected function addColumns(array $columns): void
    {
        $this->columns = array_merge($this->columns, $columns);
        $this->select->columns($this->columns);
    }

    /**
     * Add the status column to the select, $this->andReceptionCodes() must be called first
     */
    public function addShowAnswers(int $groupId): self
    {
        $this->andSurveys([]);
        $this->addColumns(
            [
                'show_answers' => TokenRepository::getShowAnswersExpression($groupId),
            ]
        );

        return $this;
    }

    /**
     * Add the status column to the select, $this->andReceptionCodes() must be called first
     */
    public function addStatus(): self
    {
        // Some queries use multiple token tables
        $expression = str_replace('gto_', 'gems__tokens.gto_', TokenRepository::getStatusExpression()->getExpression());

        $this->addColumns(
            ['token_status' => new Expression($expression)]
        );

        return $this;
    }

    /**
     * Add consent descriptions to the select statement
     *
     * @param string|array $fields
     * @return self
     */
    public function andConsents(string|array $fields = '*'): self
    {
        $this->select->join('gems__consents',
            'gr2o_consent = gco_description',
            $fields);

        return $this;
    }

    /**
     * Add reception codes and token status calculation
     *
     * @param string|array $fields
     * @param bool   $addStatus When true the token status column is added (default)
     * @return self
     */
    public function andReceptionCodes(string|array $fields = '*', bool $addStatus = true): self
    {
        $this->select->join('gems__reception_codes',
            'gems__tokens.gto_reception_code = grc_id_reception_code',
            $fields);

        if ($addStatus) {
            $this->addStatus();
        }

        return $this;
    }

    /**
     * Add Respondent info to the select statement
     *
     * @param string|array $fields
     * @return self
     */
    public function andRespondents(string|array $fields = '*'): self
    {
        $this->select->join('gems__respondents',
            'gto_id_respondent = grs_id_user',
            $fields);

        return $this;
    }

    /**
     * Add RespondentOrganization info to the select statement
     *
     * @param string|array $fields
     * @return self
     */
    public function andRespondentOrganizations(string|array $fields = '*'): self
    {
        $this->select->join('gems__respondent2org',
            'gto_id_respondent = gr2o_id_user AND gto_id_organization = gr2o_id_organization',
            $fields);

        return $this;
    }

    /**
     * Add Respondent Track info to the select statement
     *
     * @param string|array $fields
     * @param boolean $groupBy Optional, add these fields to group by statement
     * @return self
     */
    public function andRespondentTracks(string|array $fields = '*', bool $groupBy = false): self
    {
        $this->select->join('gems__respondent2track',
            'gto_id_respondent_track = gr2t_id_respondent_track',
            $fields);

        if ($groupBy && is_array($fields)) {
            $this->select->group($fields);
        }

        return $this;
    }

    /**
     * Adds round info to the select statement
     *
     * @param string|array $fields
     * @return self
     */
    public function andRounds(string|array $fields = '*'): self
    {
        $this->select->join('gems__rounds',
            'gto_id_round = gro_id_round',
            $fields);

        return $this;
    }

    /**
     * Add survey info to the select statement
     *
     * @param string|array $fields
     * @return self
     */
    public function andSurveys(string|array $fields = '*'): self
    {
        $this->select->join('gems__surveys',
            'gto_id_survey = gsu_id_survey',
            $fields);

        return $this;
    }

    /**
     * Add track info to the select statement
     *
     * @param string|array $fields
     * @param boolean $groupBy Optional, add these fields to group by statement
     * @return self
     */
    public function andTracks(string|array $fields = '*', bool $groupBy = false): self
    {
        $this->select->join('gems__tracks',
            'gto_id_track = gtr_id_track',
            $fields);

        if ($groupBy && is_array($fields)) {
            $this->select->group($fields);
        }

        return $this;
    }

    public function columns(string|array $fields = '*'): self
    {
        if ($fields === '*') {
            $this->columns = [Select::SQL_STAR];
            $fields = [Select::SQL_STAR];
        }
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $this->select->columns($fields);

        return $this;
    }

    /**
     * @return array
     */
    public function fetchAll(): array
    {
        return $this->resultFetcher->fetchAll($this->select);
    }

    /**
     * @return mixed
     */
    public function fetchOne(): mixed
    {
        $this->select->limit(1);

        return $this->resultFetcher->fetchOne($this->select);
    }

    /**
     * @return array|bool
     */
    public function fetchRow(): array|bool
    {
        $this->select->limit(1);

        return $this->resultFetcher->fetchRow($this->select) ?? false;
    }

    /**
     * Select only a specific group
     *
     * @param int $groupId Gems group id
     * @return self
     */
    public function forGroupId(int $groupId): self
    {
        $this->select->where(['gsu_id_primary_group' => $groupId]);

        return $this;
    }

    /**
     * Select the token before the current token
     *
     * @param Token $token
     * @return self
     */
    public function forNextToken(Token $token): self
    {
        $where = new Where();
        $where->equalTo('gto_id_respondent_track', $token->getRespondentTrackId());
        $where->notEqualTo('gto_id_token', $token->getTokenId());

        // The previous toke should have either an earlier round order
        $earlierOrder  = new Operator('gto_round_order', Operator::OP_LT,  $token->getRoundOrder());
        // Or the same order
        $sameOrder     = new Operator('gto_round_order', Operator::OPERATOR_EQUAL_TO,  $token->getRoundOrder());
        // And being created earlier
        $earlierCreation = new Operator('gto_created', Operator::OP_LT,  $token->getCreationDate());

        $sameRound  = new Predicate([$sameOrder, $earlierCreation], PredicateSet::COMBINED_BY_AND);
        $roundWhere = new Predicate([$earlierOrder, $sameRound], PredicateSet::COMBINED_BY_OR);
        $where->andPredicate($roundWhere);

        $this->select->where($where)
            ->order(['gto_round_order DESC', 'gto_created DESC']);

        return $this;
    }

    /**
     * Select the token after the passed token
     *
     * @param Token $token
     * @return self
     */
    public function forPreviousToken(Token $token): self
    {
        $where = new Where();
        $where->equalTo('gto_id_respondent_track', $token->getRespondentTrackId());
        $where->notEqualTo('gto_id_token', $token->getTokenId());

        // The next toke should have either a later round order order
        $laterOrder    = new Operator('gto_round_order', Operator::OP_GT,  $token->getRoundOrder());

        // Or the same order
        $sameOrder     = new Operator('gto_round_order', Operator::OPERATOR_EQUAL_TO,  $token->getRoundOrder());
        // And being created later
        $laterCreation = new Operator('gto_created', Operator::OP_GT,  $token->getCreationDate());

        $sameRound  = new Predicate([$sameOrder, $laterCreation], PredicateSet::COMBINED_BY_AND);
        $roundWhere = new Predicate([$laterOrder, $sameRound], PredicateSet::COMBINED_BY_OR);
        $where->andPredicate($roundWhere);

        $this->select->where($where)
            ->order(['gto_round_order', 'gto_created']);

        return $this;
    }

    /**
     * Select only a specific respondent
     *
     * @param int $respondentId
     * @param int $organizationId Optional
     * @return self
     */
    public function forRespondent(int $respondentId, int $organizationId = null): self
    {
        if (null !== $respondentId) {
            $this->select->where(['gto_id_respondent' => $respondentId]);
        }
        if (null !== $organizationId) {
            $this->select->where(['gto_id_organization' => $organizationId]);
        }

        return $this;
    }

    /**
     * Select only for a specific Respondent Track ID
     *
     * @param int $respTrackId Respondent Track ID
     * @return self
     */
    public function forRespondentTrack(int $respTrackId): self
    {
        $this->select
            ->where(['gto_id_respondent_track' => $respTrackId])
            ->order(['gto_round_order',
                'gto_created']);

        return $this;
    }

    /**
     * Select only for a specific Round ID
     *
     * @param int $roundId Round ID
     * @return self
     */
    public function forRound(int $roundId): self
    {
        $this->select->where(['gto_id_round' => $roundId]);

        return $this;
    }

    /**
     * Select only a specific surveyId
     *
     * @param string $surveyCode
     * @return self
     */
    public function forSurveyCode(string $surveyCode): self
    {
        $this->select->where(['gsu_code' => $surveyCode]);

        return $this;
    }

    /**
     * Select only a specific surveyId
     *
     * @param int $surveyId
     * @return self
     */
    public function forSurveyId(int $surveyId): self
    {
        $this->select->where(['gto_id_survey' => $surveyId]);

        return $this;
    }

    /**
     * Select only a specific tokenId
     *
     * @param string $tokenId
     * @return self
     */
    public function forTokenId(string $tokenId): self
    {
        $this->select->where(['gto_id_token' => $tokenId]);

        return $this;
    }

    /**
     * For adding generic where statements
     *
     * @param array $conditions SQL Where conditions
     * @return self
     */
    public function forWhere(array $conditions): self
    {
        $this->select->where($conditions);

        return $this;
    }

    /**
     * Get the constructed select statement
     *
     * @return Select
     */
    public function getSelect(): Select
    {
        return $this->select;
    }

    /**
     * Select only active tokens
     *
     * Active is token already in surveyor and completiondate is null
     *
     * @param boolean $recentCheck Check only tokens with recent gto_start_time's
     * @return self
     */
    public function onlyActive(bool $recentCheck = false): self
    {
        $this->select
            ->where(['gto_in_source' => 1,
                'gto_completion_time IS NULL']);

        if ($recentCheck) {
            $this->select->where(['gto_start_time > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 7 DAY)']);
        }

        return $this;
    }

    /**
     * Select only completed tokens
     *
     * Comleted is token has a completiondate
     *
     * @return self
     */
    public function onlyCompleted(): self
    {
        $this->select
            ->where(['gto_completion_time IS NOT NULL']);

        return $this;
    }

    /**
     * Select tokens with receptioncodes with the success status 1
     *
     * @return self
     */
    public function onlySucces(): self
    {
        $this->select->where(['grc_success' => 1]);

        return $this;
    }

    /**
     * Select only valid tokens
     *
     * Not answered, and valid_from/to in right range
     *
     * @return self
     */
    public function onlyValid(): self
    {
        $this->select->where->isNull('gto_completion_time')
            ->lessThanOrEqualTo('gto_valid_from', new Expression('CURRENT_TIMESTAMP'))
            ->nest()->isNull('gto_valid_until')->or->greaterThanOrEqualTo('gto_valid_until', new Expression('CURRENT_TIMESTAMP'))->unnest();

        return $this;
    }

    /**
     *
     * @param mixed $spec The column(s) and direction to order by.
     * @return self
     */
    public function order(mixed $spec): self
    {
        $this->select->order($spec);

        return $this;
    }

    /**
     * Do not select the current token
     *
     * @param string $tokenId
     * @return self
     */
    public function withoutToken(string $tokenId): self
    {
        $this->select->where->notEqualTo('gto_id_token', $tokenId);

        return $this;
    }
}