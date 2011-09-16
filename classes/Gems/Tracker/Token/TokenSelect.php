<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Menno Dekker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Helps building select statements for the Token model
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Token_TokenSelect {

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Zend_Db_Select
     */
    private $sql_select;

    /**
     *
     * @param Zend_Db_Adapter_Abstract $db Adapter to use
     * @param type $fields Optional select fieldlist
     */
    public function __construct(Zend_Db_Adapter_Abstract $db, $fields = "*")
    {
        $this->db = $db;
        $this->sql_select = $this->db->select();
        $this->sql_select->from('gems__tokens', $fields);
    }

    /**
     *
     * @return string SQL Select statement
     */
    public function __toString()
    {
        return $this->sql_select->__toString();
    }

    /**
     * Add consent descriptions to the select statement
     *
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function andConsents($fields = '*') {
        $this->sql_select->joinLeft('gems__consents',
                'gr2o_consent = gco_description',
                $fields);

        return $this;
    }

    /**
     *
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function andReceptionCodes($fields = '*')
    {
        $this->sql_select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code');

        return $this;
    }

    /**
     * Add Respondent info to the select statement
     *
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function andRespondents($fields = '*') {
        $this->sql_select->join('gems__respondents',
                'gto_id_respondent = grs_id_user',
                $fields);

        return $this;
    }

    /**
     * Add RespondentOrganization info to the select statement
     *
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function andRespondentOrganizations($fields = '*') {
        $this->sql_select->join('gems__respondent2org',
                'gto_id_respondent = gr2o_id_user AND gto_id_organization = gr2o_id_organization',
                $fields);

        return $this;
    }

    /**
     * Adds round info to the select statement
     *
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function andRounds($fields = '*') {
        $this->sql_select->join('gems__rounds',
                'gto_id_round = gro_id_round',
                $fields);

        return $this;
    }

    /**
     * Add survey info to the select statement
     *
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function andSurveys($fields = '*') {
        $this->sql_select->join('gems__surveys',
                'gto_id_survey = gsu_id_survey',
                $fields);

        return $this;
    }

    /**
     * @return array
     */
    public function fetchAll()
    {
        return $this->sql_select->query()->fetchAll();
    }

    /**
     * @return mixed
     */
    public function fetchOne()
    {
        $this->sql_select->limit(1);

        return $this->sql_select->query()->fetchColumn(0);
    }

    /**
     * @return array
     */
    public function fetchRow()
    {
        $this->sql_select->limit(1);

        return $this->sql_select->query()->fetch();
    }

    /**
     * Select only a specific group
     *
     * @param int $groupId Gems group id
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forGroupId($groupId) {

        // $this->andSurveys(array());

        $this->sql_select->where('gsu_id_primary_group = ?', $groupId);

        return $this;
    }

    /**
     * Select the token before the current token
     *
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forNextTokenId($tokenId) {
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
     * @param string|array $fields
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forPreviousTokenId($tokenId) {
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
     * @param string $respondentId
     * @param string $organizationId Optional
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forRespondent($respondentId, $organizationId = null) {
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
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forRespondentTrack($respTrackId) {
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
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forRound($roundId) {

        $this->sql_select->where('gto_id_round = ?', $roundId);

        return $this;
    }

    /**
     * Select only a specific surveyId
     *
     * @param string $surveyId
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forSurveyId($surveyId) {
        $this->sql_select->where('gto_id_survey = ?', $surveyId);

        return $this;
    }

    /**
     * Select only a specific tokenId
     *
     * @param string $tokenId
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forTokenId($tokenId) {
        $this->sql_select->where('gto_id_token = ?', $tokenId);

        return $this;
    }

    /**
     * For adding generic where statements
     *
     * @param string $cond SQL Where condition.
     * @param mixed $bind optional bind values
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function forWhere($cond, $bind = null)
    {
        $this->sql_select->where($cond, $bind);

        return $this;
    }

    /**
     * Get the constructed select statement
     *
     * @return Zend_Db_Select
     */
    public function getSelect() {
        return $this->sql_select;
    }

    /**
     * Select only active tokens
     *
     * Active is token already in surveyor and completiondate is null
     *
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function onlyActive() {

        $this->sql_select
                ->where('gto_in_source = ?', 1)
                ->where('gto_completion_time IS NULL');

        return $this;
    }

    /**
     * Select tokens with receptioncodes with the success status 1
     *
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function onlySucces()
    {
        $this->sql_select->where('grc_success = 1');

        return $this;
    }

    /**
     * Select only valid tokens
     *
     * Not answered, and valid_from/to in right range
     *
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function onlyValid() {

        $this->sql_select->where('gto_completion_time IS NULL')
                ->where('gto_valid_from <= CURRENT_TIMESTAMP')
                ->where('(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)');

        return $this;
    }

    /**
     *
     * @param mixed $spec The column(s) and direction to order by.
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function order($spec)
    {
        $this->sql_select->order($spec);

        return $this;
    }

    /**
     * Do not select the current token
     *
     * @param string $tokenId
     * @return Gems_Tracker_Token_TokenSelect
     */
    public function withoutToken($tokenId) {

        $this->sql_select->where('gto_id_token != ?', $tokenId);

        return $this;
    }

}