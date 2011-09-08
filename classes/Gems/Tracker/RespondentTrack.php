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
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentTrack.php 458 2011-08-31 14:15:10Z mjong $
 */

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_RespondentTrack extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var array of round_id => Gems_Tracker_Token
     */
    protected $_activeTokens = array();

    /**
     * @var Gems_Tracker_Token
     */
    protected $_checkStart;

    /**
     *
     * @var Gems_Tracker_Token
     */
    protected $_firstToken;

    /**
     *
     * @var array The gems__respondent2track data
     */
    protected $_respTrackData;

    /**
     *
     * @var int The gems__respondent2track id
     */
    protected $_respTrackId;

    /**
     *
     * @var array of Gems_Tracker_Token
     */
    protected $_tokens;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @param mixed $respTracksData Track Id or array containing reps2track record
     */
    public function __construct($respTracksData)
    {
        if (is_array($respTracksData)) {
            $this->_respTrackData = $respTracksData;
            $this->_respTrackId   = $respTracksData['gr2t_id_respondent_track'];
        } else {
            $this->_respTrackId = $respTracksData;
        }
    }

    /**
     * Makes sure the receptioncode data is part of the $this->_respTrackData
     *
     * @param boolean $reload Optional parameter to force reload.
     */
    private function _ensureReceptionCode($reload = false)
    {
        if ($reload || (! isset($this->_respTrackData['grc_success']))) {
            $sql  = "SELECT * FROM gems__reception_codes WHERE grc_id_reception_code = ?";
            $code = $this->_respTrackData['gr2t_reception_code'];

            if ($row = $this->db->fetchRow($sql, $code)) {
                $this->_respTrackData = $row + $this->_respTrackData;
            } else {
                $trackId = $this->_respTrackId;
                throw new Gems_Exception("Reception code $code is missing for track $trackId.");
            }
        }
    }

    /**
     * Makes sure the respondent data is part of the $this->_respTrackData
     */
    private function _ensureRespondentData()
    {
        if (! isset($this->_respTrackData['grs_id_user'], $this->_respTrackData['gr2o_id_user'], $this->_respTrackData['gco_code'])) {
            $sql = "SELECT *
                FROM gems__respondents INNER JOIN
                    gems__respondent2org ON grs_id_user = gr2o_id_user INNER JOIN
                    gems__consents ON gr2o_consent = gco_description
                WHERE gr2o_id_user = ? AND gr2o_id_organization = ? LIMIT 1";

            $respId = $this->_respTrackData['gr2t_id_user'];
            $orgId  = $this->_respTrackData['gr2t_id_organization'];

            if ($row = $this->db->fetchRow($sql, array($respId, $orgId))) {
                $this->_respTrackData = $this->_respTrackData + $row;
            } else {
                $trackId = $this->_respTrackId;
                throw new Gems_Exception("Respondent data missing for track $trackId.");
            }
        }
    }

    private function _updateTrack(array $values, $userId)
    {
        if ($this->tracker->filterChangesOnly($this->_respTrackData, $values)) {
            $where = $this->db->quoteInto('gr2t_id_respondent_track = ?', $this->_respTrackId);

            if (Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_respTrackData[$key] . ' => ' . $val . "\n";
                }
                MUtil_Echo::r($echo, 'Updated values for ' . $this->_respTrackId);
            }

            if (! isset($values['gr2t_changed'])) {
                $values['gr2t_changed'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');
            }
            if (! isset($values['gr2t_changed_by'])) {
                $values['gr2t_changed_by'] = $userId;
            }

            $this->_respTrackData = $values + $this->_respTrackData;
            // return 1;
            return $this->db->update('gems__respondent2track', $values, $where);

        } else {
            return 0;
        }
    }

    /**
     * Add a one-off survey to the existing track.
     *
     * @param type $surveyId    the gsu_id of the survey to add
     * @param type $surveyData
     * @return Gems_Tracker_Token
     */
    public function addSurveyToTrack($surveyId, $surveyData, $userId) {
        if ('T' == $this->getTrackEngine()->getTrackType()) {
            //Do something to get a token and add it
            $tokenLibrary = $this->tracker->getTokenLibrary();

            //Now make sure the data to add is correct:
            $surveyData['gto_id_respondent_track']=$this->_respTrackId;
            $surveyData['gto_id_organization']=$this->_respTrackData['gr2t_id_organization'];
            $surveyData['gto_id_track']=$this->_respTrackData['gr2t_id_track'];
            $surveyData['gto_id_respondent']=$this->_respTrackData['gr2t_id_user'];
            $surveyData['gto_id_survey']=$surveyId;

            $tokenId = $tokenLibrary->createToken($surveyData, $userId);

            //Now refresh the track to include the survey we just added (easiest way as order may change)
            $this->getTokens(true);
        } else {
            throw new Gems_Exception_Coding('Engine ' . $this->getTrackEngine()->getName() . ' can not add surveys.');
        }

        return $this->_tokens[$tokenId];
    }

    /**
     * Set menu parameters from this token
     *
     * @param Gems_Menu_ParameterSource $source
     * @return Gems_Tracker_RespondentTrack (continuation pattern)
     */
    public function applyToMenuSource(Gems_Menu_ParameterSource $source)
    {
        $source->setRespondentTrackId($this->_respTrackId);
        $source->setPatient($this->getPatientNumber(), $this->getOrganizationId());
        $source->setTrackId($this->getTrackId());
        $source->setTrackType($this->getTrackEngine()->getTrackType());
        $source->offsetSet('can_edit', $this->hasSuccesCode());

        return $this;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db && (! $this->_respTrackData)) {
            $this->refresh();
        }

        return (boolean) $this->_respTrackData;
    }

    /**
     * Check for the existence of all tokens and create them otherwise
     *
     * @param int $userId Id of the user who takes the action (for logging)
     * @param Gems_Tracker_ChangeTracker $changes Optional change tracker
     * @return Gems_Tracker_ChangeTracker detailed info on changes
     */
    public function checkRounds($userId, Gems_Tracker_ChangeTracker $changes = null)
    {
        $engine = $this->getTrackEngine();

        return $engine->checkRoundsFor($this, $userId, $changes);
    }

    /**
     * Check this respondent track for changes to the tokens
     *
     * @param int $userId Id of the user who takes the action (for logging)
     * @param Gems_Tracker_Token $fromToken Optional token to start from
     * @return int The number of tokens changed by this code
     */
    public function checkTrackTokens($userId, Gems_Tracker_Token $fromToken = null)
    {
        $sqlCount  = 'SELECT COUNT(*) AS count, COALESCE(SUM(CASE WHEN gto_completion_time IS NULL THEN 0 ELSE 1 END), 0) AS completed
            FROM gems__tokens
            JOIN gems__reception_codes ON gto_reception_code = grc_id_reception_code AND grc_success = 1
            WHERE gto_id_respondent_track = ?';

        if ($counts = $this->db->fetchRow($sqlCount, $this->_respTrackId)) {
            $values['gr2t_count']      = intval($counts['count']);
            $values['gr2t_completed']  = intval($counts['completed']);

            if ($values['gr2t_count'] == $values['gr2t_completed']) {
                $tokenSelect = $this->tracker->getTokenSelect(array('MAX(gto_completion_time)'));
                $tokenSelect->andReceptionCodes(array())
                        ->forRespondentTrack($this->_respTrackId)
                        ->onlySucces();

                $values['gr2t_end_date'] = $tokenSelect->fetchOne();
            } else {
                $values['gr2t_end_date'] = null;
            }

            $this->_updateTrack($values, $userId);
        }

        $engine = $this->getTrackEngine();

        if ($fromToken) {
            return $engine->checkTokensFrom($this, $fromToken, $userId);
        } elseif ($this->_checkStart) {
            return $engine->checkTokensFrom($this, $this->_checkStart, $userId);
        } else {
            return $engine->checkTokensFromStart($this, $userId);
        }
    }

    /**
     * Returns a token with a success reception code for this round or null
     *
     * @param type $roundId Gems round id
     * @param Gems_Tracker_Token $token
     * @return Gems_Tracker_Token
     */
    public function getActiveRoundToken($roundId, Gems_Tracker_Token $token = null)
    {
        if ((null !== $token) && $token->hasSuccesCode()) {
            // Cache the token
            //
            // WARNING: This may cause bugs for tracks where two tokens exists
            // with this roundId and a success reception code, but this does speed
            // this function witrh track engines where that should not occur.
            $this->_activeTokens[$token->getRoundId()] = $token;
        }

        // Use array_key_exists since there may not be a valid round
        if (! array_key_exists($roundId, $this->_activeTokens)) {
            $tokenSelect = $this->tracker->getTokenSelect();
            $tokenSelect->andReceptionCodes()
                    ->forRespondentTrack($this->_respTrackId)
                    ->forRound($roundId)
                    ->onlySucces();

            // MUtil_Echo::track($tokenSelect->__toString());

            if ($tokenData = $tokenSelect->fetchRow()) {
                $this->_activeTokens[$roundId] = $this->tracker->getToken($tokenData);
            } else {
                $this->_activeTokens[$roundId] = null;
            }
        }

        return $this->_activeTokens[$roundId];
    }

    /**
     *
     * @param string $fieldName
     * @return MUtil_Date
     */
    public function getDate($fieldName)
    {
        if (isset($this->_respTrackData[$fieldName])) {
            if (Zend_Date::isDate($this->_respTrackData[$fieldName], Gems_Tracker::DB_DATETIME_FORMAT)) {
                return new MUtil_Date($this->_respTrackData[$fieldName], Gems_Tracker::DB_DATETIME_FORMAT);
            }
            if (Zend_Date::isDate($this->_respTrackData[$fieldName], Gems_Tracker::DB_DATE_FORMAT)) {
                return new MUtil_Date($this->_respTrackData[$fieldName], Gems_Tracker::DB_DATE_FORMAT);
            }
            if (Gems_Tracker::$verbose)  {
                MUtil_Echo::r($this->_respTrackData[$fieldName], 'Missed track date value:');
            }
        }
    }

    /**
     *
     * @return array of snippet names for deleting the track
     */
    public function getDeleteSnippets()
    {
        return $this->getTrackEngine()->getTrackDeleteSnippetNames($this);
    }

    /**
     *
     * @return array of snippet names for editing this respondent track
     */
    public function getEditSnippets()
    {
        return $this->getTrackEngine()->getTrackEditSnippetNames($this);
    }

    /**
     * Returns the first token in this track
     *
     * @return Gems_Tracker_Token
     */
    public function getFirstToken()
    {
        if (! $this->_firstToken) {
            if ($this->_tokens) {
                $this->_firstToken = reset($this->_tokens);
            } else {
                //No cache yet, but we might need all tokens later
                $tokens = $this->getTokens();
                $this->_firstToken = reset($tokens);
            }
        }

        return $this->_firstToken;
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        if (! isset($this->_respTrackData['gr2o_patient_nr'])) {
            $this->_ensureRespondentData();
        }

        return $this->_respTrackData['gr2o_patient_nr'];
    }

    /**
     *
     * @return int The organization id
     */
    public function getOrganizationId()
    {
        return $this->_respTrackData['gr2t_id_organization'];
    }

    /**
     *
     * @return int The respondent id
     */
    public function getRespondentId()
    {
        return $this->_respTrackData['gr2t_id_user'];
    }

    /**
     *
     * @return int The respondent2track id
     */
    public function getRespondentTrackId()
    {
        return $this->_respTrackId;
    }

    /**
     * The start date of this track
     *
     * @return MUtil_Date
     */
    public function getStartDate()
    {
        if (isset($this->_respTrackData['gr2t_start_date'])) {
            return new MUtil_Date($this->_respTrackData['gr2t_start_date'], Gems_Tracker::DB_DATETIME_FORMAT);
        }
    }

    /**
     * Returns all the tokens in this track
     *
     * @return array of Gems_Tracker_Token
     */
    public function getTokens($refresh = false)
    {
        if (! $this->_tokens || true === $refresh) {
            if (true === $refresh) {
                unset($this->_tokens);
                unset($this->_activeTokens);
                unset($this->_firstToken);
            }
            $this->_tokens       = array();
            $this->_activeTokens = array();
            $tokenSelect = $this->tracker->getTokenSelect();
            $tokenSelect->andReceptionCodes()
                ->forRespondentTrack($this->_respTrackId);

            $tokenRows = $tokenSelect->fetchAll();
            $prevToken = null;
            foreach ($tokenRows as $tokenData) {

                $token = $this->tracker->getToken($tokenData);

                $this->_tokens[$token->getTokenId()] = $token;

                // While we are busy, set this
                if ($token->hasSuccesCode()) {
                    $this->_activeTokens[$token->getRoundId()] = $token;
                }

                // Link the tokens
                if ($prevToken) {
                    $prevToken->setNextToken($token);
                }
                $prevToken = $token;
            }
        }

        return $this->_tokens;
    }

    /**
     *
     * @return Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngine()
    {
        return $this->tracker->getTrackEngine($this->_respTrackData['gr2t_id_track']);
    }

    /**
     *
     * @return int The track id
     */
    public function getTrackId()
    {
        return $this->_respTrackData['gr2t_id_track'];
    }

    /**
     *
     * @param mixed $token
     * @param int $userId The current user
     * @return int The number of tokens changed by this event
     */
    public function handleRoundCompletion($token, $userId)
    {
        if (! $token instanceof Gems_Tracker_Token) {
            $token = $this->tracker->getToken($token);
        }
        // MUtil_Echo::track($token->getRawAnswers());

        // Store the current token as startpoint if it is the first changed token
        if ($this->_checkStart) {
            if ($this->_checkStart->getRoundId() > $token->getRoundId()) {
                // Replace current token
                $this->_checkStart = $token;
            }
        } else {
            $this->_checkStart = $token;
        }

        // Process any events
        if ($event = $this->getTrackEngine()->getRoundChangedEvent($token->getRoundId())) {
            return $event->processChangedRound($token, $this, $userId);
        }

        return 0;
    }

    /**
     *
     * @return boolean
     */
    public function hasSuccesCode()
    {
        if (! isset($this->_respTrackData['grc_success'])) {
            $this->_ensureReceptionCode();
        }

        return $this->_respTrackData['grc_success'];
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return Gems_Tracker_RespondentTrack (continuation pattern)
     */
    public function refresh(array $gemsData = null)
    {
        if (is_array($gemsData)) {
            $this->_respTrackData = $gemsData + $this->_respTrackData;
        } else {
            $sql  = "SELECT *
                        FROM gems__respondent2track INNER JOIN gems__reception_codes ON gr2t_reception_code = grc_id_reception_code
                        WHERE gr2t_id_respondent_track = ? LIMIT 1";

            $this->_respTrackData = $this->db->fetchRow($sql, $this->_respTrackId);
        }

        return $this;
    }

    /**
     * Set the reception code for this respondent track and make sure the
     * necessary cascade to the tokens and thus the source takes place.
     *
     * @param string $code The new reception code
     * @param string $comment Comment for tokens. False values leave value unchanged
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setReceptionCode($code, $comment, $userId)
    {
        $values['gr2t_reception_code'] = $code;

        $changed = $this->_updateTrack($values, $userId);

        if ($changed) {
            // Reload reception code values
            $this->_ensureReceptionCode(true);

            // Cascade to tokens
            if (! $this->hasSuccesCode()) {
                foreach ($this->getTokens() as $token) {
                    if ($token->hasSuccesCode()) {
                        $token->setReceptionCode($code, $comment, $userId);
                    }
                }
            }
        }
        return $changed;
    }
}
