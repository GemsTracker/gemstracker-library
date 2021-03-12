<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Tracker\Model\AddTrackFieldsTransformer;
use Gems\Tracker\Model\RoundModel;
use Gems\Tracker\Round;
use MUtil\Model\Dependency\DependencyInterface;

/**
 * Utility class containing functions used by most track engines.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class Gems_Tracker_Engine_TrackEngineAbstract extends \MUtil_Translate_TranslateableAbstract implements \Gems_Tracker_Engine_TrackEngineInterface
{
    /**
     * Stores how the fields are define for this track
     *
     * @var \Gems\Tracker\Engine\FieldsDefinition;
     */
    protected $_fieldsDefinition;

    /**
     *
     * @var array of rounds objects, initiated at need
     */
    protected $_roundObjects;

    /**
     *
     * @var array of rounds data
     */
    protected $_rounds;

    /**
     *
     * @var array
     */
    protected $_trackData;

    /**
     *
     * @var int
     */
    protected $_trackId;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Events
     */
    protected $events;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

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
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     *
     * @param array $trackData array containing track record
     */
    public function __construct($trackData)
    {
        $this->_trackData = $trackData;
        $this->_trackId   = $trackData['gtr_id_track'];
    }

    /**
     * Loads the rounds data for this type of track engine.
     *
     * Can be overruled by sub classes.
     */
    protected function _ensureRounds()
    {
        if (! is_array($this->_rounds)) {
            $roundSelect = $this->db->select();
            $roundSelect->from('gems__rounds')
                ->where('gro_id_track = ?', $this->_trackId)
                ->order('gro_id_order');

            // \MUtil_Echo::track((string) $roundSelect, $this->_trackId);

            $this->_rounds  = array();
            foreach ($roundSelect->query()->fetchAll() as $round) {
                $this->_rounds[$round['gro_id_round']] = $round;
            }
        }
    }

    /**
     * Returns a list of available icons under 'htdocs/pulse/icons'
     * @return string[]
     */
    protected function _getAvailableIcons()
    {
        $dir = GEMS_WEB_DIR . '/gems-responsive/images/icons';
        if (!file_exists($dir)) {
            $dir = GEMS_WEB_DIR . '/gems/icons';
        }

        $icons = array();

        if (file_exists($dir)) {
            $iterator = new DirectoryIterator($dir);

            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    // $icons[$fileinfo->getFilename()] = $fileinfo->getFilename();
                    $filename = $fileinfo->getFilename();
                    $url = $this->view->baseUrl() . \MUtil_Html_ImgElement::getImageDir($filename);
                    $icons[$fileinfo->getFilename()] = \MUtil_Html::create('span', $filename, array('data-class' => 'avatar', 'data-style' => 'background-image: url(' . $url . $filename . ');'));
                }
            }
        }

        ksort($icons);  // Sort by key

        return $icons;
    }

    /**
     * Update the track, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    private function _update(array $values, $userId)
    {
        if ($this->tracker->filterChangesOnly($this->_trackData, $values)) {

            if (\Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_trackData[$key] . ' => ' . $val . "\n";
                }
                \MUtil_Echo::r($echo, 'Updated values for ' . $this->_trackId);
            }

            if (! isset($values['gto_changed'])) {
                $values['gtr_changed'] = new \MUtil_Db_Expr_CurrentTimestamp();
            }
            if (! isset($values['gtr_changed_by'])) {
                $values['gtr_changed_by'] = $userId;
            }

            // Update values in this object
            $this->_trackData = $values + $this->_trackData;

            // return 1;
            return $this->db->update('gems__tracks', $values, array('gtr_id_track = ?' => $this->_trackId));

        } else {
            return 0;
        }
    }

    /**
     * Integrate field loading en showing and editing
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param boolean $addDependency True when editing, can be false in all other cases
     * @param string $respTrackId Optional Database column name where Respondent Track Id is set
     * @return \Gems_Tracker_Engine_TrackEngineAbstract
     */
    public function addFieldsToModel(\MUtil_Model_ModelAbstract $model, $addDependency = true, $respTrackId = false)
    {
        if ($this->_fieldsDefinition->exists) {
            // Add the data to the load / save
            $transformer = new AddTrackFieldsTransformer($this->loader, $this->_fieldsDefinition, $respTrackId);
            $model->addTransformer($transformer);

            if ($addDependency) {
                $dependencies = $this->_fieldsDefinition->getDataModelDependencies($model);

                foreach ($dependencies as $dependency) {
                    if ($dependency instanceof DependencyInterface) {
                        $model->addDependency($dependency);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Creates all tokens that should exist, but do not exist
     *
     * NOTE: When overruling this function you should not create tokens because they
     * were deleted by the user
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens created by this code
     */
    protected function addNewTokens(\Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        $orgId       = $respTrack->getOrganizationId();
        $respId      = $respTrack->getRespondentId();
        $respTrackId = $respTrack->getRespondentTrackId();

        // $this->t

        $sql = "SELECT gro_id_round, gro_id_survey, gro_id_order, gro_icon_file, gro_round_description
            FROM gems__rounds
            WHERE gro_id_track = ? AND
                gro_active = 1 AND
                gro_id_round NOT IN (SELECT gto_id_round FROM gems__tokens WHERE gto_id_respondent_track = ?) AND
                (gro_organizations IS NULL OR gro_organizations LIKE CONCAT('%|',?,'|%'))
            ORDER BY gro_id_order";

        $newRounds = $this->db->fetchAll($sql, array($this->_trackId, $respTrackId, $orgId));

        $this->db->beginTransaction();
        foreach ($newRounds as $round) {

            $values = array();

            // From the respondent track
            $values['gto_id_respondent_track'] = $respTrackId;
            $values['gto_id_respondent']       = $respId;
            $values['gto_id_organization']     = $orgId;
            $values['gto_id_track']            = $this->_trackId;

            // From the rounds
            $values['gto_id_round']          = $round['gro_id_round'];
            $values['gto_id_survey']         = $round['gro_id_survey'];
            $values['gto_round_order']       = $round['gro_id_order'];
            $values['gto_icon_file']         = $round['gro_icon_file'];
            $values['gto_round_description'] = $round['gro_round_description'];

            // All other values are not changed by this query and get the default DB value on insertion

            $this->tracker->createToken($values, $userId);
        }
        $this->db->commit();

        return count($newRounds);
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->_fieldsDefinition = $this->tracker->createTrackClass('Engine\\FieldsDefinition', $this->_trackId);
    }

    /**
     * Set menu parameters from this track engine
     *
     * @param \Gems_Menu_ParameterSource $source
     * @return \Gems_Tracker_Engine_TrackEngineInterface (continuation pattern)
     */
    public function applyToMenuSource(\Gems_Menu_ParameterSource $source)
    {
        $source->setTrackId($this->_trackId);
        $source->offsetSet('gtr_active', isset($this->_trackData['gtr_active']) ? $this->_trackData['gtr_active'] : 0);
        return $this;
    }

    /**
     * Calculate the track info from the fields
     *
     * @param array $data The values to save
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo(array $data)
    {
        return $this->_fieldsDefinition->calculateFieldsInfo($data);
    }

    /**
     * Calculate the number of active rounds in this track from the database.
     *
     * @return int The number of rounds in this track.
     */
    public function calculateRoundCount()
    {
        return $this->db->fetchOne("SELECT COUNT(*) FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ?", $this->_trackId);
    }

    /**
     * Checks all existing tokens and updates any changes to the original rounds (when necessary)
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    protected function checkExistingRoundsFor(\Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        // FOR TESTING: sqlite can not de update and joins, so when testing just return zero for now
        if (\Zend_Session::$_unitTestEnabled === true) return 0;
        // @@ TODO Make this testable and not db dependent anymore

        // Quote here, I like to keep bound parameters limited to the WHERE
        // Besides, these statements are not order dependent while parameters are and do not repeat
        $qOrgId   = $this->db->quote($respTrack->getOrganizationId());
        $qRespId  = $this->db->quote($respTrack->getRespondentId());
        $qTrackId = $this->db->quote($this->_trackId);
        $qUserId  = $this->db->quote($userId);

        $respTrackId = $respTrack->getRespondentTrackId();

        $sql = "UPDATE gems__tokens, gems__rounds, gems__reception_codes
            SET gto_id_respondent = $qRespId,
                gto_id_organization = $qOrgId,
                gto_id_track = $qTrackId,
                gto_id_survey = CASE WHEN gto_start_time IS NULL AND grc_success = 1 THEN gro_id_survey ELSE gto_id_survey END,
                gto_round_order = gro_id_order,
                gto_icon_file = gro_icon_file,
                gto_round_description = gro_round_description,
                gto_changed = CURRENT_TIMESTAMP,
                gto_changed_by = $qUserId
            WHERE gto_id_round = gro_id_round AND
                gto_reception_code = grc_id_reception_code AND
                gto_id_round != 0 AND
                gro_active = 1 AND
                (
                    gto_id_respondent != $qRespId OR
                    gto_id_organization != $qOrgId OR
                    gto_id_track != $qTrackId OR
                    gto_id_survey != CASE WHEN gto_start_time IS NULL AND grc_success = 1 THEN gro_id_survey ELSE gto_id_survey END OR
                    gto_round_order != gro_id_order OR
                    (gto_round_order IS NULL AND gro_id_order IS NOT NULL) OR
                    (gto_round_order IS NOT NULL AND gro_id_order IS NULL) OR
                    gto_icon_file != gro_icon_file OR
                    (gto_icon_file IS NULL AND gro_icon_file IS NOT NULL) OR
                    (gto_icon_file IS NOT NULL AND gro_icon_file IS NULL) OR
                    gto_round_description != gro_round_description OR
                    (gto_round_description IS NULL AND gro_round_description IS NOT NULL) OR
                    (gto_round_description IS NOT NULL AND gro_round_description IS NULL)
                ) AND
                    gto_id_respondent_track = ?";

        $stmt = $this->db->query($sql, array($respTrackId));

        return $stmt->rowCount();
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db) {
            $this->_ensureRounds();
        }

        return (boolean) $this->db;
    }

    /**
     * Check for the existence of all tokens and create them otherwise
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems_Task_TaskRunnerBatch $changes batch for counters
     */
    public function checkRoundsFor(\Gems_Tracker_RespondentTrack $respTrack, $userId, \Gems_Task_TaskRunnerBatch $batch = null)
    {
        if (null === $batch) {
            $batch = new \Gems_Task_TaskRunnerBatch('tmptrack' . $respTrack->getRespondentTrackId());
        }
        // Step one: update existing tokens
        $i = $batch->addToCounter('roundChangeUpdates', $this->checkExistingRoundsFor($respTrack, $userId));
        $batch->setMessage('roundChangeUpdates', sprintf($this->_('Round changes propagated to %d tokens.'), $i));

        // Step two: deactivate inactive rounds
        $i = $batch->addToCounter('deletedTokens', $this->removeInactiveRounds($respTrack, $userId));
        $batch->setMessage('deletedTokens', sprintf($this->_('%d tokens deleted by round changes.'), $i));

        // Step three: create lacking tokens
        $i = $batch->addToCounter('createdTokens', $this->addNewTokens($respTrack, $userId));
        $batch->setMessage('createdTokens', sprintf($this->_('%d tokens created to by round changes.'), $i));

        // Step four: set the dates and times
        //$changed = $this->checkTokensFromStart($respTrack, $userId);
        $changed = $respTrack->checkTrackTokens($userId);
        $ica = $batch->addToCounter('tokenDateCauses', $changed ? 1 : 0);
        $ich = $batch->addToCounter('tokenDateChanges', $changed);
        $batch->setMessage('tokenDateChanges', sprintf($this->_('%2$d token date changes in %1$d tracks.'), $ica, $ich));

        $i = $batch->addToCounter('checkedRespondentTracks');
        $batch->setMessage('checkedRespondentTracks', sprintf($this->_('Checked %d tracks.'), $i));
    }

    /**
     * Convert a TrackEngine instance to a TrackEngine of another type.
     *
     * @see getConversionTargets()
     *
     * @param type $conversionTargetClass
     */
    public function convertTo($conversionTargetClass)
    {
        throw new \Gems_Exception_Coding(sprintf($this->_('%s track engines cannot be converted to %s track engines.'), $this->getName(), $conversionTargetClass));
    }

    /**
     * Copy a track and all it's related data (rounds/fields etc)
     *
     * @param inte $oldTrackId  The id of the track to copy
     * @return int              The id of the copied track
     */
    public function copyTrack($oldTrackId)
    {
        $trackModel = $this->tracker->getTrackModel();

        $roundModel = $this->getRoundModel(true, 'rounds');
        $fieldModel = $this->getFieldsMaintenanceModel();

        // First load the track
        $trackModel->applyParameters(array('id' => $oldTrackId));
        $track = $trackModel->loadFirst();

        // Create an empty track
        $newTrack = $trackModel->loadNew();
        unset($track['gtr_id_track'], $track['gtr_changed'], $track['gtr_changed_by'], $track['gtr_created'], $track['gtr_created_by']);
        $track['gtr_track_name'] .= $this->_(' - Copy');
        $newTrack = $track + $newTrack;
        // Now save (not done yet)
        $savedValues = $trackModel->save($newTrack);
        $newTrackId = $savedValues['gtr_id_track'];

        // Now copy the fields
        $fieldModel->applyParameters(array('id' => $oldTrackId));
        $fields = $fieldModel->load();

        if ($fields) {
            $oldIds = array();
            $numFields = count($fields);
            $newFields = $fieldModel->loadNew($numFields);
            foreach ($newFields as $idx => $newField) {
                $field = $fields[$idx];
                $oldIds[$idx] = $field['gtf_id_field'];
                unset($field['gtf_id_field'], $field['gtf_changed'], $field['gtf_changed_by'], $field['gtf_created'], $field['gtf_created_by']);
                $field['gtf_id_track'] = $newTrackId;
                $newFields[$idx] = $field + $newFields[$idx];
            }
            // Now save (not done yet)
            $savedValues = $fieldModel->saveAll($newFields);
            foreach($savedValues as $idx => $field) {
                $oldNewFieldMap[$oldIds[$idx]] = $field['gtf_id_field'];
            }
        } else {
            $numFields = 0;
        }

        // Now copy the rounds and map the gro_id_relation to the right field
        $roundModel->applyParameters(array('id' => $oldTrackId));
        $rounds = $roundModel->load();

        if ($rounds) {
            $numRounds = count($rounds);
            $newRounds = $roundModel->loadNew($numRounds);
            foreach ($newRounds as $idx => $newRound) {
                $round = $rounds[$idx];
                unset($round['gro_id_round'], $round['gro_changed'], $round['gro_changed_by'], $round['gro_created'], $round['gro_created_by']);
                $round['gro_id_track'] = $newTrackId;
                if (array_key_exists('gro_id_relationfield', $round) && $round['gro_id_relationfield']>0) {
                    $round['gro_id_relationfield'] = $oldNewFieldMap[$round['gro_id_relationfield']];
                }
                $newRounds[$idx] = $round + $newRounds[$idx];
            }
            // Now save (not done yet)
            $savedValues = $roundModel->saveAll($newRounds);
        } else {
            $numRounds = 0;
        }

        //MUtil_Echo::track($track, $copy);
        //MUtil_Echo::track($rounds, $newRounds);
        //MUtil_Echo::track($fields, $newFields);
        Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage(sprintf($this->_('Copied track, including %s round(s) and %s field(s).'), $numRounds, $numFields));

        return $newTrackId;
    }

    /**
     * Create model for rounds. Allowes overriding by sub classes.
     *
     * @return \Gems_Model_JoinModel
     */
    protected function createRoundModel()
    {
        $roundModel = new RoundModel();
        $roundModel->answerRegistryRequest('db', $this->db);
        return $roundModel;
    }

    /**
     * Returns a list of classnames this track engine can be converted into.
     *
     * Should always contain at least the class itself.
     *
     * @see convertTo()
     *
     * @param array $options The track engine class options available in as a "track engine class names" => "descriptions" array
     * @return array Filter or adaptation of $options
     */
    public function getConversionTargets(array $options)
    {
        $classParts = explode('_', get_class($this));
        $className  = end($classParts);

        return array($className => $options[$className]);
    }

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return \Gems\Event\TrackBeforeFieldUpdateEventInterface | null
     */
    public function getFieldBeforeUpdateEvent()
    {
        if (isset($this->_trackData['gtr_beforefieldupdate_event']) && $this->_trackData['gtr_beforefieldupdate_event']) {
            return $this->events->loadBeforeTrackFieldUpdateEvent($this->_trackData['gtr_beforefieldupdate_event']);
        }
    }

    /**
     * Returns an array of the fields in this track key / value are id / code
     *
     * @return array fieldid => fieldcode With null when no fieldcode
     */
    public function getFieldCodes()
    {
        return $this->_fieldsDefinition->getFieldCodes();
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / field name
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldNames()
    {
        return $this->_fieldsDefinition->getFieldNames();
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / code
     *
     * @return array fieldid => fieldcode
     * @deprecated since 1.8.2 Replaced with getFieldCodes()
     */
    public function getFields()
    {
        return $this->_fieldsDefinition->getFieldCodes();
    }

    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsData($respTrackId)
    {
        return $this->_fieldsDefinition->getFieldsDataFor($respTrackId);
    }

    /**
     * Get the storage model for field values
     *
     * @return \Gems\Tracker\Model\FieldDataModel
     */
    public function getFieldsDataStorageModel()
    {
        return $this->_fieldsDefinition->getDataStorageModel();
    }

    /**
     * Returns the field definition for the track enige.
     *
     * @return \Gems\Tracker\Engine\FieldsDefinition;
     */
    public function getFieldsDefinition()
    {
        return $this->_fieldsDefinition;
    }

    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \Gems\Tracker\Model\FieldMaintenanceModel
     */
    public function getFieldsMaintenanceModel($detailed = false, $action = 'index')
    {
        return $this->_fieldsDefinition->getMaintenanceModel($detailed, $action);
    }

    /**
     * Returns an array name => code of all the fields of the type specified
     *
     * @param string $fieldType
     * @return array name => code
     */
    public function getFieldsOfType($fieldType)
    {
        return $this->_fieldsDefinition->getFieldCodesOfType($fieldType);
    }

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return \Gems_Event_TrackFieldUpdateEventInterface | null
     */
    public function getFieldUpdateEvent()
    {
        if (isset($this->_trackData['gtr_fieldupdate_event']) && $this->_trackData['gtr_fieldupdate_event']) {
            return $this->events->loadTrackFieldUpdateEvent($this->_trackData['gtr_fieldupdate_event']);
        }
    }

    /**
     * Get the round id of the first round
     *
     * @return int Gems id of first round
     */
    public function getFirstRoundId()
    {
        $this->_ensureRounds();

        reset($this->_rounds);

        return key($this->_rounds);
    }

    /**
     * Look up the round id for the next round
     *
     * @param int $roundId  Gems round id
     * @return int Gems round id
     */
    public function getNextRoundId($roundId)
    {
       $this->_ensureRounds();

       if ($this->_rounds && $roundId) {
           $next = false;
           foreach ($this->_rounds as $currentRoundId => $round) {
               if ($next) {
                   return $currentRoundId;
               }
               if ($currentRoundId == $roundId) {
                   $next = true;
               }
           }

           return null;

       } elseif ($this->_rounds) {
           end($this->_rounds);
           return key($this->_rounds);
       }
    }

    /**
     * Look up the round id for the previous round
     *
     * @param int $roundId  Gems round id
     * @param int $roundOrder Optional extra round order, for when the current round may have changed.
     * @return int Gems round id
     */
    public function getPreviousRoundId($roundId, $roundOrder = null)
    {
       $this->_ensureRounds();

       if ($this->_rounds && $roundId) {
           $returnId = null;
           foreach ($this->_rounds as $currentRoundId => $round) {
               if (($currentRoundId == $roundId) || ($roundOrder && ($round['gro_id_order'] >= $roundOrder))) {
                   // Null is returned when querying this function with the first round id.
                   return $returnId;
               }
               $returnId = $currentRoundId;
           }


           throw new \Gems_Exception(sprintf($this->_('Requested non existing round with id %d.'), $roundId));

       } elseif ($this->_rounds) {
            end($this->_rounds);
            $key = key($this->_rounds);
            if (empty($key) && !is_null($key)) {
                // The last round (with empty index) is the current round, step back one more round
                prev($this->_rounds);
            }
            return key($this->_rounds);
        }
    }

    /**
     * Get the round object
     *
     * @param int $roundId  Gems round id
     * @return \Gems\Tracker\Round
     */
    public function getRound($roundId)
    {
        $this->_ensureRounds();

        if (! isset($this->_rounds[$roundId])) {
            return null;
        }
        if (! isset($this->_roundObjects[$roundId])) {
            $this->_roundObjects[$roundId] = $this->tracker->createTrackClass('Round', $this->_rounds[$roundId]);
        }
        return $this->_roundObjects[$roundId];
    }

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param \Gems_Tracker_Token $token
     * @return array Of snippet names
     */
    public function getRoundAnswerSnippets(\Gems_Tracker_Token $token)
    {
        $this->_ensureRounds();
        $roundId = $token->getRoundId();

        if (isset($this->_rounds[$roundId]['gro_display_event']) && $this->_rounds[$roundId]['gro_display_event']) {
            $event = $this->events->loadSurveyDisplayEvent($this->_rounds[$roundId]['gro_display_event']);

            return $event->getAnswerDisplaySnippets($token);
        }
    }

    /**
     * Return the Round Changed event name for this round
     *
     * @param int $roundId
     * @return \Gems_Event_RoundChangedEventInterface event instance or null
     */
    public function getRoundChangedEvent($roundId)
    {
        $this->_ensureRounds();

        if (isset($this->_rounds[$roundId]['gro_changed_event']) && $this->_rounds[$roundId]['gro_changed_event']) {
            return $this->events->loadRoundChangedEvent($this->_rounds[$roundId]['gro_changed_event']);
        }
    }

    /**
     * Get the defaults for a new round
     *
     * @return array Of fieldname => default
     */
    public function getRoundDefaults()
    {
        $this->_ensureRounds();

        if ($this->_rounds) {
            $defaults = end($this->_rounds);
            unset($defaults['gro_id_round'], $defaults['gro_id_survey']);

            $defaults['gro_id_order'] = $defaults['gro_id_order'] + 10;
        } else {
            // Rest of defaults come form model
            $defaults = array('gro_id_track' => $this->_trackId);
        }

        return $defaults;
    }

    /**
     * The round descriptions for this track
     *
     * @return array roundId => string
     */
    public function getRoundDescriptions()
    {
        $this->_ensureRounds();

        $output = array();
        foreach ($this->getRounds() as $roundId => $round) {
            if ($round instanceof Round) {
                $output[$roundId] = $round->getFullDescription();
            }
        }

        return $output;
    }

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundEditSnippetNames()
    {
        return array('Tracker\\Rounds\\EditRoundStepSnippet');
    }

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \MUtil_Model_ModelAbstract
     */
    public function getRoundModel($detailed, $action)
    {
        $model      = $this->createRoundModel();
        $translated = $this->util->getTranslated();

        // Set the keys to the parameters in use.
        $model->setKeys(array(\MUtil_Model::REQUEST_ID => 'gro_id_track', \Gems_Model::ROUND_ID => 'gro_id_round'));

        if ($detailed) {
            $model->set('gro_id_track',      'label', $this->_('Track'),
                    'elementClass', 'exhibitor',
                    'multiOptions', $this->util->getTrackData()->getAllTracks
                    );
        }

        $model->set('gro_id_survey',         'label', $this->_('Survey'),
                'multiOptions', $this->util->getTrackData()->getAllSurveysAndDescriptions()
                );
        $model->set('gro_icon_file',         'label', $this->_('Icon'));
        $model->set('gro_id_order',          'label', $this->_('Order'),
                'default', 10,
                'filters[digits]', 'Digits',
                'required', true,
                'validators[uni]', $model->createUniqueValidator(array('gro_id_order', 'gro_id_track'))
                );
        $model->set('gro_round_description', 'label', $this->_('Description'),
                'size', '30'
                ); //, 'minlength', 4, 'required', true);

        $list = $this->events->listRoundChangedEvents();
        if (count($list) > 1) {
            $model->set('gro_changed_event', 'label', $this->_('After change'),   'multiOptions', $list);
        }
        $list = $this->events->listSurveyDisplayEvents();
        if (count($list) > 1) {
            $model->set('gro_display_event', 'label', $this->_('Answer display'),
                    'multiOptions', $list
                    );
        }
        $model->set('gro_active',            'label', $this->_('Active'),
                'elementClass', 'checkbox',
                'multiOptions', $translated->getYesNo()
                );
        $model->setIfExists('gro_code',      'label', $this->_('Round code'),
                'description', $this->_('Optional code name to link the field to program code.'),
                'size', 10
                );

        $model->addColumn(
            "CASE WHEN gro_active = 1 THEN '' ELSE 'deleted' END",
            'row_class');
        $model->addColumn(
            "CASE WHEN gro_organizations IS NULL THEN 0 ELSE 1 END",
            'org_specific_round');
        $model->addColumn('gro_organizations', 'organizations');

        $model->set('organizations', 'label', $this->_('Organizations'),
                'elementClass', 'MultiCheckbox',
                'multiOptions', $this->util->getDbLookup()->getOrganizations(),
                'data-source', 'org_specific_round'
                );
        $tp = new \MUtil_Model_Type_ConcatenatedRow('|', $this->_(', '));
        $tp->apply($model, 'organizations');

        $model->set('gro_condition',
                'label', $this->_('Condition'),
                'elementClass', 'Select',
                'multiOptions', $this->loader->getConditions()->getConditionsFor(Gems\Conditions::ROUND_CONDITION)
                );

        $model->set('condition_display', 'label', $this->_('Condition help'), 'elementClass', 'Hidden', 'no_text_search', true, 'noSort', true);

        $model->addDependency('Condition\\RoundDependency');

        switch ($action) {
            case 'create':
                $this->_ensureRounds();

                if ($this->_rounds && ($round = end($this->_rounds))) {
                    $model->set('gro_id_order', 'default', $round['gro_id_order'] + 10);
                }

                // Intentional fall through
                // break;
            case 'edit':
            case 'show':
            	$model->set('gro_icon_file',
                        'multiOptions', $translated->getEmptyDropdownArray() + $this->_getAvailableIcons()
                        );
                $model->set('org_specific_round',
                        'label', $this->_('Organization specific round'),
                        'default', 0,
                        'multiOptions', $translated->getYesNo(),
                        'elementClass', 'radio'
                        );

                break;

            default:
                $model->set('gro_icon_file', 'formatFunction', array('MUtil_Html_ImgElement', 'imgFile'));
                break;

        }

        return $model;
    }

    /**
     * Get all the round objects
     *
     * @return array of roundId => \Gems\Tracker\Round
     */
    public function getRounds()
    {
        $this->_ensureRounds();

        foreach ($this->_rounds as $roundId => $roundData) {
            if (! isset($this->_roundObjects[$roundId])) {
                $this->_roundObjects[$roundId] = $this->tracker->createTrackClass('Round', $roundData);
            }
        }
        return $this->_roundObjects;
    }

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundShowSnippetNames()
    {
        return array('Tracker\\Rounds\\ShowRoundStepSnippet', 'Survey\\SurveyQuestionsSnippet');
    }

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return \Gems_Tracker_Model_StandardTokenModel
     */
    public function getTokenModel()
    {
        return $this->tracker->getTokenModel();
    }

    /**
     * Get the TrackCompletedEvent for this trackId
     *
     * @return \Gems_Event_TrackCalculationEventInterface | null
     */
    public function getTrackCalculationEvent()
    {
        if (isset($this->_trackData['gtr_calculation_event']) && $this->_trackData['gtr_calculation_event']) {
            return $this->events->loadTrackCalculationEvent($this->_trackData['gtr_calculation_event']);
        }
    }

    /**
     *
     * @return string The gems track code
     */
    public function getTrackCode()
    {
        return $this->_trackData['gtr_code'];
    }

    /**
     * Get the TrackCompletedEvent for this trackId
     *
     * @return \Gems_Event_TrackCompletedEventInterface|null
     */
    public function getTrackCompletionEvent()
    {
        if (isset($this->_trackData['gtr_completed_event']) && $this->_trackData['gtr_completed_event']) {
            return $this->events->loadTrackCompletionEvent($this->_trackData['gtr_completed_event']);
        }
    }

    /**
     *
     * @return int The track id
     */
    public function getTrackId()
    {
        return $this->_trackId;
    }

    /**
     *
     * @return string The gems track name
     */
    public function getTrackName()
    {
        return $this->_trackData['gtr_track_name'];
    }

    /**
     * Is the field an appointment type
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isAppointmentField($fieldName)
    {
        return $this->_fieldsDefinition->isAppointment($fieldName);
    }

    /**
     * Remove the unanswered tokens for inactive rounds.
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    protected function removeInactiveRounds(\Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        $qTrackId     = $this->db->quote($this->_trackId);
        $qRespTrackId = $this->db->quote($respTrack->getRespondentTrackId());
        $orgId        = $this->db->quote($respTrack->getOrganizationId());

        $where = "gto_start_time IS NULL AND
            gto_id_respondent_track = $qRespTrackId AND
            gto_id_round != 0 AND
            gto_id_round IN (SELECT gro_id_round
                    FROM gems__rounds
                    WHERE (gro_active = 0 OR gro_organizations NOT LIKE CONCAT('%|',$orgId,'|%')) AND
                        gro_id_track = $qTrackId)";

        return $this->db->delete('gems__tokens', $where);
    }

    /**
     * Updates the number of rounds in this track.
     *
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function updateRoundCount($userId)
    {
        $values['gtr_survey_rounds'] = $this->calculateRoundCount();

        return $this->_update($values, $userId);
    }
}
