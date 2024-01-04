<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Engine;

use Gems\Condition\ConditionLoader;
use Gems\Db\ResultFetcher;
use Gems\Exception;
use Gems\Model;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker;
use Gems\Tracker\Model\AddTrackFieldsTransformer;
use Gems\Tracker\Model\FieldDataModel;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Tracker\Model\RoundModel;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\Round;
use Gems\Tracker\Token;
use Gems\Tracker\TrackEvents;
use Gems\Tracker\TrackEvent\RoundChangedEventInterface;
use Gems\Tracker\TrackEvent\TrackBeforeFieldUpdateEventInterface;
use Gems\Tracker\TrackEvent\TrackCalculationEventInterface;
use Gems\Tracker\TrackEvent\TrackCompletedEventInterface;
use Gems\Tracker\TrackEvent\TrackFieldUpdateEventInterface;
use Gems\Translate\DbTranslationRepository;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use Mezzio\Session\SessionInterface;
use MUtil\EchoOut\EchoOut;
use MUtil\Model\Type\ConcatenatedRow;
use MUtil\Registry\TargetAbstract;
use MUtil\Translate\Translator;
use Zalt\Html\ImgElement;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Dependency\DependencyInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Model\Type\ConcatenatedType;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * Utility class containing functions used by most track engines.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class TrackEngineAbstract implements TrackEngineInterface
{
    /**
     * Stores how the fields are define for this track
     *
     * @var FieldsDefinition
     */
    protected FieldsDefinition $_fieldsDefinition;

    /**
     *
     * @var array of rounds objects, initiated at need
     */
    protected array $_roundObjects;

    /**
     *
     * @var array|null of rounds data
     */
    protected ?array $_rounds = null;

    /**
     *
     * @var array
     */
    protected array $_trackData;

    /**
     *
     * @var int
     */
    protected int $_trackId;

    /**
     *
     * @param array $trackData array containing track record
     */
    public function __construct(
        array $trackData,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly Tracker $tracker,
        protected readonly DbTranslationRepository $dbTranslationRepository,
        protected readonly ProjectOverloader $overloader,
        protected readonly Translator $translator,
        protected readonly TrackEvents $trackEvents,
        protected readonly ConditionLoader $conditionLoader,
        protected readonly TrackDataRepository $trackDataRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly Translated $translatedUtil,
    ) {
        $this->_trackData = $trackData;
        $this->_trackId   = $trackData['gtr_id_track'];

        /**
         * @var FieldsDefinition $fieldsDefinition
         */
        $fieldsDefinition = $this->overloader->create('Tracker\\Engine\\FieldsDefinition', $this->_trackId, $this->overloader);
        $this->_fieldsDefinition = $fieldsDefinition;

        $this->_trackData = $this->dbTranslationRepository->translateTable('gems__tracks', $this->_trackId, $this->_trackData);

        $this->_ensureRounds();
    }

    /**
     * Loads the rounds data for this type of track engine.
     *
     * Can be overruled by sub classes.
     */
    protected function _ensureRounds(): void
    {
        if (! is_array($this->_rounds)) {
            $roundSelect = $this->resultFetcher->getSelect('gems__rounds')
                ->where(['gro_id_track' => $this->_trackId])
                ->order(['gro_id_order']);

            // \MUtil\EchoOut\EchoOut::track((string) $roundSelect, $this->_trackId);

            $this->_rounds  = [];
            foreach ($this->resultFetcher->fetchAll($roundSelect) as $round) {
                $roundId = intval($round['gro_id_round']);
                $this->_rounds[$roundId] = $round;
            }
        }
    }

    /**
     * Returns a list of available icons under 'htdocs/pulse/icons'
     * @return string[]
     */
    protected function _getAvailableIcons(): array
    {
        return [];
        /*$dir = GEMS_WEB_DIR . '/gems-responsive/images/icons';
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
                    $url = $this->view->baseUrl() . \MUtil\Html\ImgElement::getImageDir($filename);
                    $icons[$fileinfo->getFilename()] = \MUtil\Html::create('span', $filename, array('data-class' => 'avatar', 'data-style' => 'background-image: url(' . $url . $filename . ');'));
                }
            }
        }

        ksort($icons);  // Sort by key

        return $icons;*/
    }

    /**
     * Update the track, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    private function _update(array $values, int $userId): int
    {
        if ($this->tracker->filterChangesOnly($this->_trackData, $values)) {

            if (Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_trackData[$key] . ' => ' . $val . "\n";
                }
                EchoOut::r($echo, 'Updated values for ' . $this->_trackId);
            }

            if (! isset($values['gto_changed'])) {
                $values['gtr_changed'] = new Expression('CURRENT_TIMESTAMP');
            }
            if (! isset($values['gtr_changed_by'])) {
                $values['gtr_changed_by'] = $userId;
            }

            // Update values in this object
            $this->_trackData = $values + $this->_trackData;

            $table = new TableGateway('gems__tracks', $this->resultFetcher->getAdapter());
            return $table->update($values, ['gtr_id_track' => $this->_trackId]);
        } else {
            return 0;
        }
    }

    /**
     * Integrate field loading en showing and editing
     *
     * @param MetaModelInterface $model
     * @param bool $addDependency True when editing, can be false in all other cases
     * @param string|null $respTrackIdField Optional Database column name where Respondent Track Id is set
     * @return self
     */
    public function addFieldsToModel(MetaModelInterface $model, bool $addDependency = true, ?string $respTrackIdField = null): self
    {
        if ($this->_fieldsDefinition->exists) {
            // Add the data to the load / save
            $transformer = new AddTrackFieldsTransformer($this->tracker, $this->_fieldsDefinition, $respTrackIdField);
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
     * @param RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens created by this code
     */
    protected function addNewTokens(RespondentTrack $respTrack, int $userId): int
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

        $newRounds = $this->resultFetcher->fetchAll($sql, [$this->_trackId, $respTrackId, $orgId]);

        $connection = $this->resultFetcher->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
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
        $connection->commit();

        return count($newRounds);
    }

    /**
     * Calculate the track info from the fields
     *
     * @param array $data The values to save
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo(array $data): string
    {
        return $this->_fieldsDefinition->calculateFieldsInfo($data);
    }

    /**
     * Calculate the number of active rounds in this track from the database.
     *
     * @return int The number of rounds in this track.
     */
    public function calculateRoundCount(): int
    {
        return $this->resultFetcher->fetchOne("SELECT COUNT(*) FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ?", [$this->_trackId]);
    }

    /**
     * Checks all existing tokens and updates any changes to the original rounds (when necessary)
     *
     * @param RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    protected function checkExistingRoundsFor(RespondentTrack $respTrack, int $userId): int
    {
        $respTrackId = $respTrack->getRespondentTrackId();

        $sql = "UPDATE gems__tokens, gems__rounds, gems__reception_codes
            SET gto_id_respondent = ?,
                gto_id_organization = ?,
                gto_id_track = ?,
                gto_id_survey = CASE WHEN gto_start_time IS NULL AND grc_success = 1 THEN gro_id_survey ELSE gto_id_survey END,
                gto_round_order = gro_id_order,
                gto_icon_file = gro_icon_file,
                gto_round_description = gro_round_description,
                gto_changed = CURRENT_TIMESTAMP,
                gto_changed_by = ?
            WHERE gto_id_round = gro_id_round AND
                gto_reception_code = grc_id_reception_code AND
                gto_id_round != 0 AND
                gro_active = 1 AND
                (
                    gto_id_respondent != ? OR
                    gto_id_organization != ? OR
                    gto_id_track != ? OR
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

        $parameters = [
            $respTrack->getRespondentId(),
            $respTrack->getOrganizationId(),
            $this->_trackId,
            $userId,
            $respTrack->getRespondentId(),
            $respTrack->getOrganizationId(),
            $this->_trackId,
            $respTrackId,
        ];

        $stmt = $this->resultFetcher->query($sql, $parameters);

        return $stmt->count();
    }


    public function checkRoundsFor(RespondentTrack $respTrack, SessionInterface $session = null, int $userId, ?TaskRunnerBatch $batch = null): void
    {
        if (null === $batch) {
            $batch = new TaskRunnerBatch('tmptrack' . $respTrack->getRespondentTrackId(), $this->overloader, $session);
        }
        // Step one: update existing tokens
        $i = $batch->addToCounter('roundChangeUpdates', $this->checkExistingRoundsFor($respTrack, $userId));
        $batch->setMessage('roundChangeUpdates', sprintf($this->translator->_('Round changes propagated to %d tokens.'), $i));

        // Step two: deactivate inactive rounds
        $i = $batch->addToCounter('deletedTokens', $this->removeInactiveRounds($respTrack));
        $batch->setMessage('deletedTokens', sprintf($this->translator->_('%d tokens deleted by round changes.'), $i));

        // Step three: create lacking tokens
        $i = $batch->addToCounter('createdTokens', $this->addNewTokens($respTrack, $userId));
        $batch->setMessage('createdTokens', sprintf($this->translator->_('%d tokens created to by round changes.'), $i));

        // Step four: set the dates and times
        //$changed = $this->checkTokensFromStart($respTrack, $userId);
        $changed = $respTrack->checkTrackTokens($userId);
        $ica = $batch->addToCounter('tokenDateCauses', $changed ? 1 : 0);
        $ich = $batch->addToCounter('tokenDateChanges', $changed);
        $batch->setMessage('tokenDateChanges', sprintf($this->translator->_('%2$d token date changes in %1$d tracks.'), $ica, $ich));

        $i = $batch->addToCounter('checkedRespondentTracks');
        $batch->setMessage('checkedRespondentTracks', sprintf($this->translator->_('Checked %d tracks.'), $i));
    }

    /**
     * Copy a track and all it's related data (rounds/fields etc)
     *
     * @param int $oldTrackId  The id of the track to copy
     * @return int              The id of the copied track
     */
    public function copyTrack(int $oldTrackId): int
    {
        $trackModel = $this->tracker->getTrackModel();

        $roundModel = $this->getRoundModel(true, 'rounds');
        $fieldModel = $this->getFieldsMaintenanceModel();

        // First load the track
        $track = $trackModel->loadFirst(['id' => $oldTrackId]);

        // Create an empty track
        $newTrack = $trackModel->loadNew();
        unset($track['gtr_id_track'], $track['gtr_changed'], $track['gtr_changed_by'], $track['gtr_created'], $track['gtr_created_by']);
        $track['gtr_track_name'] .= $this->translator->_(' - Copy');
        $newTrack = $track + $newTrack;
        // Now save (not done yet)
        $savedValues = $trackModel->save($newTrack);
        $newTrackId = $savedValues['gtr_id_track'];

        // Now copy the fields
        $fields = $fieldModel->load(['id' => $oldTrackId]);
        $oldNewFieldMap = [];

        if ($fields) {
            $oldIds = [];
            $numFields = count($fields);
            $newFields = $fieldModel->loadNew($numFields);
            foreach ($newFields as $idx => $newField) {
                $field = $fields[$idx];
                $oldIds[$idx] = $field['gtf_id_field'];
                unset($field['gtf_id_field'], $field['gtf_changed'], $field['gtf_changed_by'], $field['gtf_created'], $field['gtf_created_by']);
                $field['gtf_id_track'] = $newTrackId;
                $newFields[$idx] = $field + $newFields;
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
        $rounds = $roundModel->load(['id' => $oldTrackId]);

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
                $newRounds[$idx] = $round + $newRound;
            }
            // Now save (not done yet)
            $savedValues = $roundModel->saveAll($newRounds);
        } else {
            $numRounds = 0;
        }

        //\Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage(sprintf($this->translator->_('Copied track, including %s round(s) and %s field(s).'), $numRounds, $numFields));

        return $newTrackId;
    }

    /**
     * Create model for rounds. Allows overriding by subclasses.
     *
     * @return RoundModel
     */
    protected function createRoundModel(): RoundModel
    {
        return $this->overloader->create(RoundModel::class);
    }

    /**
     *
     * @return string External description of the track
     */
    public function getExternalName(): string
    {
        if (isset($this->_trackData['gtr_external_description']) && $this->_trackData['gtr_external_description']) {
            return $this->_trackData['gtr_external_description'];
        }

        return $this->getTrackName();
    }

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return TrackBeforeFieldUpdateEventInterface | null
     */
    public function getFieldBeforeUpdateEvent(): ?TrackBeforeFieldUpdateEventInterface
    {
        if (isset($this->_trackData['gtr_beforefieldupdate_event']) && $this->_trackData['gtr_beforefieldupdate_event']) {
            return $this->trackEvents->loadBeforeTrackFieldUpdateEvent($this->_trackData['gtr_beforefieldupdate_event']);
        }
        return null;
    }

    /**
     * Returns an array of the fields in this track key / value are id / code
     *
     * @return array fieldid => fieldcode With null when no fieldcode
     */
    public function getFieldCodes(): array
    {
        return $this->_fieldsDefinition->getFieldCodes();
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / field name
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldNames(): array
    {
        return $this->_fieldsDefinition->getFieldNames();
    }

    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId \Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsData(int $respTrackId): array
    {
        return $this->_fieldsDefinition->getFieldsDataFor($respTrackId);
    }

    /**
     * Get the storage model for field values
     *
     * @return FieldDataModel
     */
    public function getFieldsDataStorageModel(): FieldDataModel
    {
        return $this->_fieldsDefinition->getDataStorageModel();
    }

    /**
     * Returns the field definition for the track enige.
     *
     * @return FieldsDefinition
     */
    public function getFieldsDefinition(): FieldsDefinition
    {
        return $this->_fieldsDefinition;
    }

    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param bool $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return FieldMaintenanceModel
     */
    public function getFieldsMaintenanceModel(bool $detailed = false, string $action = 'index'): FieldMaintenanceModel
    {
        return $this->_fieldsDefinition->getMaintenanceModel($detailed, $action);
    }

    /**
     * Returns an array name => code of all the fields of the type specified
     *
     * @param string $fieldType
     * @return array name => code
     */
    public function getFieldsOfType(string $fieldType): array
    {
        return $this->_fieldsDefinition->getFieldCodesOfType($fieldType);
    }

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return TrackFieldUpdateEventInterface | null
     */
    public function getFieldUpdateEvent(): ?TrackFieldUpdateEventInterface
    {
        if (isset($this->_trackData['gtr_fieldupdate_event']) && $this->_trackData['gtr_fieldupdate_event']) {
            return $this->trackEvents->loadTrackFieldUpdateEvent($this->_trackData['gtr_fieldupdate_event']);
        }
        return null;
    }

    /**
     * Get the round id of the first round
     *
     * @return int \Gems id of first round
     */
    public function getFirstRoundId(): int
    {
        $this->_ensureRounds();

        reset($this->_rounds);

        return key($this->_rounds);
    }

    /**
     * Look up the round id for the next round
     *
     * @param int $roundId  \Gems round id
     * @return int|null \Gems round id
     */
    public function getNextRoundId(int $roundId): ?int
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
       return null;
    }

    /**
     * @return array Of organization ids
     */
    public function getOrganizationIds(): array
    {
        if (is_string($this->_trackData['gtr_organizations'])) {
            $this->_trackData['gtr_organizations'] = array_filter(explode('|', $this->_trackData['gtr_organizations']));
        }

        return $this->_trackData['gtr_organizations'];
    }

    /**
     * Look up the round id for the previous round
     *
     * @param int $roundId  \Gems round id
     * @param int|null $roundOrder Optional extra round order, for when the current round may have changed.
     * @return int|null \Gems round id
     */
    public function getPreviousRoundId(mixed $roundId, int|null $roundOrder = null): ?int
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


           throw new Exception(sprintf($this->translator->_('Requested non existing round with id %d.'), $roundId));

       } elseif ($this->_rounds) {
            end($this->_rounds);
            $key = key($this->_rounds);
            if (empty($key) && !is_null($key)) {
                // The last round (with empty index) is the current round, step back one more round
                prev($this->_rounds);
            }
            return key($this->_rounds);
       }
       return null;
    }

    /**
     * Get the round object
     *
     * @param int $roundId  \Gems round id
     * @return Round|null
     */
    public function getRound(mixed $roundId): ?Round
    {
        $this->_ensureRounds();

        if (! isset($this->_rounds[$roundId])) {
            return null;
        }
        if (! isset($this->_roundObjects[$roundId])) {
            $this->_roundObjects[$roundId] = $this->overloader->create('Tracker\\Round', $this->_rounds[$roundId]);
        }
        return $this->_roundObjects[$roundId];
    }

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param Token $token
     * @return array Of snippet names
     */
    public function getRoundAnswerSnippets(Token $token): array
    {
        $this->_ensureRounds();
        $roundId = $token->getRoundId();

        if (isset($this->_rounds[$roundId]['gro_display_event']) && $this->_rounds[$roundId]['gro_display_event']) {
            $event = $this->trackEvents->loadSurveyDisplayEvent($this->_rounds[$roundId]['gro_display_event']);

            return $event->getAnswerDisplaySnippets($token);
        }
        return [];
    }

    /**
     * Return the Round Changed event name for this round
     *
     * @param int $roundId
     * @return RoundChangedEventInterface|null event instance or null
     */
    public function getRoundChangedEvent(int $roundId): ?RoundChangedEventInterface
    {
        $this->_ensureRounds();

        if (isset($this->_rounds[$roundId]['gro_changed_event']) && $this->_rounds[$roundId]['gro_changed_event']) {
            return $this->trackEvents->loadRoundChangedEvent($this->_rounds[$roundId]['gro_changed_event']);
        }
        return null;
    }

    /**
     * Get the defaults for a new round
     *
     * @return array Of fieldname => default
     */
    public function getRoundDefaults(): array
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
    public function getRoundDescriptions(): array
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
    public function getRoundEditSnippetNames(): array
    {
        return ['Tracker\\Rounds\\EditRoundStepSnippet'];
    }

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param bool $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return RoundModel
     */
    public function getRoundModel(bool $detailed, string $action): RoundModel
    {
        $model = $this->createRoundModel();
        $metaModel = $model->getMetaModel();

        // Set the keys to the parameters in use.
        $metaModel->setKeys([
            MetaModelInterface::REQUEST_ID => 'gro_id_track',
            \Gems\Model::ROUND_ID => 'gro_id_round'
        ]);

        if ($detailed) {
            $metaModel->set('gro_id_track', [
                'label' => $this->translator->_('Track'),
                'elementClass' => 'exhibitor',
                'multiOptions' => $this->trackDataRepository->getAllTracks(),
            ]);
        }

        $metaModel->set('gro_id_survey', [
            'label' => $this->translator->_('Survey'),
            'multiOptions' => $this->trackDataRepository->getAllSurveysAndDescriptions()
        ]);
        $metaModel->set('gro_icon_file', ['label' => $this->translator->_('Icon')]);
        $metaModel->set('gro_id_order', [
            'label' => $this->translator->_('Order'),
            'default' => 10,
            'filters[digits]' => 'Digits',
            'required' => true,
            'validators[uni]' => [ModelUniqueValidator::class, true, ['with' => 'gro_id_track']]
        ]);
        $metaModel->set('gro_round_description', [
            'label' => $this->translator->_('Description'),
            'size' => '30'
        ]);

        $list = $this->trackEvents->listRoundChangedEvents();
        if (count($list) > 1) {
            $metaModel->set('gro_changed_event', [
                'label' => $this->translator->_('After change'),
                'multiOptions' => $list
            ]);
        }
        $list = $this->trackEvents->listSurveyDisplayEvents();
        if (count($list) > 1) {
            $metaModel->set('gro_display_event', [
                'label' => $this->translator->_('Answer display'),
                'multiOptions' => $list
            ]);
        }
        $metaModel->set('gro_active', [
            'label' => $this->translator->_('Active'),
            'type' => new ActivatingYesNoType($this->translatedUtil->getYesNo(), 'row_class'),
        ]);
        $metaModel->setIfExists('gro_code', [
            'label' => $this->translator->_('Round code'),
            'description' => $this->translator->_('Optional code name to link the field to program code.'),
            'size' => 10
        ]);

        $model->addColumn(
            "CASE WHEN gro_active = 1 THEN '' ELSE 'deleted' END",
            'row_class');
        $model->addColumn(
            "CASE WHEN gro_organizations IS NULL THEN 0 ELSE 1 END",
            'org_specific_round');
        $model->addColumn('gro_organizations', 'organizations');

        $metaModel->set('organizations', [
            'label' => $this->translator->_('Organizations'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $this->organizationRepository->getOrganizations(),
            'data-source' => 'org_specific_round',
            'type' => new ConcatenatedType('|', $this->translator->_(', '))
        ]);

        $metaModel->set('gro_condition', [
            'label' => $this->translator->_('Condition'),
            'autoSubmit' => true,
            'elementClass' => 'Select',
            'multiOptions' => $this->conditionLoader->getConditionsFor(ConditionLoader::ROUND_CONDITION)
        ]);

        $metaModel->set('condition_display', [
            'label' => $this->translator->_('Condition help'),
            'elementClass' => 'Hidden',
            'no_text_search' => true,
            'noSort' => true,
            SqlRunnerInterface::NO_SQL => true,
        ]);

        $metaModel->addDependency('Condition\\RoundDependency');

        switch ($action) {
            case 'create':
                $this->_ensureRounds();

                if ($this->_rounds && ($round = end($this->_rounds))) {
                    $metaModel->set('gro_id_order', ['default' => $round['gro_id_order'] + 10]);
                }

                // Intentional fall through
                // break;
            case 'edit':
            case 'show':
                $metaModel->set('gro_icon_file', [
                    'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + $this->_getAvailableIcons()
                ]);
                $metaModel->set('org_specific_round', [
                    'label' => $this->translator->_('Organization specific round'),
                    'default' => 0,
                    'multiOptions' => $this->translatedUtil->getYesNo(),
                    'elementClass' => 'radio',
                    'autoSubmit' => true
                ]);

                break;

            default:
                $metaModel->set('gro_icon_file', ['formatFunction' => [ImgElement::class, 'imgFile']]);
                break;

        }

        return $model;
    }

    /**
     * Get all the round objects
     *
     * @return array of roundId => \Gems\Tracker\Round
     */
    public function getRounds(): array
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
    public function getRoundShowSnippetNames(): array
    {
        return ['Tracker\\Rounds\\ShowRoundStepSnippet', 'Survey\\SurveyQuestionsSnippet'];
    }

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return StandardTokenModel
     */
    public function getTokenModel(): StandardTokenModel
    {
        return $this->tracker->getTokenModel();
    }

    /**
     * Get the TrackCompletedEvent for this trackId
     *
     * @return TrackCalculationEventInterface | null
     */
    public function getTrackCalculationEvent(): ?TrackCalculationEventInterface
    {
        if (isset($this->_trackData['gtr_calculation_event']) && $this->_trackData['gtr_calculation_event']) {
            return $this->trackEvents->loadTrackCalculationEvent($this->_trackData['gtr_calculation_event']);
        }
        return null;
    }

    /**
     *
     * @return string The gems track code
     */
    public function getTrackCode(): string
    {
        return $this->_trackData['gtr_code'];
    }

    /**
     * Get the TrackCompletedEvent for this trackId
     *
     * @return TrackCompletedEventInterface|null
     */
    public function getTrackCompletionEvent(): ?TrackCompletedEventInterface
    {
        if (isset($this->_trackData['gtr_completed_event']) && $this->_trackData['gtr_completed_event']) {
            return $this->trackEvents->loadTrackCompletionEvent($this->_trackData['gtr_completed_event']);
        }
        return null;
    }

    /**
     *
     * @return int The track id
     */
    public function getTrackId(): int
    {
        return $this->_trackId;
    }

    /**
     *
     * @return string The gems track name
     */
    public function getTrackName(): string
    {
        return $this->_trackData['gtr_track_name'];
    }

    /**
     * Is the field an appointment type
     *
     * @param string $fieldName
     * @return bool
     */
    public function isAppointmentField(string $fieldName): bool
    {
        return $this->_fieldsDefinition->isAppointment($fieldName);
    }

    /**
     * Remove the unanswered tokens for inactive rounds.
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @return int The number of tokens changed by this code
     */
    protected function removeInactiveRounds(RespondentTrack $respTrack): int
    {
        $sql = "DELETE FROM gems__tokens WHERE gto_start_time IS NULL AND
            gto_id_respondent_track = ? AND
            gto_id_round != 0 AND
            gto_id_round IN (SELECT gro_id_round
                    FROM gems__rounds
                    WHERE (gro_active = 0 OR gro_organizations NOT LIKE CONCAT('%|',?,'|%')) AND
                        gro_id_track = ?)";

        $statement = $this->resultFetcher->query($sql, [
            $respTrack->getRespondentTrackId(),
            $respTrack->getOrganizationId(),
            $this->_trackId,
        ]);

        return $statement->getFieldCount();
    }

    /**
     * Updates the number of rounds in this track.
     *
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function updateRoundCount(int $userId): int
    {
        $values['gtr_survey_rounds'] = $this->calculateRoundCount();

        return $this->_update($values, $userId);
    }
}
