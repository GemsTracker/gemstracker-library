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

use Gems\Util;

/**
 * Helps building select statements for the Token model
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TokenSelect
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Zend_Db_Select
     */
    private $sql_select;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     *
     * @param \Zend_Db_Adapter_Abstract $db Adapter to use
     * @param string|array $fields Optional select fieldlist
     */
<<<<<<< HEAD
    public function __construct(\Zend_Db_Adapter_Abstract $db, Util $util, $fields = "*")
=======
    public function __construct(\Zend_Db_Adapter_Abstract $db, \Gems_Util $util)
>>>>>>> 2.x-mail
    {
        $this->db   = $db;
        $this->util = $util;

        $this->sql_select = $this->db->select();
        $this->sql_select->from('gems__tokens');
    }

    /**
     *
     * @return string SQL Select statement
     */
    public function __toString(): string
    {
        return $this->sql_select->__toString();
    }

    /**
     * Add the status column to the select, $this->andReceptionCodes() must be called first
     *
     * @param int $groupId
     * @return $this
     * @throws \Zend_Db_Select_Exception
     */
    public function addShowAnswers(int $groupId): self
    {
        $this->andSurveys([]);
        $this->sql_select->columns(
            ['show_answers' => $this->util->getTokenData()->getShowAnswersExpression($groupId),],
            'gems__tokens'
        );

        return $this;
    }
    
    /**
     * Add the status column to the select, $this->andReceptionCodes() must be called first
     * 
     * @return self
     * @throws \Zend_Db_Select_Exception
     */
    public function addStatus(): self
    {
        // Some queries use multiple token tables
        $expr = str_replace('gto_', 'gems__tokens.gto_', (string) $this->util->getTokenData()->getStatusExpression());

        $this->sql_select->columns(
            ['token_status' => new \Zend_Db_Expr($expr)],
            'gems__tokens'
        );
                
        return $this;
    }
    
    /**
     * Add consent descriptions to the select statement
     *
     * @param string|array $fields
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function andConsents(string|array $fields = '*'): self
    {
        $this->sql_select->join('gems__consents',
                'gr2o_consent = gco_description',
                $fields);

        return $this;
    }

    /**
     * Add reception codes and token status calculation
     *
     * @param string|array $fields
     * @param bool   $addStatus When true the token status column is added (default)
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     * @throws \Zend_Db_Select_Exception
     */
    public function andReceptionCodes(string|array $fields = '*', bool $addStatus = true): self
    {
        $this->sql_select->join('gems__reception_codes',
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
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function andRespondents(string|array $fields = '*'): self
    {
        $this->sql_select->join('gems__respondents',
                'gto_id_respondent = grs_id_user',
                $fields);

        return $this;
    }

    /**
     * Add RespondentOrganization info to the select statement
     *
     * @param string|array $fields
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function andRespondentOrganizations(string|array $fields = '*'): self
    {
        $this->sql_select->join('gems__respondent2org',
                'gto_id_respondent = gr2o_id_user AND gto_id_organization = gr2o_id_organization',
                $fields);

        return $this;
    }

    /**
     * Add Respondent Track info to the select statement
     *
     * @param string|array $fields
     * @param boolean $groupBy Optional, add these fields to group by statement
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function andRespondentTracks(string|array $fields = '*', bool $groupBy = false): self
    {
        $this->sql_select->join('gems__respondent2track',
                'gto_id_respondent_track = gr2t_id_respondent_track',
                $fields);

        if ($groupBy && is_array($fields)) {
            $this->sql_select->group($fields);
        }

        return $this;
    }

    /**
     * Adds round info to the select statement
     *
     * @param string|array $fields
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function andRounds(string|array $fields = '*'): self
    {
        $this->sql_select->join('gems__rounds',
                'gto_id_round = gro_id_round',
                $fields);

        return $this;
    }

    /**
     * Add survey info to the select statement
     *
     * @param string|array $fields
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function andSurveys(string|array $fields = '*'): self
    {
        $this->sql_select->join('gems__surveys',
                'gto_id_survey = gsu_id_survey',
                $fields);

        return $this;
    }

    /**
     * Add track info to the select statement
     *
     * @param string|array $fields
     * @param boolean $groupBy Optional, add these fields to group by statement
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function andTracks(string|array $fields = '*', bool $groupBy = false): self
    {
        $this->sql_select->join('gems__tracks',
                'gto_id_track = gtr_id_track',
                $fields);

        if ($groupBy && is_array($fields)) {
            $this->sql_select->group($fields);
        }

        return $this;
    }

    public function columns(string|array $fields = '*'): self
    {
        $this->sql_select->columns($fields);

        return $this;
    }

    /**
     * @return array
     */
    public function fetchAll(): array
    {
        return $this->sql_select->query()->fetchAll();
    }

    /**
     * @return mixed
     */
    public function fetchOne(): mixed
    {
        $this->sql_select->limit(1);

        return $this->sql_select->query()->fetchColumn(0);
    }

    /**
     * @return array
     */
    public function fetchRow(): array
    {
        $this->sql_select->limit(1);

        return $this->sql_select->query()->fetch();
    }

    /**
     * Select only a specific group
     *
<<<<<<< HEAD
     * @param int $groupId \Gems group id
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @param int $groupId Gems group id
     * @return self
>>>>>>> 2.x-mail
     */
    public function forGroupId(int $groupId): self
    {

        // $this->andSurveys(array());

        $this->sql_select->where('gsu_id_primary_group = ?', $groupId);

        return $this;
    }

    /**
     * Select the token before the current token
     *
<<<<<<< HEAD
     * @param string|array $fields
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @param string $tokenId
     * @return self
>>>>>>> 2.x-mail
     */
    public function forNextTokenId(string $tokenId): self
    {
        $this->sql_select->join('gems__tokens as ct',
                'gems__tokens.gto_id_respondent_track = ct.gto_id_respondent_track AND
                    gems__tokens.gto_id_token != ct.gto_id_token AND
                        ((gems__tokens.gto_round_order < ct.gto_round_order) OR
                            (gems__tokens.gto_round_order = ct.gto_round_order AND gems__tokens.gto_created < ct.gto_created))',
                array());

        $this->sql_select
                ->where('ct.gto_id_token = ?', $tokenId)
                ->order('gems__tokens.gto_round_order DESC')
                ->order('gems__tokens.gto_created DESC');

        return $this;
    }

    /**
     * Select the token before the current token
     *
<<<<<<< HEAD
     * @param string|array $fields
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @param string $tokenId
     * @return self
>>>>>>> 2.x-mail
     */
    public function forPreviousTokenId(string $tokenId): self
    {
        $this->sql_select->join('gems__tokens as ct',
                'gems__tokens.gto_id_respondent_track = ct.gto_id_respondent_track AND
                    gems__tokens.gto_id_token != ct.gto_id_token AND
                        ((gems__tokens.gto_round_order > ct.gto_round_order) OR
                            (gems__tokens.gto_round_order = ct.gto_round_order AND gems__tokens.gto_created > ct.gto_created))',
                array());

        $this->sql_select
                ->where('ct.gto_id_token = ?', $tokenId)
                ->order('gems__tokens.gto_round_order')
                ->order('gems__tokens.gto_created');

        return $this;
    }

    /**
     * Select only a specific respondent
     *
<<<<<<< HEAD
     * @param string $respondentId
     * @param string $organizationId Optional
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @param int $respondentId
     * @param int $organizationId Optional
     * @return self
>>>>>>> 2.x-mail
     */
    public function forRespondent(int $respondentId, int $organizationId = null): self
    {
        if (null !== $respondentId) {
            $this->sql_select->where('gto_id_respondent = ?', $respondentId);
        }
        if (null !== $organizationId) {
            $this->sql_select->where('gto_id_organization = ?', $organizationId);
        }

        return $this;
    }

    /**
     * Select only for a specific Respondent Track ID
     *
     * @param int $respTrackId Respondent Track ID
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function forRespondentTrack(int $respTrackId): self
    {
        $this->sql_select
                ->where('gto_id_respondent_track = ?', $respTrackId)
                ->order('gto_round_order')
                ->order('gto_created');

        return $this;
    }

    /**
     * Select only for a specific Round ID
     *
     * @param int $roundId Round ID
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function forRound(int $roundId): self
    {
        $this->sql_select->where('gto_id_round = ?', $roundId);

        return $this;
    }

    /**
     * Select only a specific surveyId
     *
     * @param string $surveyId
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function forSurveyCode(string $surveyCode): self
    {
        $this->sql_select->where('gsu_code = ?', $surveyCode);

        return $this;
    }

    /**
     * Select only a specific surveyId
     *
<<<<<<< HEAD
     * @param string $surveyId
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @param int $surveyId
     * @return self
>>>>>>> 2.x-mail
     */
    public function forSurveyId(id $surveyId): self
    {
        $this->sql_select->where('gto_id_survey = ?', $surveyId);

        return $this;
    }

    /**
     * Select only a specific tokenId
     *
     * @param string $tokenId
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function forTokenId(string $tokenId): self
    {
        $this->sql_select->where('gto_id_token = ?', $tokenId);

        return $this;
    }

    /**
     * For adding generic where statements
     *
     * @param string $cond SQL Where condition.
     * @param mixed $bind optional bind values
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function forWhere(string $cond, mixed $bind = null): self
    {
        $this->sql_select->where($cond, $bind);

        return $this;
    }

    /**
     * Get the constructed select statement
     *
     * @return \Zend_Db_Select
     */
    public function getSelect(): \Zend_Db_Select
    {
        return $this->sql_select;
    }

    /**
     * Select only active tokens
     *
     * Active is token already in surveyor and completiondate is null
     *
     * @param boolean $recentCheck Check only tokens with recent gto_start_time's
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function onlyActive(bool $recentCheck = false): self
    {
        $this->sql_select
                ->where('gto_in_source = ?', 1)
                ->where('gto_completion_time IS NULL');

        if ($recentCheck) {
            $this->sql_select->where('gto_start_time > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 7 DAY)');
            //$this->sql_select->where('(gto_valid_until IS NULL OR gto_valid_until > CURRENT_TIMESTAMP)');
        }

        return $this;
    }

    /**
     * Select only completed tokens
     *
     * Comleted is token has a completiondate
     *
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function onlyCompleted(): self
    {
        $this->sql_select
                ->where('gto_completion_time IS NOT NULL');

        return $this;
    }

    /**
     * Select tokens with receptioncodes with the success status 1
     *
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function onlySucces(): self
    {
        $this->sql_select->where('grc_success = 1');

        return $this;
    }

    /**
     * Select only valid tokens
     *
     * Not answered, and valid_from/to in right range
     *
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function onlyValid(): self
    {
        $this->sql_select->where('gto_completion_time IS NULL')
                ->where('gto_valid_from <= CURRENT_TIMESTAMP')
                ->where('(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)');

        return $this;
    }

    /**
     *
     * @param mixed $spec The column(s) and direction to order by.
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function order(mixed $spec): self
    {
        $this->sql_select->order($spec);

        return $this;
    }

    /**
     * Do not select the current token
     *
     * @param string $tokenId
<<<<<<< HEAD
     * @return \Gems\Tracker\Token\TokenSelect
=======
     * @return self
>>>>>>> 2.x-mail
     */
    public function withoutToken(string $tokenId): self
    {
        $this->sql_select->where('gto_id_token != ?', $tokenId);

        return $this;
    }
}