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

use Gems\Event\Application\TokenEvent;
use Gems\Event\Application\RespondentTrackFieldUpdateEvent;
use Gems\Event\Application\RespondentTrackFieldEvent;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Translate\DbTranslateUtilTrait;

use MUtil\Model;

/**
 * Object representing a track assignment to a respondent.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class RespondentTrack extends \Gems\Registry\TargetAbstract
{
    use DbTranslateUtilTrait;

    /**
     *
     * @var array of round_id => \Gems\Tracker\Token
     */
    protected $_activeTokens = array();

    /**
     * @var \Gems\Tracker\Token
     */
    protected $_checkStart;

    /**
     * If a field has a code name the value will occur both using
     * the code name and using the id.
     *
     * @var array Field data id/code => value
     */
    protected $_fieldData = null;

    /**
     *
     * @var \Gems\Tracker\Token
     */
    protected $_firstToken;

    /**
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $_respondentObject = null;

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
     * @var array The gems__rounds data
     */
    protected $_rounds = null;

    /**
     *
     * @var array of \Gems\Tracker\Token
     */
    protected $_tokens;

    /**
     * @var array
     */
    protected $_tablesForTranslations = [
        'gems__respondent2track' => 'gr2t_id_respondent_track',
        'gems__tracks' => 'gtr_id_track',
        ];

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * @var \Gems\Event\EventDispatcher
     */
    protected $event;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Tracker
     */
    protected $tracker;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

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
     * Check this respondent track for the number of tokens completed / to do
     *
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int 1 if the track was changed by this code
     */
    public function _checkTrackCount($userId)
    {
        $sqlCount  = 'SELECT COUNT(*) AS count,
                SUM(CASE WHEN gto_completion_time IS NULL THEN 0 ELSE 1 END) AS completed
            FROM gems__tokens INNER JOIN
                gems__reception_codes ON gto_reception_code = grc_id_reception_code AND grc_success = 1
            WHERE gto_id_respondent_track = ?';

        $counts = $this->db->fetchRow($sqlCount, $this->_respTrackId);
        if (! $counts) {
            $counts = array('count' => 0, 'completed' => 0);
        }

        $values['gr2t_count']      = intval($counts['count']);
        $values['gr2t_completed']  = intval($counts['completed']);

        if (! $this->_respTrackData['gr2t_end_date_manual']) {
            $values['gr2t_end_date'] = $this->calculateEndDate();
        }

        if ($values['gr2t_count'] == $values['gr2t_completed']) {
            if (null === $this->_respTrackData['gr2t_end_date']) {
                $now =  new DateTimeImmutable();
                $values['gr2t_end_date'] = $now->format(Model::getTypeDefault(Model::TYPE_DATETIME, 'storageFormat'));
            }
            //Handle TrackCompletionEvent, send only changed fields in $values array
            $this->handleTrackCompletion($values, $userId);
        }

        // Remove unchanged values
        $this->tracker->filterChangesOnly($this->_respTrackData, $values);

        return $this->_updateTrack($values, $userId);
    }

    /**
     * Makes sure the fieldData is in $this->_fieldData
     *
     * @param boolean $reload Optional parameter to force reload.
     */
    private function _ensureFieldData($reload = false)
    {
        if ($this->_respTrackData && (null === $this->_fieldData) || $reload) {
            $this->_fieldData = $this->getTrackEngine()->getFieldsData($this->_respTrackId);
            $this->_fixFieldData();
        }
    }

    /**
     * Adds the code fields to the fieldData array
     */
    public function _fixFieldData()
    {
        $fieldMap = $this->getTrackEngine()->getFieldCodes();

        foreach ($this->_fieldData as $key => $value) {
            if (isset($fieldMap[$key])) {
                // The old name remains in the data set of course,
                // using the code is a second occurence
                $this->_fieldData[$fieldMap[$key]] = $value;
            }
        }
    }

    /**
     * Makes sure the respondent data is part of the $this->_respTrackData
     */
    protected function _ensureRespondentData()
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
                throw new \Gems\Exception("Respondent data missing for track $trackId.");
            }
        }
    }

    /**
     * Makes sure the rounds info is loaded
     *
     * @param boolean $reload
     */
    protected function _ensureRounds($reload = false)
    {
        if ((null === $this->_rounds) || $reload) {
            $rounds = $this->getTrackEngine()->getRoundModel(true, 'index')
                                             ->load(['gro_id_track'=>$this->getTrackId()]);

            $this->_rounds = array();
            foreach($rounds as $round) {
                $this->_rounds[$round['gro_id_round']] = $round;
            }
        }
    }

    /**
     * Makes sure the track data is part of the $this->_respTrackData
     */
    protected function _ensureTrackData()
    {
        if (! isset($this->_respTrackData['gtr_code'], $this->_respTrackData['gtr_name'])) {
            $trackData = $this->fetchTranslatedRow('gems__tracks', 'gtr_id_track', $this->_respTrackData['gr2t_id_track']);
            if ($trackData) {
                $this->_respTrackData = $this->_respTrackData + $trackData;
            } else {
                $trackId = $this->_respTrackId;
                throw new \Gems\Exception("Track data missing for respondent track $trackId.");
            }
        }
    }

    /**
     * Processes the field values and returns the new complete field data
     *
     * @param array $newFieldData The new field values, may be partial, field set by code overwrite field set by key
     * @param array $oldFieldData The old field values
     * @param \Gems\Tracker\Engine\TrackEngineInterface $trackEngine
     * @return array The processed data in the format key1 => val1, code1 => val1, key2 => val2
     */
    protected function _mergeFieldValues(array $newFieldData, array $oldFieldData, \Gems\Tracker\Engine\TrackEngineInterface $trackEngine)
    {
        $fieldDef = $trackEngine->getFieldsDefinition();
        $fieldMap = $fieldDef->getFieldCodes() + $fieldDef->getManualFields();
        $output   = array();

        // \MUtil\EchoOut\EchoOut::track($fieldMap);
        foreach ($fieldMap as $key => $code) {
            if ($code) {
                if (array_key_exists($code, $newFieldData)) {
                    $output[$key]  = $newFieldData[$code];
                    $output[$code] = $newFieldData[$code];
                } elseif (array_key_exists($key, $newFieldData)) {
                    $output[$key]  = $newFieldData[$key];
                    $output[$code] = $newFieldData[$key];
                } elseif (isset($oldFieldData[$code])) {
                    $output[$key]  = $oldFieldData[$code];
                    $output[$code] = $oldFieldData[$code];
                } elseif (isset($oldFieldData[$key])) {
                    $output[$key]  = $oldFieldData[$key];
                    $output[$code] = $oldFieldData[$key];
                } else {
                    $output[$key]  = null;
                    $output[$code] = null;
                }
            } else {
                if (array_key_exists($key, $newFieldData)) {
                    $output[$key]  = $newFieldData[$key];
                } elseif (isset($oldFieldData[$key])) {
                    $output[$key]  = $oldFieldData[$key];
                } else {
                    $output[$key]  = null;
                }
            }
        }

        return $output;
    }

    /**
     * Save the values if any have been changed
     *
     * @param array $values
     * @param int $userId
     * @return int
     */
    protected function _updateTrack(array $values, $userId  = null)
    {
        if (null === $userId) {
            $userId = $this->currentUser->getUserId();
        }
        // \MUtil\EchoOut\EchoOut::track($values);
        if ($this->tracker->filterChangesOnly($this->_respTrackData, $values)) {
            $where = $this->db->quoteInto('gr2t_id_respondent_track = ?', $this->_respTrackId);

            if (\Gems\Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_respTrackData[$key] . ' => ' . $val . "\n";
                }
                \MUtil\EchoOut\EchoOut::r($echo, 'Updated values for ' . $this->_respTrackId);
            }

            if (! isset($values['gr2t_changed'])) {
                $values['gr2t_changed'] = new \MUtil\Db\Expr\CurrentTimestamp();
            }
            if (! isset($values['gr2t_changed_by'])) {
                $values['gr2t_changed_by'] = $userId;
            }

            $this->_respTrackData = $values + $this->_respTrackData;
            // \MUtil\EchoOut\EchoOut::track($values);
            // return 1;
            return $this->db->update('gems__respondent2track', $values, $where);

        } else {
            return 0;
        }
    }

    /**
     * Add a one-off survey to the existing track.
     *
     * @param int $surveyId    the gsu_id of the survey to add
     * @param array $surveyData
     * @param int $userId
     * @param boolean $checkTrack Should the track be checked? Set to false when adding more then one and check manually
     * @return \Gems\Tracker\Token
     */
    public function addSurveyToTrack($surveyId, $surveyData, $userId, $checkTrack = true )
    {
        //Do something to get a token and add it
        $tokenLibrary = $this->tracker->getTokenLibrary();

        //Now make sure the data to add is correct:
        $surveyData['gto_id_respondent_track'] = $this->_respTrackId;
        $surveyData['gto_id_organization']     = $this->_respTrackData['gr2t_id_organization'];
        $surveyData['gto_id_track']            = $this->_respTrackData['gr2t_id_track'];
        $surveyData['gto_id_respondent']       = $this->_respTrackData['gr2t_id_user'];
        $surveyData['gto_id_survey']           = $surveyId;

        if (! isset($surveyData['gto_id_round'])) {
            $surveyData['gto_id_round'] = 0;
        }

        $tokenId = $tokenLibrary->createToken($surveyData, $userId);

        if ($checkTrack === true) {
            //Now refresh the track to include the survey we just added (easiest way as order may change)
            $this->getTokens(true);

            $this->checkTrackTokens($userId, $this->_tokens[$tokenId]);
            // Update the track counter
            //$this->_checkTrackCount($userId);
            return $this->_tokens[$tokenId];
        }

        return $this->tracker->getToken($tokenId);
    }

    /**
     * Add a one-off survey to the existing track.
     *
     * @param type $surveyId    the gsu_id of the survey to add
     * @param type $surveyData
     * @param int $userId
     * @param boolean $checkTrack Should the track be checked? Set to false when adding more then one and check manually
     * @return \Gems\Tracker\Token
     */
    public function addTokenToTrack(\Gems\Tracker\Token $token, $tokenData, $userId, $checkTrack = true)
    {
        //Now make sure the data to add is correct:
        $tokenData['gto_id_respondent_track'] = $this->_respTrackId;
        $tokenData['gto_id_organization']     = $this->_respTrackData['gr2t_id_organization'];
        $tokenData['gto_id_track']            = $this->_respTrackData['gr2t_id_track'];
        $tokenData['gto_id_respondent']       = $this->_respTrackData['gr2t_id_user'];
        $tokenData['gto_changed']             = new \MUtil\Db\Expr\CurrentTimestamp();
        $tokenData['gto_changed_by']          = $userId;

        $where = $this->db->quoteInto('gto_id_token = ?', $token->getTokenId());
        $this->db->update('gems__tokens', $tokenData, $where);

        $token->refresh();

        if ($checkTrack === true) {
            //Now refresh the track to include the survey we just added (easiest way as order may change)
            $this->getTokens(true);

            $this->checkTrackTokens($userId, $token);
            // Update the track counter
            //$this->_checkTrackCount($userId);
        }

        return $token;
    }

    /**
     * Set menu parameters from this token
     *
     * @param \Gems\Menu\ParameterSource $source
     * @return \Gems\Tracker\RespondentTrack (continuation pattern)
     */
    public function applyToMenuSource(\Gems\Menu\ParameterSource $source)
    {
        $source->setRespondentTrackId($this->_respTrackId);
        $source->offsetSet(
                'gr2t_active',
                (isset($this->_respTrackData['gr2t_active']) ? $this->_respTrackData['gr2t_active'] : 0)
                );
        $source->offsetSet('can_edit', $this->hasSuccesCode() ? 1 : 0);
        $source->offsetSet('track_can_be_created', 0);

        $this->getRespondent()->applyToMenuSource($source);
        $this->getTrackEngine()->applyToMenuSource($source);

        return $this;
    }

    /**
     * Assign the tokens to the correct relation
     *
     * Only surveys that have not yet been answered will be assigned to the correct relation.
     *
     * @return int Number of changes tokens
     */
    public function assignTokensToRelations()
    {
        // Find out if we have relation fields and return when none exists in this track
        $relationFields = $this->getTrackEngine()->getFieldsOfType('relation');
        if (empty($relationFields)) {
            return 0;
        }

        // Check if we have a respondent relation id (grr_id) in the track fields
        // and assign the token to the correct relation or leave open when no
        // relation is defined.
        $this->_ensureRounds();
        $relationFields = $this->getFieldData();
        $fieldPrefix = FieldsDefinition::makeKey(FieldMaintenanceModel::FIELDS_NAME, '');
        $changes = 0;
        foreach ($this->getTokens() as $token) {
            /* @var $token \Gems\Tracker\Token */
            if ((!$token->isCompleted()) && $token->getReceptionCode()->isSuccess()) {
                $roundId = $token->getRoundId();
                if (!array_key_exists($roundId, $this->_rounds)) {
                    // If not a current round for this track, do check the round when it still exists
                    $round = $this->getTrackEngine()->getRoundModel(true, 'index')->loadFirst(array('gro_id_round' => $roundId));
                } else {
                    $round = $this->_rounds[$roundId];
                }

                $relationFieldId = null;
                $relationId      = null;

                // Read from the round
                if (!empty($round) && $round['gro_id_track'] == $this->getTrackId() && $round['gro_active'] == 1) {
                    if ($round['gro_id_relationfield'] > 0) {
                        $relationFieldId = $round['gro_id_relationfield'];
                    }
                } else {
                    // Try to read from token, as this is a token without a round
                    $relationFieldId = $token->getRelationFieldId();
                }

                if ($relationFieldId>0) {
                    $fieldKey = $fieldPrefix . $relationFieldId;
                    if (isset($relationFields[$fieldKey])) {
                        $relationId = (int) $relationFields[$fieldKey];
                    } else {
                        $relationId = -1 * $relationFieldId;
                    }
                }

                $changes = $changes + $token->assignTo($relationId, $relationFieldId);
            }
        }

        if (\MUtil\Model::$verbose && $changes > 0) {
            \MUtil\EchoOut\EchoOut::r(sprintf('%s tokens changed due to changes in respondent relation assignments.', $changes));
        }

        return $changes;
    }

    /**
     * Calculates the track end date
     *
     * The end date can be calculated when:
     *  - all active tokens have a completion date
     *  - or all active tokens have a valid until date
     *  - or the end date of the tokens is calculated using the end date
     *
     *  You can overrule this calculation at the project level.
     *
     * @return string or null
     */
    public function calculateEndDate()
    {
        // Exclude the tokens whose end date is calculated from the track end date
        $excludeWheres[] = sprintf(
                "gro_valid_for_source = '%s' AND gro_valid_for_field = 'gr2t_end_date'",
                \Gems\Tracker\Engine\StepEngineAbstract::RESPONDENT_TRACK_TABLE
                );

        // Exclude the tokens whose start date is calculated from the track end date, while the
        // end date is calculated using that same start date
        $excludeWheres[] = sprintf(
                "gro_valid_after_source = '%s' AND gro_valid_after_field = 'gr2t_end_date' AND
                    gro_id_round = gro_valid_for_id AND
                    gro_valid_for_source = '%s' AND gro_valid_for_field = 'gto_valid_from'",
                \Gems\Tracker\Engine\StepEngineAbstract::RESPONDENT_TRACK_TABLE,
                \Gems\Tracker\Engine\StepEngineAbstract::TOKEN_TABLE
                );
        // In future we may want to add some nesting to this, e.g. tokens with an end date calculated
        // from another token whose... for the time being users should use the end date directly in
        // each token, otherwise the end date will not be calculated

        $maxExpression = "
            CASE
            WHEN SUM(
                CASE WHEN COALESCE(gto_completion_time, gto_valid_until) IS NULL THEN 1 ELSE 0 END
                ) > 0
            THEN NULL
            ELSE MAX(COALESCE(gto_completion_time, gto_valid_until))
            END as enddate";

        $tokenSelect = $this->tracker->getTokenSelect([new \Zend_Db_Expr($maxExpression)]);
        $tokenSelect->andReceptionCodes([], false)
                ->andRounds([])
                ->forRespondentTrack($this->_respTrackId)
                ->onlySucces();

        foreach ($excludeWheres as $where) {
            $tokenSelect->forWhere('NOT (' . $where . ')');
        }

        $endDate = $tokenSelect->fetchOne();

        // \MUtil\EchoOut\EchoOut::track($endDate, $tokenSelect->getSelect()->__toString());

        if (false === $endDate) {
            return null;
        } else {
            return $endDate;
        }
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        $this->initDbTranslations();

        if ($this->_respTrackData) {
            $this->_respTrackData = $this->translateTables($this->_tablesForTranslations, $this->_respTrackData);
            if ($this->currentUser instanceof \Gems\User\User) {
                $this->_respTrackData = $this->currentUser->applyGroupMask($this->_respTrackData);
            }
        } else {
            if ($this->db instanceof \Zend_Db_Adapter_Abstract) {
                $this->refresh();
            }
        }

        return (boolean) $this->_respTrackData;
    }

    /**
     * Check this respondent track for changes to the tokens
     *
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems\Tracker\Token $fromToken Optional token to start from
     * @param \Gems\Tracker\Token $skipToken Optional token to skip in the recalculation when $fromToken is used
     * @return int The number of tokens changed by this code
     */
    public function checkTrackTokens($userId, \Gems\Tracker\Token $fromToken = null, \Gems\Tracker\Token $skipToken = null)
    {
        // Execute any defined functions
        $count = $this->handleTrackCalculation($userId);

        $engine = $this->getTrackEngine();

        $this->db->beginTransaction();
        // Check for validFrom and validUntil dates that have changed.
        if ($fromToken) {
            $count += $engine->checkTokensFrom($this, $fromToken, $userId, $skipToken);
        } elseif ($this->_checkStart) {
            $count += $engine->checkTokensFrom($this, $this->_checkStart, $userId);
        } else {
            $count += $engine->checkTokensFromStart($this, $userId);
        }
        $this->db->commit();

        // Update token completion count and possible enddate
        $this->_checkTrackCount($userId);

        return $count;
    }

    /**
     * Returns a token with a success reception code for this round or null
     *
     * @param int $roundId \Gems round id
     * @param \Gems\Tracker\Token $token Optional token to add as a round (for speed optimization)
     * @return \Gems\Tracker\Token
     */
    public function getActiveRoundToken($roundId, \Gems\Tracker\Token $token = null)
    {
        if ((null !== $token) && $token->hasSuccesCode()) {
            // Cache the token
            //
            // WARNING: This may cause bugs for tracks where two tokens exists
            // with this roundId and a success reception code, but this does speed
            // this function with track engines where that should not occur.
            $this->_activeTokens[$token->getRoundId()] = $token;
        }

        // Nothing to find
        if (! $roundId) {
            return null;
        }

        // Use array_key_exists since there may not be a valid round
        if (! array_key_exists($roundId, $this->_activeTokens)) {
            $tokenSelect = $this->tracker->getTokenSelect();
            $tokenSelect->andReceptionCodes()
                    ->forRespondentTrack($this->_respTrackId)
                    ->forRound($roundId)
                    ->onlySucces();

            // \MUtil\EchoOut\EchoOut::track($tokenSelect->__toString());

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
     * @return string Internal code of the track
     */
    public function getCode()
    {
        if (!isset($this->_respTrackData['gtr_code'])) {
            $this->_ensureTrackData();
        }

        return $this->_respTrackData['gtr_code'];
    }

    /**
     * Return all possible code fields with the values filled for those that exist for this track,
     *
     * @return array code => value
     */
    public function getCodeFields()
    {
        $fieldDef = $this->getTrackEngine()->getFieldsDefinition();
        $codes    = $this->tracker->getAllCodeFields();
        $results  = array_fill_keys($codes, null);

        $this->_ensureFieldData();

        foreach ($this->_fieldData as $id => $value) {
            if (!isset($codes[$id])) {
                continue;
            }

            $fieldCode           = $codes[$id];
            $results[$fieldCode] = $value;
            $field               = $fieldDef->getFieldByCode($fieldCode);
            if (!is_null($field)) {
                $results[$fieldCode] = $field->calculateFieldInfo($value, $this->_fieldData);
            }
        }

        return $results;
    }

    /**
     *
     * @return string Comment field
     */
    public function getComment()
    {
        if (isset($this->_respTrackData['gr2t_comment'])) {
            return $this->_respTrackData['gr2t_comment'];
        }

        return null;
    }

    /**
     *
     * @return int The number of rounds completed
     */
    public function getCompleted()
    {
        if (isset($this->_respTrackData['gr2t_completed'])) {
            return $this->_respTrackData['gr2t_completed'];
        }

        return 0;
    }

    /**
     *
     * @return int The number of rounds
     */
    public function getCount()
    {
        if (isset($this->_respTrackData['gr2t_count'])) {
            return $this->_respTrackData['gr2t_count'];
        }

        return 0;
    }

    /**
     * The round description of the first round that has not been answered.
     *
     * @return string Round description or Stopped/Completed if not found.
     */
    public function getCurrentRound()
    {
        $isStop = false;
        $today  = new \Zend_Date();
        $tokens = $this->getTokens();
        $stop   = $this->util->getReceptionCodeLibrary()->getStopString();

        foreach ($tokens as $token) {
            $validUntil = $token->getValidUntil();

            if (! empty($validUntil) && $validUntil->isEarlier($today)) {
                continue;
            }

            if ($token->isCompleted()) {
                continue;
            }

            $code = $token->getReceptionCode();
            if (! $code->isSuccess()) {
                if ($code->getCode() === $stop) {
                    $isStop = true;
                }
                continue;
            }

            return $token->getRoundDescription();
        }
        if ($isStop) {
            return $this->translate->_('Track stopped');
        }

        return $this->translate->_('Track completed');
    }

    /**
     *
     * @param string $fieldName
     * @return DateTimeInterface
     */
    public function getDate($fieldName)
    {
        if (isset($this->_respTrackData[$fieldName])) {
            $date = $this->_respTrackData[$fieldName];
        } else {
            $this->_ensureFieldData();

            if (isset($this->_fieldData[$fieldName])) {
                $date = $this->_fieldData[$fieldName];

                if ($this->getTrackEngine()->isAppointmentField($fieldName)) {
                    $appointment = $this->tracker->getAppointment($date);
                    if ($appointment->isActive()) {
                        $date = $appointment->getAdmissionTime();
                    } else {
                        $date = false;
                    }
                }
            } else {
                $date = false;
            }
        }

        if ($date) {
            return Model::getDateTimeInterface($date);
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
     * @deprecated since version 1.7.1 Snippets defined TrackAction
     */
    public function getEditSnippets()
    {
        return $this->getTrackEngine()->getTrackEditSnippetNames($this);
    }

    /**
     * The end date of this track
     *
     * @return ?DateTimeInterface
     */
    public function getEndDate()
    {
        if (isset($this->_respTrackData['gr2t_end_date'])) {
            return DateTimeImmutable::createFromFormat(\Gems\Tracker::DB_DATETIME_FORMAT, $this->_respTrackData['gr2t_end_date']);
        }
    }

    /**
     *
     * @return string Name of the track
     */
    public function getExternalTrackName()
    {
        if (!isset($this->_respTrackData['gtr_track_name'])) {
            $this->_ensureTrackData();
        }
        if (isset($this->_respTrackData['gtr_external_description']) && $this->_respTrackData['gtr_external_description']) {
            return $this->_respTrackData['gtr_external_description'];
        }

        return $this->getTrackName();
    }

    /**
     * Returns the field data for this respondent track id.
     *
     * The values of fields with a field code occur twice: once using the field
     * id and once using the code name.
     *
     * @return array of the existing field values for this respondent track
     */
    public function getFieldData()
    {
        $this->_ensureFieldData();

        return $this->_fieldData;
    }

    /**
     * Returns the description of this track as stored in the fields.
     *
     * @return string
     */
    public function getFieldsInfo()
    {
        return $this->_respTrackData['gr2t_track_info'];
    }

    /**
     * Returns the first token in this track
     *
     * @return \Gems\Tracker\Token
     */
    public function getFirstToken()
    {
        if (! $this->_firstToken) {
            if (! $this->_tokens) {
                //No cache yet, but we might need all tokens later
                $this->getTokens();
            }
            $this->_firstToken = reset($this->_tokens);
        }

        return $this->_firstToken;
    }

    /**
     * Returns the first token in this track
     *
     * @return \Gems\Tracker\Token
     */
    public function getLastToken()
    {
        if (! $this->_tokens) {
            //No cache yet, but we might need all tokens later
            $this->getTokens();
        }
        return end($this->_tokens);
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
     * Return the \Gems\Util\ReceptionCode object
     *
     * @return \Gems\Util\ReceptionCode reception code
     */
    public function getReceptionCode()
    {
        return $this->util->getReceptionCode($this->_respTrackData['gr2t_reception_code']);
    }

    /**
     * Get the respondent linked to this token
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent()
    {
        $patientNumber  = $this->getPatientNumber();
        $organizationId = $this->getOrganizationId();

        if (! ($this->_respondentObject instanceof \Gems\Tracker\Respondent)
                || $this->_respondentObject->getPatientNumber()  !== $patientNumber
                || $this->_respondentObject->getOrganizationId() !== $organizationId) {
            $this->_respondentObject = $this->loader->getRespondent($patientNumber, $organizationId);
        }

        return $this->_respondentObject;
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
     * Return the default language for the respondent
     *
     * @return string Two letter language code
     */
    public function getRespondentLanguage()
    {
        if (! isset($this->_respTrackData['grs_iso_lang'])) {
            $this->_ensureRespondentData();

            if (! isset($this->_respTrackData['grs_iso_lang'])) {
                // Still not set in a project? The it is single language
                $this->_respTrackData['grs_iso_lang'] = $this->locale->getLanguage();
            }
        }

        return $this->_respTrackData['grs_iso_lang'];
    }

    /**
     * Return the name of the respondent
     *
     * @return string The respondents name
     */
    public function getRespondentName()
    {
        if (! isset($this->_respTrackData['grs_first_name'], $this->_respTrackData['grs_last_name'])) {
            $this->_ensureRespondentData();
        }

        return trim($this->_respTrackData['grs_first_name'] . ' ' . $this->_respTrackData['grs_surname_prefix']) . ' ' . $this->_respTrackData['grs_last_name'];
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
     * Return the appointment (if any) linked to the valid after setting of given roundId
     *
     * @param int $roundId
     * @return int | null | false False when RoundId not found or not an appointment otherwise appointment id or null when not set
     */
    public function getRoundAfterAppointmentId($roundId)
    {
        $this->_ensureFieldData();
        $this->_ensureRounds();

        if (isset($this->_rounds[$roundId])) {
            $round = $this->_rounds[$roundId];

            if (isset($round['gro_valid_after_source'], $round['gro_valid_after_field']) &&
                    ('app' === $round['gro_valid_after_source'])) {

                if (isset($this->_fieldData[$round['gro_valid_after_field']])) {
                    return $this->_fieldData[$round['gro_valid_after_field']];
                } else {
                    return null;
                }
            }
        }

        return false;
    }

    /**
     * Return the round code for a given roundId
     *
     * @param int $roundId
     * @return string|null Null when RoundId not found
     */
    public function getRoundCode($roundId)
    {
        $this->_ensureRounds();
        $roundCode = null;

        if (array_key_exists($roundId, $this->_rounds) && array_key_exists('gro_code', $this->_rounds[$roundId])) {
            $roundCode = $this->_rounds[$roundId]['gro_code'];
        }

        return $roundCode;
    }

    /**
     * The start date of this track
     *
     * @return ?DateTimeInterface
     */
    public function getStartDate()
    {
        if (isset($this->_respTrackData['gr2t_start_date'])) {
            return DateTimeImmutable::createFromFormat(\Gems\Tracker::DB_DATETIME_FORMAT, $this->_respTrackData['gr2t_start_date'] );
        }
    }

    /**
     * Returns all the tokens in this track
     *
     * @param boolean $refresh When true, always reload
     * @return \Gems\Tracker\Token[]
     */
    public function getTokens($refresh = false)
    {
        if (! $this->_tokens || $refresh) {
            if ($refresh) {
                $this->_firstToken = null;
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
     * @return string Check if track is active
     */
    public function getTrackActive()
    {
        if (!isset($this->_respTrackData['gtr_active'])) {
            $this->_ensureTrackData();
        }

        return (bool)$this->_respTrackData['gtr_active'];
    }

    /**
     *
     * @return \Gems\Tracker\Engine\TrackEngineInterface
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
     * @return string Name of the track
     */
    public function getTrackName()
    {
        if (!isset($this->_respTrackData['gtr_track_name'])) {
            $this->_ensureTrackData();
        }

        return $this->_respTrackData['gtr_track_name'];
    }

    /**
     * Find out if there are before field update events and delegate to the event if needed
     *
     * @param array $fieldData fieldname => value + codename => value
     * @return array Of changed fields. Codename using items overwrite any key using items
     */
    public function handleBeforeFieldUpdate(array $fieldData)
    {
        static $running = array();

        // Process any events
        $trackEngine = $this->getTrackEngine();

        if (! $trackEngine) {
            return array();
        }

        $beforeFieldUpdateEvent = $trackEngine->getFieldBeforeUpdateEvent();

        $eventName = 'gems.track.before-field-update';

        if (! $beforeFieldUpdateEvent && !$this->event->hasListeners($eventName)) {
            return [];
        }

        if (isset($running[$this->_respTrackId])) {
            throw new \Gems\Exception(sprintf(
                "Nested calls to '%s' track before field update event are not allowed.",
                $trackEngine->getName()
            ));
        }
        $running[$this->_respTrackId] = true;

        if ($beforeFieldUpdateEvent) {
            $eventFunction = function (RespondentTrackFieldEvent $event) use ($beforeFieldUpdateEvent) {
                $respondentTrack = $event->getRespondentTrack();
                $fieldData = $event->getFieldData();

                try {
                    $changed = $beforeFieldUpdateEvent->prepareFieldUpdate($fieldData, $respondentTrack);
                    $event->addChanged($changed);
                    $fieldData = $changed + $fieldData;
                    $event->setFieldData($fieldData);
                } catch (\Exception $e) {
                    throw new \Exception('Event: ' . $beforeFieldUpdateEvent->getEventName() . '. ' . $e->getMessage());
                }
            };
            $this->event->addListener($eventName, $eventFunction, 100);
        }

        $respondentTrackFieldEvent = new RespondentTrackFieldEvent($this, $this->currentUser->getUserId());
        $respondentTrackFieldEvent->setFieldData($fieldData);
        $this->event->dispatch($respondentTrackFieldEvent, $eventName);

        unset($running[$this->_respTrackId]);

        return $respondentTrackFieldEvent->getChanged();
    }

    /**
     * Find out if there are field update events and delegate to the event if needed
     *
     * @param array $fieldData Optional field data to use instead of data currently stored at object
     * @return void
     */
    public function handleFieldUpdate(array $oldFieldData = null)
    {
        static $running = array();

        // Process any events
        $trackEngine = $this->getTrackEngine();

        if (! $trackEngine) {
            return;
        }

        $fieldUpdateEvent = $trackEngine->getFieldUpdateEvent();

        $eventName = 'gems.track.field-update';

        if (! $fieldUpdateEvent && !$this->event->hasListeners($eventName)) {
            return;
        }

        if (isset($running[$this->_respTrackId])) {
            throw new \Gems\Exception(sprintf(
                    "Nested calls to '%s' track after field update event are not allowed.",
                    $trackEngine->getName()
                    ));
        }
        $running[$this->_respTrackId] = true;

        if ($fieldUpdateEvent) {
            $eventFunction = function (RespondentTrackFieldUpdateEvent $event) use ($fieldUpdateEvent) {
                $respondentTrack = $event->getRespondentTrack();
                $userId = $event->getUserId();

                try {
                    $fieldUpdateEvent->processFieldUpdate($respondentTrack, $userId);
                } catch (\Exception $e) {
                    throw new \Exception('Event: ' . $fieldUpdateEvent->getEventName() . '. ' . $e->getMessage());
                }
            };
            $this->event->addListener($eventName, $eventFunction, 100);
        }

        $respondentTrackEvent = new RespondentTrackFieldUpdateEvent($this, $this->currentUser->getUserId(), $oldFieldData);
        $this->event->dispatch($respondentTrackEvent, $eventName);

        unset($running[$this->_respTrackId]);
    }

    /**
     *
     * @param mixed $token
     * @param int $userId The current user
     * @return int The number of tokens changed by this event
     */
    public function handleRoundCompletion($token, $userId)
    {
        if (! $token instanceof \Gems\Tracker\Token) {
            $token = $this->tracker->getToken($token);
        }
        // \MUtil\EchoOut\EchoOut::track($token->getRawAnswers());

        // Store the current token as startpoint if it is the first changed token
        if ($this->_checkStart) {
            if ($this->_checkStart->getRoundId() > $token->getRoundId()) {
                // Replace current token
                $this->_checkStart = $token;
            }
        } else {
            $this->_checkStart = $token;
        }

        $eventName = 'gems.round.changed';

        // Process any events
        if ($roundChangedEvent = $this->getTrackEngine()->getRoundChangedEvent($token->getRoundId())) {
            $eventFunction = function (TokenEvent $event) use ($roundChangedEvent, $userId) {
                $token = $event->getToken();
                $respondentTrack = $token->getRespondentTrack();
                try {
                    $changed = $roundChangedEvent->processChangedRound($token, $respondentTrack, $userId);
                    if (is_array($changed)) {
                        $event->addChanged($changed);
                    }
                } catch (\Exception $e) {
                    throw new \Exception('Event: ' . $roundChangedEvent->getEventName() . '. ' . $e->getMessage());
                }
            };
            $this->event->addListener($eventName, $eventFunction, 100);
        }

        $tokenEvent = new TokenEvent($token);
        try {
            $this->event->dispatch($tokenEvent, $eventName);
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                "Round changed after event error for token %s on survey '%s': %s",
                $token->getTokenId(),
                $token->getSurveyName(),
                $e->getMessage()
            ));
        }

        return 0;
    }

    /**
     * Find out if there are track calculation events and delegate to the event if needed
     *
     * @param int $userId
     */
    public function handleTrackCalculation($userId)
    {
        // Process any events
        $trackEngine = $this->getTrackEngine();

        // Places here instead of only in handle field update so it will run on new tracks too
        $this->assignTokensToRelations();

        if ($event = $trackEngine->getTrackCalculationEvent()) {
            return $event->processTrackCalculation($this, $userId);
        }

        return 0;
    }

    /**
     * Find out if there are track completion events and delegate to the event if needed
     *
     * @param array $values The values changed before entering this event
     * @param int $userId
     */
    public function handleTrackCompletion(&$values, $userId)
    {
        // Process any events
        $trackEngine = $this->getTrackEngine();

        if ($event = $trackEngine->getTrackCompletionEvent()) {
            $event->processTrackCompletion($this, $values, $userId);
        }
    }

    /**
     *
     * @return boolean
     */
    public function hasSuccesCode()
    {
        return $this->getReceptionCode()->isSuccess();
    }

    /**
     * Are there still unanswered rounds
     *
     * @return boolean
     */
    public function isOpen()
    {
        if (isset($this->_respTrackData['gr2t_count'], $this->_respTrackData['gr2t_completed'])) {
            return $this->_respTrackData['gr2t_count'] > $this->_respTrackData['gr2t_completed'];
        }
        return true;
    }

    /**
     * Can mails be sent for this track?
     *
     * Cascades to the respondent mailable setting too
     *
     * @return boolean
     */
    public function isMailable()
    {
        if (!array_key_exists('gr2t_mailable', $this->_respTrackData)) {
            $this->refresh();
        }

        $mailCode = $this->util->getDbLookup()->getRespondentTrackNoMailCodeValue();
        return $this->_respTrackData['gr2t_mailable'] > $mailCode && $this->getRespondent()->isMailable();
    }

    /**
     * Processes the field values and and changes them as required
     *
     * @param array $newFieldData The new field values, may be partial, field set by code overwrite field set by key
     * @return array The processed data in the format key1 => val1, code1 => val1, key2 => val2
     */
    public function processFieldsBeforeSave(array $newFieldData)
    {
        $trackEngine = $this->getTrackEngine();

        if (! $trackEngine) {
            return $newFieldData;
        }

        // \MUtil\EchoOut\EchoOut::track($newFieldData);
        $step1Data = $this->_mergeFieldValues($newFieldData, $this->getFieldData(), $trackEngine);
        $step2Data = $trackEngine->getFieldsDefinition()->processBeforeSave($step1Data, $this->_respTrackData);
        $step3Data = $this->handleBeforeFieldUpdate($this->_mergeFieldValues($step2Data, $step1Data, $trackEngine));

        if ($step3Data) {
            return $this->_mergeFieldValues($step3Data, $step2Data, $trackEngine);
        } else {
            return $step2Data;
        }
    }

    /**
     * Refresh the fields (to reflect any changed appointments)
     *
     * @param boolean $trackEngine Set to true when changed
     * @return int The number of tokens changed as a result of this update
     */
    public function recalculateFields(&$fieldsChanged = false)
    {
        $fieldDef  = $this->getTrackEngine()->getFieldsDefinition();

        $this->_ensureFieldData();
        $oldFieldData     = $this->_fieldData;
        $this->_fieldData = $this->processFieldsBeforeSave($this->_fieldData);
        $fieldsChanged    = $fieldDef->changed;

        $changes       = $fieldDef->saveFields($this->_respTrackId, $this->_fieldData);
        $fieldsChanged = (boolean) $changes;

        $this->handleFieldUpdate($oldFieldData);

        $info = $fieldDef->calculateFieldsInfo($this->_fieldData);
        if ($info != $this->_respTrackData['gr2t_track_info']) {
            $this->_updateTrack(array('gr2t_track_info' => $info), $this->currentUser->getUserId());
        }

        // We always update the fields, but recalculate the token dates
        // only when this respondent track is still running.
        if ($this->hasSuccesCode() && $this->isOpen()) {
            return $this->checkTrackTokens($this->currentUser->getUserId());
        }
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return \Gems\Tracker\RespondentTrack (continuation pattern)
     */
    public function refresh(array $gemsData = null)
    {
        if (is_array($gemsData)) {
            $this->_respTrackData = $this->translateTables($this->_tablesForTranslations, $gemsData) + $this->_respTrackData;
        } else {
            $this->_respTrackData = $this->fetchTranslatedRow('gems__respondent2track', 'gr2t_id_respondent_track', $this->_respTrackId);
        }
        if ($this->_respTrackData && $this->currentUser instanceof \Gems\User\User) {
            $this->_respTrackData = $this->currentUser->applyGroupMask($this->_respTrackData);
        }

        $this->_ensureFieldData(true);

        $this->_rounds = null;
        $this->_tokens = null;

        return $this;
    }

    /**
     * Restores tokens for this track, when the reception code matches the given $oldCode
     *
     * Used when restoring a respondent or this tracks, and the restore tracks/tokens
     * box is checked.
     *
     * @param \Gems\Util\ReceptionCode $oldCode The old reception code
     * @param \Gems\Util\ReceptionCode $newCode the new reception code
     * @return int  The number of restored tokens
     */
    public function restoreTokens(\Gems\Util\ReceptionCode $oldCode, \Gems\Util\ReceptionCode $newCode) {
        $count = 0;

        if (!$oldCode->isSuccess() && $newCode->isSuccess()) {
            foreach ($this->getTokens() as $token) {
                if ($token instanceof \Gems\Tracker\Token) {
                    if ($oldCode->getCode() === $token->getReceptionCode()->getCode()) {
                        $token->setReceptionCode($newCode, null, $this->currentUser->getUserId());
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Saves the field data for the respondent track id.
     *
     * @param array $fieldData The values to save, only the key is used, not the code
     * @return int The number of changed fields
     */
    public function saveFields(array $fieldData)
    {
        $trackEngine = $this->getTrackEngine();

        if (! $trackEngine) {
            return 0;
        }

        //\MUtil\EchoOut\EchoOut::track($fieldData);
        $oldFieldData     = $this->getFieldData();
        $this->_fieldData = $this->_mergeFieldValues($fieldData, $oldFieldData, $trackEngine);

        $changed = $trackEngine->getFieldsDefinition()->saveFields($this->_respTrackId, $this->_fieldData);

        if ($changed) {
            $this->_ensureFieldData(true);
        }

        $this->handleFieldUpdate($oldFieldData);

        return $changed;
    }

    /**
     * Set the end date for this respondent track.
     *
     * @param mixed $endDate The new end date for this track
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setEndDate($endDate, $userId)
    {
        $values['gr2t_end_date']        = $endDate;
        $values['gr2t_end_date_manual'] = 1;

        return $this->_updateTrack($values, $userId);
    }

    /**
     * Update one or more values for this track's fields.
     *
     * Return the complete set of fielddata
     *
     * @param array $newFieldData The new field values, may be partial, field set by code overwrite field set by key
     * @return array
     */
    public function setFieldData($newFieldData)
    {
        $trackEngine = $this->getTrackEngine();

        if (! $trackEngine) {
            return $newFieldData;
        }

        $this->_fieldData = $this->processFieldsBeforeSave($newFieldData);
        $changes          = $this->saveFields(array());

        if ($changes) {
            $info = $trackEngine->getFieldsDefinition()->calculateFieldsInfo($this->_fieldData);

            if ($info != $this->_respTrackData['gr2t_track_info']) {
                $this->_updateTrack(array('gr2t_track_info' => $info), $this->currentUser->getUserId());
            }
        }

        return $this->_fieldData;
    }

    /**
     * Set the mailability for this respondent track.
     *
     * @param boolean $mailable Should this respondent track be st to mailable
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setMailable($mailable)
    {
        $mailCodes = array_keys($this->util->getDbLookup()->getRespondentTrackMailCodes());
        $values['gr2t_mailable'] = $mailable ? max($mailCodes) : min($mailCodes);

        return $this->_updateTrack($values, $this->currentUser->getUserId());
    }

    /**
     * Set the reception code for this respondent track and make sure the
     * necessary cascade to the tokens and thus the source takes place.
     *
     * @param string $code The new (non-success) reception code or a \Gems\Util\ReceptionCode object
     * @param string $comment Comment for tokens. False values leave value unchanged
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setReceptionCode($code, $comment, $userId)
    {
        // Make sure it is a \Gems\Util\ReceptionCode object
        if (! $code instanceof \Gems\Util\ReceptionCode) {
            $code = $this->util->getReceptionCode($code);
        }
        $changed = 0;

        // Apply this code both only when it is a track code.
        // Patient level codes are just cascaded to the tokens.
        //
        // The exception is of course when the exiting values must
        // be overwritten, e.g. when cooperation is retracted.
        if ($code->isForTracks() || $code->isOverwriter()) {
            $values['gr2t_reception_code'] = $code->getCode();
        }

        $values['gr2t_comment'] = $comment;

        $changed = $this->_updateTrack($values, $userId);

        // Stopcodes have a different logic.
        if ($code->isStopCode()) {
            // Cascade stop to unanswered tokens
            foreach ($this->getTokens() as $token) {
                if ($token->getReceptionCode()->isSuccess() && (! $token->isCompleted())) {
                    $changed += $token->setReceptionCode($code, $comment, $userId);
                }
            }
            $changed = max($changed, 1);

            // Update token count / completion
            $this->_checkTrackCount($userId);

        } elseif (! $code->isSuccess()) {
            // Cascade code to tokens
            foreach ($this->getTokens() as $token) {
                if ($token->getReceptionCode()->isSuccess()) {
                    $token->setReceptionCode($code, $comment, $userId);
                }
            }
        }

        return $changed;
    }
}
