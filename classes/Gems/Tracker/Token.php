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
 * @version    $Id$
 */

/**
 * Object class for checking and changing tokens.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Token extends Gems_Registry_TargetAbstract
{
    const COMPLETION_NOCHANGE = 0;
    const COMPLETION_DATACHANGE = 1;
    const COMPLETION_EVENTCHANGE = 2;

    /**
     *
     * @var array Can hold any data the source likes to store for the token
     */
    private $_cache = array();

    /**
     *
     * @var array The gems token data
     */
    protected $_gemsData = array();

    /**
     * Helper var for preventing infinite loops
     *
     * @var boolean
     */
    protected $_loopCheck = false;

    /**
     *
     * @var Gems_Tracker_Token
     */
    private $_nextToken = null;

    /**
     *
     * @var Gems_Tracker_Token
     */
    private $_previousToken = null;
    
    /**
     *
     * @var Gems_Tracker_Respondent
     */
    protected $_respondentObject = null;

    /**
     *
     * @var array The answers in raw format
     */
    private $_sourceDataRaw;

    /**
     *
     * @var string The id of the token
     */
    protected $_tokenId;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * True when the token does exist.
     *
     * @var boolean
     */
    public $exists = true;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var Gems_Tracker_RespondentTrack
     */
    protected $respTrack;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     * The size of the result field, calculated from meta data when null,
     * but can be set by project specific class to fixed value
     *
     * @var int The maximum character length of the result field
     */
    protected $resultFieldLength = null;

    /**
     * Cache for storing the calculation of the length
     *
     * @var int the character length of the result field
     */
    protected static $staticResultFieldLength = null;

    /**
     *
     * @var Gems_Tracker_Survey
     */
    protected $survey;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Zend_Translate
     */
    public $translate;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Creates the token object
     *
     * @param mixed $gemsTokenData Token Id or array containing token record
     */
    public function __construct($gemsTokenData)
    {
        if (is_array($gemsTokenData)) {
            $this->_gemsData = $gemsTokenData;
            $this->_tokenId  = $gemsTokenData['gto_id_token'];
        } else {
            $this->_tokenId  = $gemsTokenData;
        }
    }

    /**
     * Makes sure the receptioncode data is part of the $this->_gemsData
     *
     * @param boolean $reload Optional parameter to force reload or an array with the new values.
     */
    private function _ensureReceptionCode($reload = false)
    {
        if ($reload || (! isset($this->_gemsData['grc_success']))) {
            if (is_array($reload)) {
                $this->_gemsData = $reload + $this->_gemsData;
            } else {
                $sql  = "SELECT * FROM gems__reception_codes WHERE grc_id_reception_code = ?";
                $code = $this->_gemsData['gto_reception_code'];

                if ($row = $this->db->fetchRow($sql, $code)) {
                    $this->_gemsData = $row + $this->_gemsData;
                } else {
                    $token = $this->_tokenId;
                    throw new Gems_Exception("Reception code $code is missing for token $token.");
                }
            }
        }
    }

    /**
     * Makes sure the respondent data is part of the $this->_gemsData
     */
    protected function _ensureRespondentData()
    {
        if (! isset($this->_gemsData['grs_id_user'], $this->_gemsData['gr2o_id_user'], $this->_gemsData['gco_code'])) {
            $sql = "SELECT *
                FROM gems__respondents INNER JOIN
                    gems__respondent2org ON grs_id_user = gr2o_id_user INNER JOIN
                    gems__consents ON gr2o_consent = gco_description
                WHERE gr2o_id_user = ? AND gr2o_id_organization = ? LIMIT 1";

            $respId = $this->_gemsData['gto_id_respondent'];
            $orgId  = $this->_gemsData['gto_id_organization'];
            // MUtil_Echo::track($this->_gemsData);

            if ($row = $this->db->fetchRow($sql, array($respId, $orgId))) {
                $this->_gemsData = $this->_gemsData + $row;
            } else {
                $token = $this->_tokenId;
                throw new Gems_Exception("Respondent data missing for token $token.");
            }
        }
    }

    /**
     * The maximum length of the result field
     *
     * @return int
     */
    protected function _getResultFieldLength()
    {
        if (null !== $this->resultFieldLength) {
            return $this->resultFieldLength;
        }

        if (null !== self::$staticResultFieldLength) {
            $this->resultFieldLength = self::$staticResultFieldLength;
            return $this->resultFieldLength;
        }

        $model = new MUtil_Model_TableModel('gems__tokens');
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
    protected function _updateToken(array $values, $userId)
    {
        if ($this->tracker->filterChangesOnly($this->_gemsData, $values)) {

            if (Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_gemsData[$key] . ' => ' . $val . "\n";
                }
                MUtil_Echo::r($echo, 'Updated values for ' . $this->_tokenId);
            }

            if (! isset($values['gto_changed'])) {
                $values['gto_changed'] = new MUtil_Db_Expr_CurrentTimestamp();
            }
            if (! isset($values['gto_changed_by'])) {
                $values['gto_changed_by'] = $userId;
            }

            // Update values in this object
            $this->_gemsData = $values + (array) $this->_gemsData;

            // return 1;
            return $this->db->update('gems__tokens', $values, array('gto_id_token = ?' => $this->_tokenId));

        } else {
            return 0;
        }
    }

    /**
     * Set menu parameters from this token
     *
     * @param Gems_Menu_ParameterSource $source
     * @return Gems_Tracker_Token (continuation pattern)
     */
    public function applyToMenuSource(Gems_Menu_ParameterSource $source)
    {
        $source->setTokenId($this->_tokenId);
        if ($this->exists) {
            if (! isset($this->_gemsData['gr2o_patient_nr'])) {
                $this->_ensureRespondentData();
            }
            if (! isset($this->_gemsData['grc_success'])) {
                $this->_ensureReceptionCode();
            }

            $source->setTokenId($this->_tokenId);
            $source->setPatient($this->_gemsData['gr2o_patient_nr'], $this->_gemsData['gto_id_organization']);
            $source->setRespondentTrackId($this->_gemsData['gto_id_respondent_track']);
            $source->setTrackId($this->_gemsData['gto_id_track']);
            $source->setTrackType($this->getTrackEngine()->getTrackType());

            $source->offsetSet('gsu_id_survey', $this->_gemsData['gto_id_survey']);
            $source->offsetSet('grc_success', $this->_gemsData['grc_success']);
            $source->offsetSet('is_completed', $this->_gemsData['gto_completion_time'] ? 1 : 0);

            if ($this->_gemsData['grc_success'] &&
                    (! $this->_gemsData['gto_completion_time']) &&
                    ($validFrom = $this->getValidFrom())) {

                $validUntil = $this->getValidUntil();
                $today = new MUtil_Date();

                $can_be_taken = $validFrom->isEarlier($today) && ($validUntil ? $validUntil->isLater($today) : true);
            } else {
                $can_be_taken = false;
            }
            $source->offsetSet('can_be_taken', $can_be_taken);
        }
        return $this;
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
    public function cacheGet($key, $defaultValue = null) {
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
     * @return boolean
     */
    public function cacheHas($key) {
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
    public function cacheReset($key = null) {
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
    public function cacheSet($key, $value) {
        $this->_cache[$key] = $value;
    }

    /**
     * Returns the full url Gems should forward to after survey completion.
     *
     * This fix allows multiple sites with multiple url's to share a single
     * installation.
     *
     * @return string
     */
    protected function calculateReturnUrl()
    {
        $currentUri = $this->util->getCurrentURI();

        /*
        // Referrer would be powerful when someone is usng multiple windows, but
        // the loop does not always provide a correct referrer.
        $referrer   = $_SERVER["HTTP_REFERER"];

        // If a referrer was specified and that referral is from the current site, then use it
        // as it is more dependable when the user has multiple windows open on the application.
        if ($referrer && (0 == strncasecmp($referrer, $currentUri, strlen($currentUri)))) {
            return $referrer;
            // MUtil_Echo::track($referrer);
        } // */

        // Use the survey return if available.
        $surveyReturn = $this->loader->getCurrentUser()->getSurveyReturn();
        if ($surveyReturn) {
            // Do not show the base url as it is in $currentUri
            $surveyReturn['NoBase'] = true;

            // Add route reset to prevet the current parameters to be
            // added to the url.
            $surveyReturn['RouteReset'] = true;

            // MUtil_Echo::track($currentUri, MUtil_Html::urlString($surveyReturn));
            return $currentUri . MUtil_Html::urlString($surveyReturn);
        }

        // Ultimate backup solution for return
        return $currentUri . '/ask/forward/' . MUtil_Model::REQUEST_ID . '/' . urlencode($this->getTokenId());
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db && (! $this->_gemsData)) {
            $this->refresh();
        }

        return $this->exists;
    }

    /**
     * Checks whether the survey for this token was completed and processes the result
     *
     * @param int $userId The id of the gems user
     * @return int self::COMPLETION_NOCHANGE || (self::COMPLETION_DATACHANGE | self::COMPLETION_EVENTCHANGE)
     */
    public function checkTokenCompletion($userId)
    {
        $result = self::COMPLETION_NOCHANGE;

        // Some defaults
        $values['gto_completion_time'] = null;
        $values['gto_duration_in_sec'] = null;
        $values['gto_result']          = null;

        if ($this->inSource()) {
            $survey = $this->getSurvey();

            $values['gto_in_source'] = 1;

            $startTime = $survey->getStartTime($this);
            if ($startTime instanceof MUtil_Date) {
                // Value from source overrules any set date time
                $values['gto_start_time'] = $startTime->toString(Gems_Tracker::DB_DATETIME_FORMAT);

            } else {
                // Otherwise use the time kept by Gems.
                $startTime = $this->getDateTime('gto_start_time');

                //What if we have older tokens... where no gto_start_time was set??
                if (is_null($startTime)) {
                    $startTime = new MUtil_Date();
                }

                // No need to set $values['gto_start_time'], it does not change
            }

            // If there is a start date there can be a completion date
            if ($startTime instanceof MUtil_Date) {
                if ($survey->isCompleted($this)) {
                    $complTime         = $survey->getCompletionTime($this);
                    $setCompletionTime = true;

                    if (! $complTime instanceof MUtil_Date) {
                        // Token is completed but the source cannot tell the time
                        //
                        // Try to see it was stored already
                        $complTime = $this->getDateTime('gto_completion_time');

                        if ($complTime instanceof MUtil_Date) {
                            // Again no need to change a time that did not change
                            unset($values['gto_completion_time']);
                            $setCompletionTime = false;
                        } else {
                            // Well anyhow it was completed now or earlier. Get the current moment.
                            $complTime = new MUtil_Date();
                        }
                    }

                    //Set completion time for completion event
                    if ($setCompletionTime) {
                        $values['gto_completion_time'] = $complTime->toString(Gems_Tracker::DB_DATETIME_FORMAT);
                        //Save the old value
                        $originalCompletionTime = $this->_gemsData['gto_completion_time'];
                        $this->_gemsData['gto_completion_time'] = $values['gto_completion_time'];
                    }

                    // Process any Gems side survey dependent changes
                    if ($changed = $this->handleAfterCompletion()) {

                        // Communicate change
                        $result += self::COMPLETION_EVENTCHANGE;

                        if (Gems_Tracker::$verbose) {
                            MUtil_Echo::r($changed, 'Source values for ' . $this->_tokenId . ' changed by event.');
                        }
                    }

                    if ($setCompletionTime) {
                        //Reset to old value, so changes will be picked up
                        $this->_gemsData['gto_completion_time'] = $originalCompletionTime;
                    }
                    $values['gto_duration_in_sec'] = max($complTime->diffSeconds($startTime), 0);

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

                    if ($this->project->hasResponseDatabase()) {
                        $this->toResponseDatabase($userId);
                    }
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
     * @return string The new token
     */
    public function createReplacement($newComment, $userId)
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
        $values['gto_valid_until']         = $this->_gemsData['gto_valid_until'];
        $values['gto_mail_sent_date']      = $this->_gemsData['gto_mail_sent_date'];
        $values['gto_next_mail_date']      = $this->_gemsData['gto_next_mail_date'];
        $values['gto_comment']             = $newComment;

        $tokenId = $this->tracker->createToken($values, $userId);

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
    public function getAllUnansweredTokens($where = '')
    {
        $select = $this->tracker->getTokenSelect();
        $select->andReceptionCodes()
                ->andRespondentTracks()
                ->andRounds()
                ->andSurveys()
                ->andTracks()
                ->forGroupId($this->getSurvey()->getGroupId())
                ->forRespondent($this->getRespondentId(), $this->getOrganizationId())
                ->onlySucces()
                ->order('gtr_track_type')
                ->order('gtr_track_name')
                ->order('gr2t_track_info')
                ->order('gto_valid_until')
                ->order('gto_valid_from');

        if (!empty($where)) {
            $select->forWhere($where);
        }

        return $select->fetchAll();        
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * @param string $fieldName Name of answer field
     * @return MUtil_Date date time or null
     */
    public function getAnswerDateTime($fieldName)
    {
        $survey = $this->getSurvey();
        return $survey->getAnswerDateTime($fieldName, $this);
    }

    /**
     * Returns a snippet name that can be used to display the answers to this token.
     *
     * @return string
     */
    public function getAnswerSnippetNames()
    {
        if ($this->exists) {
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
        } else {
            return 'TokenNotFoundSnippet';
        }
    }

    /**
     * Returns the staff or respondent id of the person
     * who last changed this token.
     *
     * @return int
     */
    public function getChangedBy()
    {
        return $this->_gemsData['gto_changed_by'];
    }

    /**
     *
     * @return MUtil_Date Completion time as a date or null
     */
    public function getCompletionTime()
    {
        if (isset($this->_gemsData['gto_completion_time']) && $this->_gemsData['gto_completion_time']) {
            if ($this->_gemsData['gto_completion_time'] instanceof MUtil_Date) {
                return $this->_gemsData['gto_completion_time'];
            }
            return new MUtil_Date($this->_gemsData['gto_completion_time'], Gems_Tracker::DB_DATETIME_FORMAT);
        }
    }

    /**
     *
     * @return string
     */
    public function getConsentCode()
    {
        if ($this->hasSuccesCode()) {
            if (! isset($this->_gemsData['gco_code'])) {
                $this->_ensureRespondentData();
            }

            return $this->_gemsData['gco_code'];
        } else {
            return $this->util->getConsentRejected();
        }
    }

    /**
     * Returns the staff or respondent id of the person
     * who created this token.
     *
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->_gemsData['gto_created_by'];
    }

    /**
     *
     * @param string $fieldName
     * @return MUtil_Date
     */
    public function getDateTime($fieldName)
    {
        if (isset($this->_gemsData[$fieldName])) {
            if ($this->_gemsData[$fieldName] instanceof MUtil_Date) {
                return $this->_gemsData[$fieldName];
            }

            if (Zend_Date::isDate($this->_gemsData[$fieldName], Gems_Tracker::DB_DATETIME_FORMAT)) {
                return new MUtil_Date($this->_gemsData[$fieldName], Gems_Tracker::DB_DATETIME_FORMAT);
            }
            if (Zend_Date::isDate($this->_gemsData[$fieldName], Gems_Tracker::DB_DATE_FORMAT)) {
                return new MUtil_Date($this->_gemsData[$fieldName], Gems_Tracker::DB_DATE_FORMAT);
            }
            if (Gems_Tracker::$verbose)  {
                MUtil_Echo::r($this->_gemsData[$fieldName], 'Missed token date value:');
            }
        }
    }

    /**
     * Returns an array of snippet names that can be used to delete this token.
     *
     * @return array of strings
     */
    public function getDeleteSnippetNames()
    {
        if ($this->exists) {
            return $this->getTrackEngine()->getTokenDeleteSnippetNames($this);
        } else {
            return 'TokenNotFoundSnippet';
        }
    }

    /**
     * Returns an array of snippet names that can be used to edit this token.
     *
     * @return array of strings
     */
    public function getEditSnippetNames()
    {
        if ($this->exists) {
            return $this->getTrackEngine()->getTokenEditSnippetNames($this);
        } else {
            return 'TokenNotFoundSnippet';
        }
    }

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return Gems_Tracker_Model_StandardTokenModel
     */
    public function getModel()
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
     * @return Gems_Tracker_Token
     */
    public function getNextToken()
    {
        if (null === $this->_nextToken) {
            $tokenSelect = $this->tracker->getTokenSelect();
            $tokenSelect
                    ->andReceptionCodes()
                    ->forPreviousTokenId($this->_tokenId);

            if ($tokenData = $tokenSelect->fetchRow()) {
                $this->_nextToken = $this->tracker->getToken($tokenData);
                $this->_nextToken->_previousToken = $this;
            } else {
                $this->_nextToken = false;
            }
        }

        return $this->_nextToken;
    }

    /**
     * Returns the next unanswered token for the person answering this token
     *
     * @return Gems_Tracker_Token
     */
    public function getNextUnansweredToken()
    {
        $tokenSelect = $this->tracker->getTokenSelect();
        $tokenSelect
                ->andReceptionCodes()
                // ->andRespondents()
                // ->andRespondentOrganizations()
                // ->andConsents
                ->andSurveys(array())
                ->forRespondent($this->getRespondentId())
                ->forGroupId($this->getSurvey()->getGroupId())
                ->onlySucces()
                ->onlyValid()
                ->order(array('gto_valid_from', 'gto_round_order'));

        if ($tokenData = $tokenSelect->fetchRow()) {
            return $this->tracker->getToken($tokenData);
        }
    }

    /**
     *
     * @return Gems_User_Organization
     */
    public function getOrganization()
    {
        return $this->loader->getOrganization($this->getOrganizationId());
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId()
    {
        return $this->_gemsData['gto_id_organization'];
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        if (! isset($this->_gemsData['gr2o_patient_nr'])) {
            $this->_ensureRespondentData();
        }

        return $this->_gemsData['gr2o_patient_nr'];
    }

    /**
     * Returns the previous token that has succes in this track
     *
     * @return Gems_Tracker_Token
     */
    public function getPreviousSuccessToken()
    {
        $prev = $this->getPreviousToken();

        while ($prev && (! $prev->hasSuccesCode())) {
            $prev = $prev->getPreviousToken();
        }

        return $prev;
    }

    /**
     * Returns the previous token in this track
     *
     * @return Gems_Tracker_Token
     */
    public function getPreviousToken()
    {
        if (null === $this->_previousToken) {
            $tokenSelect = $this->tracker->getTokenSelect();
            $tokenSelect
                    ->andReceptionCodes()
                    ->forNextTokenId($this->_tokenId);

            if ($tokenData = $tokenSelect->fetchRow()) {
                $this->_previousToken = $this->tracker->getToken($tokenData);
                $this->_previousToken->_nextToken = $this;
            } else {
                $this->_previousToken = false;
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
    public function getRawAnswers()
    {
        if (! is_array($this->_sourceDataRaw)) {
            $this->_sourceDataRaw = $this->getSurvey()->getRawTokenAnswerRow($this->_tokenId);
        }
        return $this->_sourceDataRaw;
    }

    /**
     * Return the Gems_Util_ReceptionCode object
     *
     * @return Gems_Util_ReceptionCode reception code
     */
    public function getReceptionCode()
    {
        return $this->util->getReceptionCode($this->_gemsData['gto_reception_code']);
    }
    
    /**
     * Get the respondent linked to this token
     *
     * @return Gems_Tracker_Respondent
     */
    public function getRespondent()
    {
        $patientNumber = $this->getPatientNumber();
        $organizationId = $this->getOrganizationId();
        
        if (    !($this->_respondentObject instanceof Gems_Tracker_Respondent) 
                || $this->_respondentObject->getPatientId() !== $patientNumber
                || $this->_respondentObject->getOrganizationId() !== $organizationId) {
            $this->_respondentObject = $this->loader->getRespondent($patientNumber, $organizationId);
        }
        
        return $this->_respondentObject;
    }

    /**
     * Returns the gender as a letter code
     *
     * @return string
     */
    public function getRespondentGender()
    {
        return $this->getRespondent()->getGender();
    }

    /**
     * Returns the gender for use as part of a sentence, e.g. Dear Mr/Mrs
     *
     * @return string
     */
    public function getRespondentGenderHello()
    {
        $greetings = $this->util->getTranslated()->getGenderGreeting();
        $gender    = $this->getRespondentGender();

        if (isset($greetings[$gender])) {
            return $greetings[$gender];
        }
    }

    /**
     *
     * @return int
     */
    public function getRespondentId()
    {
        if (array_key_exists('gto_id_respondent', $this->_gemsData)) {
            return $this->_gemsData['gto_id_respondent'];
        } else {
            throw new Gems_Exception(sprintf('Token not loaded correctly', $this->getTokenId()));
        }
    }

    /**
     * Return the default language for the respondent
     *
     * @return string Two letter language code
     */
    public function getRespondentLanguage()
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
    public function getRespondentLastName()
    {
        return $this->getRespondent()->getLastName();
    }

    /**
     *
     * @return string
     */
    public function getRespondentName()
    {
        $this->getRespondent()->getName();
    }

    /**
     *
     * @return Gems_Tracker_RespondentTrack
     */
    public function getRespondentTrack()
    {
        if (! $this->respTrack) {
            $this->respTrack = $this->tracker->getRespondentTrack($this->_gemsData['gto_id_respondent_track']);
        }

        return $this->respTrack;
    }

    /**
     *
     * @return int
     */
    public function getRespondentTrackId()
    {
        return $this->_gemsData['gto_id_respondent_track'];
    }

    /**
     * The full return url for a redirect
     *
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->_gemsData['gto_return_url'];
    }

    /**
     *
     * @return string Round description
     */
    public function getRoundDescription()
    {
        return $this->_gemsData['gto_round_description'];
    }

    /**
     *
     * @return int round id
     */
    public function getRoundId()
    {
        return $this->_gemsData['gto_id_round'];
    }

    /**
     *
     * @return int round order
     */
    public function getRoundOrder()
    {
        return $this->_gemsData['gto_round_order'];
    }

    /**
     *
     * @return string Last mail sent date
     */
    public function getMailSentDate()
    {
        return $this->_gemsData['gto_mail_sent_date'];
    }

    /**
     * Returns a snippet name that can be used to display this token.
     *
     * @return string
     */
    public function getShowSnippetNames()
    {
        if ($this->exists) {
            return $this->getTrackEngine()->getTokenShowSnippetNames($this);
        } else {
            return 'TokenNotFoundSnippet';
        }
    }

    /**
     * Returns a string that tells if the token is open, completed or any other
     * status you might like. This will not be interpreted by the tracker it is
     * for display purposes only
     *
     * @return string Token status description
     */
    public function getStatus()
    {
        $today  = new Zend_Date();

        if ($this->isCompleted()) {
            $status = $this->translate->getAdapter()->_('Completed');
        } else {
            $validFrom  = $this->getValidFrom();
            $validUntil = $this->getValidUntil();

            if (! empty($validUntil) && $validUntil->isEarlier($today)) {
                $status = $this->translate->getAdapter()->_('Missed');
            } elseif (! empty($validFrom) && $validFrom->isLater($today)) {
                $status = $this->translate->getAdapter()->_('Future');
            } elseif (empty($validFrom) && empty($validUntil)) {
                $status = $this->translate->getAdapter()->_('Future');
            } else {
                $status = $this->translate->getAdapter()->_('Open');
            }
        }

        return $status;
    }

    /**
     *
     * @return Gems_Tracker_Survey
     */
    public function getSurvey()
    {
        if (! $this->survey) {
            $this->survey = $this->tracker->getSurvey($this->_gemsData['gto_id_survey']);
        }

        return $this->survey;
    }

    /**
     *
     * @return int Gems survey id
     */
    public function getSurveyId()
    {
        return $this->_gemsData['gto_id_survey'];
    }

    /**
     *
     * @param string $language (ISO) language string
     * @return MUtil_Model_ModelAbstract
     */
    public function getSurveyAnswerModel($language)
    {
        $survey = $this->getSurvey();
        return $survey->getAnswerModel($language);
    }

    /**
     *
     * @return string Name of the survey
     */
    public function getSurveyName()
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
    public function getTokenCountUnanswered()
    {
        $tokenSelect = $this->tracker->getTokenSelect(new Zend_Db_Expr('COUNT(*)'));
        $tokenSelect
                ->andReceptionCodes(array())
                ->andSurveys(array())
                ->forRespondent($this->getRespondentId())
                ->forGroupId($this->getSurvey()->getGroupId())
                ->onlySucces()
                ->onlyValid()
                ->withoutToken($this->_tokenId);

        return $tokenSelect->fetchOne();
    }

    /**
     *
     * @return string token
     */
    public function getTokenId()
    {
        return $this->_tokenId;
    }

    /**
     * Get the track engine that generated this token
     *
     * @return Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngine()
    {
        if ($this->exists) {
            return $this->tracker->getTrackEngine($this->_gemsData['gto_id_track']);
        }

        throw new Gems_Exception_Coding('Coding error: requesting track engine for non existing token.');
    }

    /**
     *
     * @return int gems_tracks track id
     */
    public function getTrackId()
    {
        return $this->_gemsData['gto_id_track'];
    }

    public function getTrackName()
    {
        $trackData = $this->db->fetchRow("SELECT gtr_track_name FROM gems__tracks WHERE gtr_id_track = ?", $this->getTrackId());
        return $trackData['gtr_track_name'];
    }

    /**
     *
     * @param string $language The language currently used by the user
     * @param int $userId The id of the gems user
     * @throws Gems_Tracker_Source_SurveyNotFoundException
     */
    public function getUrl($language, $userId)
    {
        $survey = $this->getSurvey();

        $survey->copyTokenToSource($this, $language);

        if (! $this->_gemsData['gto_in_source']) {
            $values['gto_start_time'] = new MUtil_Db_Expr_CurrentTimestamp();
            $values['gto_in_source']  = 1;
        }
        $values['gto_by']         = $userId;
        $values['gto_return_url'] = $this->calculateReturnUrl();

        // MUtil_Echo::track($values);

        $this->_updateToken($values, $userId);

        $this->handleBeforeAnswering();

        return $survey->getTokenUrl($this, $language);
    }

    /**
     *
     * @return MUtil_Date Valid from as a date or null
     */
    public function getValidFrom()
    {
        if (isset($this->_gemsData['gto_valid_from']) && $this->_gemsData['gto_valid_from']) {
            if ($this->_gemsData['gto_valid_from'] instanceof MUtil_Date) {
                return $this->_gemsData['gto_valid_from'];
            }
            return new MUtil_Date($this->_gemsData['gto_valid_from'], Gems_Tracker::DB_DATETIME_FORMAT);
        }
    }

    /**
     *
     * @return MUtil_Date Valid until as a date or null
     */
    public function getValidUntil()
    {
        if (isset($this->_gemsData['gto_valid_until']) && $this->_gemsData['gto_valid_until']) {
            if ($this->_gemsData['gto_valid_until'] instanceof MUtil_Date) {
                return $this->_gemsData['gto_valid_until'];
            }
            return new MUtil_Date($this->_gemsData['gto_valid_until'], Gems_Tracker::DB_DATETIME_FORMAT);
        }
    }

    /**
     * Returns true when the answers are loaded.
     *
     * There may not be any answers, but the attemt to retrieve them was made.
     *
     * @return boolean
     */
    public function hasAnswersLoaded()
    {
        return (boolean) $this->_sourceDataRaw;
    }

    /**
     *
     * @return boolean
     */
    public function hasResult()
    {
        return $this->_gemsData['gto_result'];
    }

    /**
     * Survey dependent calculations / answer changes that must occur after a survey is completed
     *
     * @param type $tokenId The tokend the answers are for
     * @param array $tokenAnswers Array with answers. May be changed in process
     * @return array The changed values
     */
    public function handleAfterCompletion()
    {
        $survey = $this->getSurvey();

        if ($event = $survey->getSurveyCompletedEvent()) {

            if ($changed = $event->processTokenData($this)) {

                $this->setRawAnswers($changed);

                return $changed;
            }
        }
    }

    /**
     * Survey dependent calculations / answer changes that must occur after a survey is completed
     *
     * @param type $tokenId The tokend the answers are for
     * @param array $tokenAnswers Array with answers. May be changed in process
     * @return array The changed values
     */
    public function handleBeforeAnswering()
    {
        $survey = $this->getSurvey();

        if ($event = $survey->getSurveyBeforeAnsweringEvent()) {

            if ($changed = $event->processTokenInsertion($this)) {

                $source = $survey->getSource();
                $this->setRawAnswers($changed);

                if (Gems_Tracker::$verbose) {
                    MUtil_Echo::r($changed, 'Source values for ' . $this->_tokenId . ' changed by event.');
                }

                return $changed;
            }
        }
    }

    /**
     *
     * @deprecated Use the ReceptionCode->hasRedoCode
     * @return boolean
     */
    public function hasRedoCode()
    {
        return $this->getReceptionCode()->hasRedoCode();
        /*if (! isset($this->_gemsData['grc_redo_survey'])) {
            $this->_ensureReceptionCode();
        }

        return (boolean) $this->_gemsData['grc_redo_survey'];
         */
    }

    /**
     * True if the reception code is a redo survey copy.
     *
     * @deprecated Use the ReceptionCode->hasRedoCopyCode
     * @return boolean
     */
    public function hasRedoCopyCode()
    {
        return $this->getReceptionCode()->hasRedoCopyCode();
        /*
        if (! isset($this->_gemsData['grc_redo_survey'])) {
            $this->_ensureReceptionCode();
        }

        return Gems_Util_ReceptionCodeLibrary::REDO_COPY == $this->_gemsData['grc_redo_survey'];
         */
    }

    /**
     *
     * @deprecated Use the ReceptionCode->isSuccess
     * @return boolean
     */
    public function hasSuccesCode()
    {
        return $this->getReceptionCode()->isSuccess();
        /*
        if (! isset($this->_gemsData['grc_success'])) {
            $this->_ensureReceptionCode();
        }

        return $this->_gemsData['grc_success'];
         */
    }

    /**
     * True is this token was exported to the source.
     *
     * @return boolean
     */
    public function inSource()
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
     * @return boolean
     */
    public function isCompleted()
    {
        return isset($this->_gemsData['gto_completion_time']) && $this->_gemsData['gto_completion_time'];
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return Gems_Tracker_Token (continuation pattern)
     */
    public function refresh(array $gemsData = null)
    {
        if (is_array($gemsData)) {
            $this->_gemsData = $gemsData + $this->_gemsData;
        } else {
            $tokenSelect = $this->tracker->getTokenSelect();

            $tokenSelect
                    ->andReceptionCodes()
                    ->andRespondents()
                    ->andRespondentOrganizations()
                    ->andConsents()
                    ->forTokenId($this->_tokenId);

            $this->_gemsData = $tokenSelect->fetchRow();
            if (false == $this->_gemsData) {
                // on failure, reset to empty array
                $this->_gemsData = array();
            }
        }
        $this->exists = isset($this->_gemsData['gto_id_token']);

        return $this;
    }

    /**
     * Sets the next token in this track
     *
     * @param Gems_Tracker_Token $token
     * @return Gems_Tracker_Token (continuation pattern)
     */
    public function setNextToken(Gems_Tracker_Token $token)
    {
        $this->_nextToken = $token;

        $token->_previousToken = $this;

        return $this;
    }

    /**
     * Sets answers for this token to the values defined in the $answers array. Also handles updating the
     * internal answercache if present
     *
     * @param array $answers
     */
    public function setRawAnswers($answers) {
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
     * @param string $code The new (non-success) reception code or a Gems_Util_ReceptionCode object
     * @param string $comment Comment False values leave value unchanged
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setReceptionCode($code, $comment, $userId)
    {
        // Make sure it is a Gems_Util_ReceptionCode object
        if (! $code instanceof Gems_Util_ReceptionCode) {
            $code = $this->util->getReceptionCode($code);
        }

        $values['gto_reception_code'] = $code->getCode();
        if ($comment) {
            $values['gto_comment'] = $comment;
        }
        // MUtil_Echo::track($values);

        $changed = $this->_updateToken($values, $userId);

        if ($changed) {
            // Reload reception code values
            $this->_ensureReceptionCode($code->getAllData());

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
     *
     * @param mixed $validFrom Zend_Date or string
     * @param mixed $validUntil null, Zend_Date or string. False values leave values unchangeds
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setValidFrom($validFrom, $validUntil, $userId)
    {
        if ($validFrom instanceof Zend_Date) {
            $validFrom = $validFrom->toString(Gems_Tracker::DB_DATETIME_FORMAT);
        }
        if ($validUntil instanceof Zend_Date) {
            $validUntil = $validUntil->toString(Gems_Tracker::DB_DATETIME_FORMAT);
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
    protected function toResponseDatabase($userId)
    {
        $db = $this->project->getResponseDatabase();

        $rValues = array(
            'gdr_id_token'   => $this->_tokenId,
            'gdr_changed'    => new MUtil_Db_Expr_CurrentTimestamp(),
            'gdr_changed_by' => $userId,
            'gdr_created'    => new MUtil_Db_Expr_CurrentTimestamp(),
            'gdr_created_by' => $userId,
        );
        $responses = $this->getRawAnswers();
        unset($responses['token'], $responses['id'], $responses['lastpage'],
                $responses['startlanguage'], $responses['submitdate'], $responses['startdate'],
                $responses['datestamp']);

        // first read current responses to differentiate between insert and update
        $responseSelect = $db->select()->from('gemsdata__responses')
                ->where('gdr_id_token = ?', $this->_tokenId);
        $currentResponses = $responseSelect->query()->fetchAll();

        // Map to gdr__answer_id index array for easy lookups
        $dbResponse = array();
        foreach($currentResponses as $response)
        {
            $dbResponse[$response['gdr_answer_id']] = $response;
        }

        $inserts = array();
        foreach ($responses as $fieldName => $response) {
            $rValues['gdr_answer_id'] = $fieldName;
            if (is_array($response)) {
                $response = join('|', $response);
            }
            $rValues['gdr_response']  = $response;

            if (array_key_exists($fieldName, $dbResponse)) {    // Already exists, do update
                // But only if value changed
                if ($dbResponse[$fieldName]['gdr_response'] != $response) {
                    $where = $db->quoteInto('gdr_id_token = ? AND ', $rValues['gdr_id_token']) .
                            $db->quoteInto('gdr_answer_id = ?', $fieldName);

                    try {
                        $db->update(
                                'gemsdata__responses',
                                array(
                                    'gdr_response'   => $response,
                                    'gdr_changed'    => $rValues['gdr_changed'],
                                    'gdr_changed_by' => $rValues['gdr_changed_by'],
                                    ),
                                $where);
                    } catch (Zend_Db_Statement_Exception $e) {
                        error_log($e->getMessage());
                    }
                }
            } else {
                // We add the inserts together in one statement to improve speed
                $inserts[] = $rValues;
            }
        }

        if (count($inserts)>0) {
            try {
                $fields = array();
                foreach ($inserts[0] as $fieldName => $value)
                {
                    $fields[] .= $db->quoteIdentifier($fieldName);
                }
                $sql = 'INSERT INTO gemsdata__responses (' . implode(', ', $fields) . ') VALUES ';
                foreach($inserts as $insert) {
                    $vals = array();
                    foreach($insert as $field => $value)
                    {
                        $vals[] = $db->quote($value);   // Takes care of converting Zend_Db_Expression
                    }
                    $sql .= '(' . implode(', ', $vals) . '),';
                }
                $sql = substr($sql, 0, -1) . ';';
                $db->query($sql);
            } catch (Zend_Db_Statement_Exception $e) {
                error_log($e->getMessage());
            }
        }
    }
}
