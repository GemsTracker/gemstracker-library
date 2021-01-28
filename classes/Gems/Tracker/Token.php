<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Event\Application\TokenEvent;
use MUtil\Translate\TranslateableTrait;


/**
 * Object class for checking and changing tokens.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Token extends \Gems_Registry_TargetAbstract
{
    use TranslateableTrait;

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
     * @var string The token id of the token this one was copied from, null when not loaded, false when does not exist
     */
    protected $_copiedFromTokenId = null;

    /**
     *
     * @var array The token id's of the tokens this one was copied to, null when not loaded, [] when none exist
     */
    protected $_copiedToTokenIds = null;

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
     * @var \Gems_Tracker_Token
     */
    private $_nextToken = null;

    /**
     *
     * @var \Gems_Tracker_Token
     */
    private $_previousToken = null;

    /**
     * Holds the relation (if any) for this token
     *
     * @var array
     */
    protected $_relation = null;

    /**
     *
     * @var \Gems_Tracker_Respondent
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
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Event\EventDispatcher
     */
    protected $event;

    /**
     * True when the token does exist.
     *
     * @var boolean
     */
    public $exists = true;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * Logger instance
     *
     * @var \Gems_Log
     */
    protected $logger;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Gems_Tracker_RespondentTrack
     */
    protected $respTrack;

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
     * @var \Gems_Tracker_Survey
     */
    protected $survey;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var \Gems_Util
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
            // loading occurs in checkRegistryRequestAnswers
        }
    }

    /**
     * Add relation to the select statement
     *
     * @param Gems_Tracker_Token_TokenSelect $select
     */
    protected function _addRelation($select)
    {
        // now add a left join with the round table so we have all tokens, also the ones without rounds
        if (!is_null($this->_gemsData['gto_id_relation'])) {
            $select->forWhere('gto_id_relation = ?', $this->_gemsData['gto_id_relation']);
        } else {
            $select->forWhere('gto_id_relation IS NULL');
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
            // \MUtil_Echo::track($this->_gemsData);

            if ($row = $this->db->fetchRow($sql, array($respId, $orgId))) {
                $this->_gemsData = $this->_gemsData + $row;
            } else {
                $token = $this->_tokenId;
                throw new \Gems_Exception("Respondent data missing for token $token.");
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

        $model = new \MUtil_Model_TableModel('gems__tokens');
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
        if (!$this->tracker->filterChangesOnly($this->_gemsData, $values)) {
            return 0;   // No changes
        }

        if (\Gems_Tracker::$verbose) {
            $echo = '';
            foreach ($values as $key => $val) {
                $echo .= $key . ': ' . $this->_gemsData[$key] . ' => ' . $val . "\n";
            }
            \MUtil_Echo::r($echo, 'Updated values for ' . $this->_tokenId);
        }

        $defaults = [
            'gto_changed'    => new \MUtil_Db_Expr_CurrentTimestamp(),
            'gto_changed_by' => $userId
        ];

        // Update values in this object
        $this->_gemsData = $values + $defaults + (array) $this->_gemsData;

        // return 1;
        return $this->db->update('gems__tokens', $values, array('gto_id_token = ?' => $this->_tokenId));
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();
    }

    /**
     * Set menu parameters from this token
     *
     * @param \Gems_Menu_ParameterSource $source
     * @return \Gems_Tracker_Token (continuation pattern)
     */
    public function applyToMenuSource(\Gems_Menu_ParameterSource $source)
    {
        $source->setTokenId($this->_tokenId);
        if (!$this->exists) return $this;

        if (! isset($this->_gemsData['gr2o_patient_nr'])) {
            $this->_ensureRespondentData();
        }
        $this->getRespondentTrack()->applyToMenuSource($source);

        $completed = $this->_gemsData['gto_completion_time'] ? 1 : 0;
        $source->offsetSet('gsu_id_survey', $this->_gemsData['gto_id_survey']);
        $source->offsetSet('is_completed', $completed);
        $source->offsetSet('show_answers', $completed);
        $source->offsetSet('gto_in_source', $this->_gemsData['gto_in_source']);
        $source->offsetSet('gto_reception_code', $this->_gemsData['gto_reception_code']);

        $receptionCode = $this->getReceptionCode();
        $source->offsetSet('grc_success', $receptionCode->isSuccess() ? 1 : 0);
        $canBeTaken = false;
        if ($receptionCode->isSuccess() &&
                ($completed == 0) &&
                ($validFrom = $this->getValidFrom())) {

            $validUntil = $this->getValidUntil();
            $today = new \MUtil_Date();

            $canBeTaken = $validFrom->isEarlier($today) && ($validUntil ? $validUntil->isLater($today) : true);
        }
        $source->offsetSet('can_be_taken', $canBeTaken);

        return $this;
    }

    /**
     * Assign this token to a specific relation
     *
     * @param int $respondentRelationId
     * @param int $relationFieldId
     * @return int 1 if data changed, 0 otherwise
     */
    public function assignTo($respondentRelationId, $relationFieldId)
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
            // \MUtil_Echo::track($referrer);
        } // */

        // Use the survey return if available.
        $surveyReturn = $this->currentUser->getSurveyReturn();
        if ($surveyReturn) {
            // Do not show the base url as it is in $currentUri
            $surveyReturn['NoBase'] = true;

            // Add route reset to prevet the current parameters to be
            // added to the url.
            $surveyReturn['RouteReset'] = true;

            // \MUtil_Echo::track($currentUri, \MUtil_Html::urlString($surveyReturn));
            return $currentUri . \MUtil_Html::urlString($surveyReturn);
        }

        // Ultimate backup solution for return
        return $currentUri . '/ask/forward/' . \MUtil_Model::REQUEST_ID . '/' . urlencode($this->getTokenId());
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->_gemsData) {
            if ($this->currentUser instanceof \Gems_User_User) {
                $this->_gemsData = $this->currentUser->applyGroupMask($this->_gemsData);
            }
        } else {
            if (!$this->db instanceof \Zend_Db_Adapter_Abstract) {
                return false;
            }
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
            $values['gto_in_source'] = 1;
            $survey                  = $this->getSurvey();
            $startTime               = $survey->getStartTime($this);
            if ($startTime instanceof \MUtil_Date) {
                // Value from source overrules any set date time
                $values['gto_start_time'] = $startTime->toString(\Gems_Tracker::DB_DATETIME_FORMAT);

            } else {
                // Otherwise use the time kept by Gems.
                $startTime = $this->getDateTime('gto_start_time');

                //What if we have older tokens... where no gto_start_time was set??
                if (is_null($startTime)) {
                    $startTime = new \MUtil_Date();
                }

                // No need to set $values['gto_start_time'], it does not change
            }

            if ($survey->isCompleted($this)) {
                $complTime         = $survey->getCompletionTime($this);
                $setCompletionTime = true;

                if (! $complTime instanceof \MUtil_Date) {
                    // Token is completed but the source cannot tell the time
                    //
                    // Try to see it was stored already
                    $complTime = $this->getDateTime('gto_completion_time');

                    if ($complTime instanceof \MUtil_Date) {
                        // Again no need to change a time that did not change
                        unset($values['gto_completion_time']);
                        $setCompletionTime = false;
                    } else {
                        // Well anyhow it was completed now or earlier. Get the current moment.
                        $complTime = new \MUtil_Date();
                    }
                }

                //Save the old completiontime
                $oldCompletionTime = $this->_gemsData['gto_completion_time'];
                //Set completion time for completion event
                if ($setCompletionTime) {
                    $values['gto_completion_time']          = $complTime->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
                    $this->_gemsData['gto_completion_time'] = $values['gto_completion_time'];
                }

                // Process any Gems side survey dependent changes
                if ($changed = $this->handleAfterCompletion()) {
                    // Communicate change
                    $result += self::COMPLETION_EVENTCHANGE;

                    if (\Gems_Tracker::$verbose) {
                        \MUtil_Echo::r($changed, 'Source values for ' . $this->_tokenId . ' changed by event.');
                    }
                }

                //Reset completiontime to old value, so changes will be picked up
                $this->_gemsData['gto_completion_time'] = $oldCompletionTime;
                $values['gto_duration_in_sec']          = max($complTime->diffSeconds($startTime), 0);

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
    public function createReplacement($newComment, $userId, array $otherValues = array())
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
            if ($value instanceof \Zend_Date) {
                $value = $value->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
            }
        }

        $tokenId = $this->tracker->createToken($newValues, $userId);

        $replacementLog['gtrp_id_token_new'] = $tokenId;
        $replacementLog['gtrp_id_token_old'] = $this->_tokenId;
        $replacementLog['gtrp_created']      = new \MUtil_Db_Expr_CurrentTimestamp();
        $replacementLog['gtrp_created_by']   = $userId;

        $this->db->insert('gems__token_replacements', $replacementLog);

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
                ->andRounds(array())
                ->andSurveys(array())
                ->andTracks()
                ->forGroupId($this->getSurvey()->getGroupId())
                ->forRespondent($this->getRespondentId(), $this->getOrganizationId())
                ->onlySucces()
                ->forWhere('gsu_active = 1')
                ->forWhere('gro_active = 1 OR gro_active IS NULL')
                ->order('gtr_track_name')
                ->order('gr2t_track_info')
                ->order('gto_valid_until')
                ->order('gto_valid_from');

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
     * @return \MUtil_Date date time or null
     */
    public function getAnswerDateTime($fieldName)
    {
        $survey = $this->getSurvey();
        return $survey->getAnswerDateTime($fieldName, $this);
    }

    /**
     * Returns an array of snippetnames that can be used to display the answers to this token.
     *
     * @return array Of snippet names
     */
    public function getAnswerSnippetNames()
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
    public function getArrayCopy()
    {
        return $this->_gemsData;
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
     * Return the comment for this token
     *
     * @return string
     */
    public function getComment()
    {
        return $this->_gemsData['gto_comment'];
    }

    /**
     *
     * @return \MUtil_Date Completion time as a date or null
     */
    public function getCompletionTime()
    {
        if (isset($this->_gemsData['gto_completion_time']) && $this->_gemsData['gto_completion_time']) {
            if ($this->_gemsData['gto_completion_time'] instanceof \MUtil_Date) {
                return $this->_gemsData['gto_completion_time'];
            }
            return new \MUtil_Date($this->_gemsData['gto_completion_time'], \Gems_Tracker::DB_DATETIME_FORMAT);
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
     * Get the token id of the token this one was copied from, null when not loaded, false when does not exist
     *
     * @return string
     */
    public function getCopiedFrom()
    {
        if (null === $this->_copiedFromTokenId) {
            $this->_copiedFromTokenId = $this->db->fetchOne(
                    "SELECT gtrp_id_token_old FROM gems__token_replacements WHERE gtrp_id_token_new = ?",
                    $this->_tokenId
                    );
        }

        return $this->_copiedFromTokenId;
    }

    /**
     * The token id's of the tokens this one was copied to, null when not loaded, [] when none exist
     *
     * @return array tokenId => tokenId
     */
    public function getCopiedTo()
    {
        if (null === $this->_copiedToTokenIds) {
            $this->_copiedToTokenIds = $this->db->fetchPairs(
                    "SELECT gtrp_id_token_new, gtrp_id_token_new
                        FROM gems__token_replacements
                        WHERE gtrp_id_token_old = ?",
                    $this->_tokenId
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
    public function getCreatedBy()
    {
        return $this->_gemsData['gto_created_by'];
    }

    /**
     *
     * @param string $fieldName
     * @return \MUtil_Date
     */
    public function getDateTime($fieldName)
    {
        if (isset($this->_gemsData[$fieldName])) {
            if ($this->_gemsData[$fieldName] instanceof \MUtil_Date) {
                return $this->_gemsData[$fieldName];
            }

            return \MUtil_Date::ifDate(
                    $this->_gemsData[$fieldName],
                    array(\Gems_Tracker::DB_DATETIME_FORMAT, \Gems_Tracker::DB_DATE_FORMAT)
                    );
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
            return 'Token\\TokenNotFoundSnippet';
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
            return 'Token\\TokenNotFoundSnippet';
        }
    }

    /**
     * Get the email address of the person who needs to fill out this survey.
     *
     * This method will return null when no address available
     *
     * @return string|null Email address of the person who needs to fill out the survey or null
     */
    public function getEmail()
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
    public function getMailSentDate()
    {
        return $this->_gemsData['gto_mail_sent_date'];
    }

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return \Gems_Tracker_Model_StandardTokenModel
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
     * @return \Gems_Tracker_Token
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
     * @return \Gems_Tracker_Token
     */
    public function getNextUnansweredToken()
    {
        $tokenSelect = $this->tracker->getTokenSelect();
        $tokenSelect
                ->andReceptionCodes()
                // ->andRespondents()
                // ->andRespondentOrganizations()
                // ->andConsents
                ->andRounds(array())
                ->andSurveys(array())
                ->forRespondent($this->getRespondentId())
                ->forGroupId($this->getSurvey()->getGroupId())
                ->onlySucces()
                ->onlyValid()
                ->forWhere('gsu_active = 1')
                ->forWhere('gro_active = 1 OR gro_active IS NULL')
                ->order(array('gto_valid_from', 'gto_round_order'));

        $this->_addRelation($tokenSelect);

        if ($tokenData = $tokenSelect->fetchRow()) {
            return $this->tracker->getToken($tokenData);
        }
    }

    /**
     *
     * @return \Gems_User_Organization
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
     * @return \Gems_Tracker_Token
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
     * @return \Gems_Tracker_Token
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
     * Return the \Gems_Util_ReceptionCode object
     *
     * @return \Gems_Util_ReceptionCode reception code
     */
    public function getReceptionCode()
    {
        return $this->util->getReceptionCode($this->_gemsData['gto_reception_code']);
    }

    /**
     * Get the relation object if any
     *
     * @return Gems_Model_RespondentRelationInstance
     */
    public function getRelation()
    {
        if (is_null($this->_relation) || $this->_relation->getRelationId() !== $this->getRelationId()) {
            $model = $this->loader->getModels()->getRespondentRelationModel();
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
    public function getRelationFieldId()
    {
        return $this->hasRelation() ? (int) $this->_gemsData['gto_id_relationfield'] : null;
    }

    /**
     * Get the name of the relationfield for this token
     *
     * @return string
     */
    public function getRelationFieldName()
    {
        if ($relationFieldId = $this->getRelationFieldId()) {
            $names = $this->getRespondentTrack()->getTrackEngine()->getFieldNames();
            $fieldPrefix = \Gems\Tracker\Model\FieldMaintenanceModel::FIELDS_NAME . \Gems\Tracker\Engine\FieldsDefinition::FIELD_KEY_SEPARATOR;
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
    public function getRelationId()
    {
        return $this->hasRelation() ? $this->_gemsData['gto_id_relation'] : null;
    }

    /**
     * Get the respondent linked to this token
     *
     * @return \Gems_Tracker_Respondent
     */
    public function getRespondent()
    {
        $patientNumber  = $this->getPatientNumber();
        $organizationId = $this->getOrganizationId();

        if (! ($this->_respondentObject instanceof \Gems_Tracker_Respondent)
                || $this->_respondentObject->getPatientNumber()  !== $patientNumber
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
            throw new \Gems_Exception(sprintf('Token not loaded correctly', $this->getTokenId()));
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
     * Get the name of the person answering this token
     *
     * Could be the patient or the relation when assigned to one
     *
     * @return string
     */
    public function getRespondentName()
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
     * @return \Gems_Tracker_RespondentTrack
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
     * The result value
     *
     * @return string
     */
    public function getResult()
    {
        return $this->_gemsData['gto_result'];
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
     * Get the round code for this token
     *
     * @return string|null Null when no round id is present or round no longer exists
     */
    public function getRoundCode()
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
     * Return the name of the respondent
     *
     * To be used when there is a relation and you need to know the name of the respondent
     *
     * @return string
     */
    public function getSubjectname()
    {
        return $this->getRespondent()->getName();
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
            return 'Token\\TokenNotFoundSnippet';
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
        return $this->util->getTokenData()->getStatusDescription($this->getStatusCode());

        /*
        $today  = new \Zend_Date();

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

        return $status; // */
    }

    /**
     * Returns token status code
     *
     * @return string Token status code in one letter
     */
    public function getStatusCode()
    {
        return $this->_gemsData['token_status'];
    }

    /**
     *
     * @return \Gems_Tracker_Survey
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
     * @return \MUtil_Model_ModelAbstract
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
        $tokenSelect = $this->tracker->getTokenSelect(new \Zend_Db_Expr('COUNT(*)'));
        $tokenSelect
                ->andReceptionCodes(array())
                ->andSurveys(array())
                ->andRounds(array())
                ->forRespondent($this->getRespondentId())
                ->forGroupId($this->getSurvey()->getGroupId())
                ->onlySucces()
                ->onlyValid()
                ->forWhere('gsu_active = 1')
                ->forWhere('gro_active = 1 OR gro_active IS NULL')
                ->withoutToken($this->_tokenId);

        $this->_addRelation($tokenSelect);

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
     * @return \Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngine()
    {
        if ($this->exists) {
            return $this->tracker->getTrackEngine($this->_gemsData['gto_id_track']);
        }

        throw new \Gems_Exception_Coding('Coding error: requesting track engine for non existing token.');
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
     * @throws \Gems_Tracker_Source_SurveyNotFoundException
     */
    public function getUrl($language, $userId)
    {
        $survey = $this->getSurvey();

        $survey->copyTokenToSource($this, $language);

        if (! $this->_gemsData['gto_in_source']) {
            $values['gto_start_time'] = new \MUtil_Db_Expr_CurrentTimestamp();
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
        $values['gto_return_url'] = $this->calculateReturnUrl();

        $this->_updateToken($values, $userId);

        $this->handleBeforeAnswering();

        return $survey->getTokenUrl($this, $language);
    }

    /**
     *
     * @return \MUtil_Date Valid from as a date or null
     */
    public function getValidFrom()
    {
        return $this->getDateTime('gto_valid_from');
    }

    /**
     *
     * @return \MUtil_Date Valid until as a date or null
     */
    public function getValidUntil()
    {
        return $this->getDateTime('gto_valid_until');
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
        $completedEvent = $survey->getSurveyCompletedEvent();

        $eventName = 'gems.survey.completed';

        if ($this->event->hasListeners($eventName)) {
            // Remove previous gems survey completed events if set
            $listeners = $this->event->getListeners($eventName);
            foreach($listeners as $listener) {
                $order = $this->event->getListenerPriority($eventName, $listener);
                if ($order === 100) {
                    $this->event->removeListener($eventName, $listener);
                }
            }
        }

        if (! $completedEvent && !$this->event->hasListeners($eventName)) {
            return;
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
            $this->event->addListener($eventName, $eventFunction, 100);
        }

        $tokenEvent = new TokenEvent($this);
        try {
            $this->event->dispatch($tokenEvent, $eventName);
        } catch (\Exception $e) {
            $this->logger->log(sprintf(
                "After completion event error for token %s on survey '%s': %s",
                $this->_tokenId,
                $this->getSurveyName(),
                $e->getMessage()
            ), \Zend_Log::ERR);
        }
        if ($completedEvent) {
            // Remove this event to prevent double triggering
            $this->event->removeListener($eventName, $eventFunction);
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
     * @param type $tokenId The tokend the answers are for
     * @param array $tokenAnswers Array with answers. May be changed in process
     * @return array The changed values
     */
    public function handleBeforeAnswering()
    {
        $survey = $this->getSurvey();
        $beforeAnswerEvent  = $survey->getSurveyBeforeAnsweringEvent();

        $eventName = 'gems.survey.before-answering';

        if (! $beforeAnswerEvent && !$this->event->hasListeners($eventName)) {
            return;
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
            $this->event->addListener($eventName, $eventFunction, 100);
        }

        $tokenEvent = new TokenEvent($this);

        try {
            $this->event->dispatch($tokenEvent, $eventName);
        } catch (\Exception $e) {
            $this->logger->log(sprintf(
                "Before answering before event error for token %s on survey '%s': %s",
                $this->_tokenId,
                $this->getSurveyName(),
                $e->getMessage()
            ), \Zend_Log::ERR);
        }
        if ($beforeAnswerEvent) {
            // Remove this event to prevent double triggering
            $this->event->removeListener($eventName, $eventFunction);
        }

        $changed = $tokenEvent->getChanged();
        if ($changed && is_array($changed)) {

            $this->setRawAnswers($changed);

            if (\Gems_Tracker::$verbose) {
                \MUtil_Echo::r($changed, 'Source values for ' . $this->_tokenId . ' changed by event.');
            }
        }

        return $changed;
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
     * @deprecated Use the ReceptionCode->hasRedoCode
     * @return boolean
     */
    public function hasRedoCode()
    {
        return $this->getReceptionCode()->hasRedoCode();
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
    }

    /**
     * Is this token linked to a relation?
     *
     * @return boolean
     */
    public function hasRelation()
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
     * @return boolean
     */
    public function hasResult()
    {
        return $this->_gemsData['gto_result'];
    }

    /**
     *
     * @deprecated Use the ReceptionCode->isSuccess
     * @return boolean
     */
    public function hasSuccesCode()
    {
        return $this->getReceptionCode()->isSuccess();
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
     * True when the valid from is set and in the past and the valid until is not set or is in the future
     *
     * @return boolean
     */
    public function isCurrentlyValid()
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
     * @return boolean
     */
    public function isExpired()
    {
        $date = $this->getValidUntil();

        if ($date instanceof \MUtil_Date) {
            return $date->isEarlierOrEqual(time());
        }

        return false;
    }

    /**
     * Can mails be sent for this token?
     *
     * Cascades to track and respondent level mailable setting
     * also checks is the email field for respondent or relation is not null
     *
     * @return boolean
     */
    public function isMailable()
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
     * @return boolean
     */
    public function isNotYetValid()
    {
        $date = $this->getValidFrom();

        if ($date instanceof \MUtil_Date) {
            return $date->isLaterOrEqual(time());
        }

        return true;
    }

    /**
     *
     * @return boolean True when this date was set by user input
     */
    public function isValidFromManual()
    {
        return isset($this->_gemsData['gto_valid_from_manual']) && $this->_gemsData['gto_valid_from_manual'];
    }

    /**
     *
     * @return boolean True when this date was set by user input
     */
    public function isValidUntilManual()
    {
        return isset($this->_gemsData['gto_valid_until_manual']) && $this->_gemsData['gto_valid_until_manual'];
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return \Gems_Tracker_Token (continuation pattern)
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
        if ($this->currentUser instanceof \Gems_User_User) {
            $this->_gemsData = $this->currentUser->applyGroupMask($this->_gemsData);
        }
        $this->exists = isset($this->_gemsData['gto_id_token']);

        return $this;
    }

    /**
     * Refresh the consent Code
     *
     * @param string $consentCode
     */
    public function refreshConsent()
    {
        if (isset($this->_gemsData['gco_code'])) {
            // Setting the gco_code to false will make sure the data is reloaded
            $this->_gemsData['gco_code'] = false;
            $this->getConsentCode();
        }
    }

    /**
     *
     * @param string|\MUtil_Date $completionTime Completion time as a date or null
     * @param int $userId The current user
     * @return \Gems_Tracker_Token (continuation pattern)
     */
    public function setCompletionTime($completionTime, $userId)
    {
        $values['gto_completion_time'] = null;
        if (!is_null($completionTime)) {
            if (! $completionTime instanceof \Zend_Date) {
                $completionTime = \MUtil_Date::ifDate(
                        $completionTime,
                        array(\Gems_Tracker::DB_DATETIME_FORMAT, \Gems_Tracker::DB_DATE_FORMAT, null)
                        );
            }
            if ($completionTime instanceof \Zend_Date) {
                $values['gto_completion_time'] = $completionTime->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
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
     * Sets the next token in this track
     *
     * @param \Gems_Tracker_Token $token
     * @return \Gems_Tracker_Token (continuation pattern)
     */
    public function setNextToken(\Gems_Tracker_Token $token)
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
    public function setRawAnswers($answers)
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
     * @param string $code The new (non-success) reception code or a \Gems_Util_ReceptionCode object
     * @param string $comment Comment False values leave value unchanged
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setReceptionCode($code, $comment, $userId)
    {
        // Make sure it is a \Gems_Util_ReceptionCode object
        if (! $code instanceof \Gems_Util_ReceptionCode) {
            $code = $this->util->getReceptionCode($code);
        }
        $values['gto_reception_code'] = $code->getCode();
        if ($comment) {
            $values['gto_comment'] = $comment;
        }
        // \MUtil_Echo::track($values);

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
    public function setRoundDescription($description, $userId)
    {
        $values = $this->_gemsData;
        $values['gto_round_description'] = $description;
        return $this->_updateToken($values, $userId);
    }

    /**
     *
     * @param mixed $validFrom \Zend_Date or string
     * @param mixed $validUntil null, \Zend_Date or string. False values leave values unchangeds
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setValidFrom($validFrom, $validUntil, $userId)
    {
        if ($validFrom && $this->getMailSentDate()) {
            // Check for newerness

            if ($validFrom instanceof \Zend_Date) {
                $start = $validFrom;
            } else {
                $start = new \MUtil_Date($validFrom, \Gems_Tracker::DB_DATETIME_FORMAT);
            }

            if ($start->isLater($this->getMailSentDate())) {
                $values['gto_mail_sent_date'] = null;
                $values['gto_mail_sent_num']  = 0;

                $now = new \MUtil_Date();
                $newComment = sprintf(
                    $this->_('%s: Reset number of contact moments because new start date %s is later than last contact date.'),
                    $now->toString('yyyy-MM-dd HH:mm:ss'),
                    $start->toString('yyyy-MM-dd HH:mm:ss')
                );
                $comment = $this->getComment();
                if (!empty($comment)) {
                    $comment .= "\n";
                }
                $values['gto_comment'] = $comment .= $newComment;
            }
        }

        if ($validFrom instanceof \Zend_Date) {
            $validFrom = $validFrom->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
        } elseif ('' === $validFrom) {
            $validFrom = null;
        }
        if ($validUntil instanceof \Zend_Date) {
            $validUntil = $validUntil->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
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
    protected function toResponseDatabase($userId)
    {
        $responseDb = $this->project->getResponseDatabase();

        // WHY EXPLANATION!!
        //
        // For some reason mysql prepared parameters do nothing with a \Zend_Db_Expr
        // object and that causes an error when using CURRENT_TIMESTAMP
        $current = \MUtil_Date::now()->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
        $rValues = array(
            'gdr_id_token'   => $this->_tokenId,
            'gdr_changed'    => $current,
            'gdr_changed_by' => $userId,
            'gdr_created'    => $current,
            'gdr_created_by' => $userId,
        );
        $responses = $this->getRawAnswers();

        $source = $this->getSurvey()->getSource();
        if ($source instanceof \Gems_Tracker_Source_SourceAbstract) {
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
        // \MUtil_Echo::track($currentResponses, $responses);

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
                        // \MUtil_Echo::track($sql, $rValues['gdr_id_token'], $fieldName, $response);
                        $statement->execute(array(
                            $response,
                            $rValues['gdr_changed'],
                            $rValues['gdr_changed_by'],
                            $rValues['gdr_id_token'],
                            $fieldName
                        ));
                    } catch (\Zend_Db_Statement_Exception $e) {
                        error_log($e->getMessage());
                        \Gems_Log::getLogger()->logError($e);
                    }
                }
            } else {
                // We add the inserts together in the next prepared statement to improve speed
                $inserts[$fieldName] = $rValues;
            }
        }

        if (count($inserts)>0) {
            // \MUtil_Echo::track($inserts);
            try {
                $fields = array_keys(reset($inserts));
                $fields = array_map(array($responseDb, 'quoteIdentifier'), $fields);
                $sql = 'INSERT INTO gemsdata__responses (' .
                        implode(', ', $fields) . ') VALUES (' .
                        implode(', ', array_fill(1, count($fields), '?')) . ')';

                // \MUtil_Echo::track($sql);
                $statement = $responseDb->prepare($sql);

                foreach($inserts as $insert) {
                    // \MUtil_Echo::track($insert);
                    $statement->execute($insert);
                }

            } catch (\Zend_Db_Statement_Exception $e) {
                error_log($e->getMessage());
                \Gems_Log::getLogger()->logError($e);
            }
        }
    }
}
