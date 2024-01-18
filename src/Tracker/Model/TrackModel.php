<?php

declare(strict_types=1);

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use DateTimeImmutable;
use Gems\Db\ResultFetcher;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Model\SqlTableModel;
use Gems\Project\ProjectSettings;
use Gems\Repository\OrganizationRepository;
use Gems\Tracker;
use Gems\Tracker\TrackEvents;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Model\Type\ConcatenatedType;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * Simple stub for track model, allows extension by projects and adds auto labelling
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TrackModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Model $model,
        protected readonly Tracker $tracker,
        protected readonly Translated $translatedUtil,
        protected readonly ProjectSettings $project,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly TrackEvents $trackEvents,
        protected readonly OrganizationRepository $organizationRepository,
    ) {
        parent::__construct('gems__tracks', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gtr');

        $this->metaModel->set('gtr_date_start', ['default' => new DateTimeImmutable()]);
        $this->metaModel->setKeys(['trackId' => 'gtr_id_track']);
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @param bool $detailed True when showing detailed information
     * @param bool $edit When true use edit settings
     */
    public function applyFormatting(bool $detailed = false, bool $edit = false): TrackModel
    {
        $this->metaModel->resetOrder();

        $this->metaModel->set('gtr_track_name', [
            'label' => $this->translate->_('Name'),
            'translate' => true
        ]);
        $this->metaModel->set('gtr_external_description', [
            'label' => $this->translate->_('External Name'),
            'description' => $this->translate->_(
                'Optional alternate external description for communication with respondents'
            ),
            'translate' => true
        ]);
        $this->metaModel->set('gtr_track_class', [
            'label' => $this->translate->_('Track Engine'),
            'multiOptions' => $this->tracker->getTrackEngineList($detailed)
        ]);
        $this->metaModel->set('gtr_survey_rounds', ['label' => $this->translate->_('Surveys')]);

        $this->metaModel->set('gtr_active', [
            'label' => $this->translate->_('Active'),
            'type' => new ActivatingYesNoType($this->translatedUtil->getYesNo(), 'row_class'),
        ]);
        $this->metaModel->set('gtr_date_start', [
            'label' => $this->translate->_('From'),
            'elementClass' => 'Date',
        ]);
        $this->metaModel->set('gtr_date_until', [
            'label' => $this->translate->_('Use until'),
            'elementClass' => 'Date',
        ]);
        $this->metaModel->setIfExists('gtr_code', [
            'label' => $this->translate->_('Track code'),
            'size' => 10,
            'description' => $this->translate->_('Optional code name to link the track to program code.')
        ]);

        $this->model->addDatabaseTranslationEditFields($this->metaModel);

        if ($detailed) {
            $caList = $this->trackEvents->listTrackCalculationEvents();
            if (count($caList) > 1) {
                $this->metaModel->setIfExists('gtr_calculation_event', [
                    'label' => $this->translate->_('Before (re)calculation'),
                    'multiOptions' => $caList
                ]);
            }

            $coList = $this->trackEvents->listTrackCompletionEvents();
            if (count($coList) > 1) {
                $this->metaModel->setIfExists('gtr_completed_event', [
                    'label' => $this->translate->_('After completion'),
                    'multiOptions' => $coList
                ]);
            }

            $bfuList = $this->trackEvents->listTrackBeforeFieldUpdateEvents();
            if (count($bfuList) > 1) {
                $this->metaModel->setIfExists('gtr_beforefieldupdate_event', [
                    'label' => $this->translate->_('Before field update'),
                    'multiOptions' => $bfuList
                ]);
            }

            $fuList = $this->trackEvents->listTrackFieldUpdateEvents();
            if (count($fuList) > 1) {
                $this->metaModel->setIfExists('gtr_fieldupdate_event', [
                    'label' => $this->translate->_('After field update'),
                    'multiOptions' => $fuList
                ]);
            }
            $this->metaModel->setIfExists('gtr_organizations', [
                'label' => $this->translate->_('Organizations'),
                'elementClass' => 'MultiCheckbox',
                'multiOptions' => $this->organizationRepository->getOrganizationsWithRespondents(),
                'required'=> true,
                'type' => new ConcatenatedType('|', $this->translate->_(', ')),
            ]);
        }

        if ($edit) {
            $this->metaModel->set('toggleOrg', [
                'elementClass' => 'ToggleCheckboxes',
                'selectorName' => 'gtr_organizations'
            ]);
            $this->metaModel->set('gtr_track_name', [
                'minlength' => 4,
                'size' => 30,
                'validators[unique]' => ModelUniqueValidator::class
            ]);
        }

        if ($this->project->translateDatabaseFields()) {
            if ($edit) {
                $this->model->addDatabaseTranslationEditFields($this->metaModel);
            } else {
                $this->model->addDatabaseTranslations($this->metaModel);
            }
        }
        return $this;
    }

    public function applySummary(): void
    {
        $this->metaModel->getKeys(true);

        $this->metaModel->resetOrder();

        $this->metaModel->set('gtr_track_name', ['label' => $this->_('Track')]);
        $this->metaModel->set('gtr_survey_rounds', ['label' => $this->_('Survey #')]);
        $this->metaModel->set('gtr_date_start', [
            'label' => $this->_('From'),
            'tdClass' => 'date'
        ]);
        $this->metaModel->set('gtr_date_until', [
            'label' => $this->_('Until'),
            'tdClass' => 'date'
        ]);
    }

    /**
     * Delete items from the model
     *
     * This method also takes care of cascading to track fields and rounds
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = null): int
    {
        $tracks = $this->load($filter);

        if ($tracks) {
            foreach ($tracks as $row) {
                if (isset($row['gtr_id_track'])) {
                    $trackId = $row['gtr_id_track'];
                    if ($this->isDeletable($trackId)) {
                        // Now cascade to children, they should take care of further cascading
                        // Delete rounds
                        $trackEngine = $this->tracker->getTrackEngine($trackId);
                        $roundModel = $trackEngine->getRoundModel(true, 'index');
                        $roundModel->delete(['gro_id_track' => $trackId]);

                        // Delete trackfields
                        $trackFieldModel = $trackEngine->getFieldsMaintenanceModel(false, 'index');
                        $trackFieldModel->delete(['gtf_id_track' => $trackId]);

                        // Delete assigned but unused tracks
                        $this->resultFetcher->query('DELETE FROM gems__respondent2track WHERE gr2t_id_track = ?', [$trackId]);

                        $this->resultFetcher->query('DELETE FROM gems__tracks WHERE gtr_id_track = ?', [$trackId]);
                    } else {
                        $values['gtr_id_track'] = $trackId;
                        $values['gtr_active'] = 0;
                        $this->save($values);
                    }
                    $this->addChanged();
                }
            }
        }

        return $this->getChanged();
    }

    /**
     * When this method returns something other than an empty array it will try
     * to add fields to a newly created track
     *
     * @return array Should be an array or arrays, containing the default fields
     */
    public function getDefaultFields(): array
    {
        return [];
    }

    /**
     * Get the number of times someone started answering a round in this track.
     */
    public function getStartCount(int $trackId): int
    {
        if (!$trackId) {
            return 0;
        }

        $sql = "SELECT COUNT(DISTINCT gto_id_respondent_track) FROM gems__tokens WHERE gto_id_track = ? AND gto_start_time IS NOT NULL";
        return $this->resultFetcher->fetchOne($sql, [$trackId]);
    }

    /**
     * Can this track be deleted as is?
     */
    public function isDeletable(int $trackId): bool
    {
        if (!$trackId) {
            return true;
        }
        $sql = "SELECT gto_id_token FROM gems__tokens WHERE gto_id_track = ? AND gto_start_time IS NOT NULL";
        return (bool)!$this->resultFetcher->fetchOne($sql, [$trackId]);
    }

    public function save(array $newValues, array $filter = null): array
    {
        // Allow to add default fields to any new track
        if ($defaultFields = $this->getDefaultFields()) {
            $keys = $this->metaModel->getKeys();
            $keys = array_flip($keys);
            $missing = array_diff_key($keys, $newValues);     // On copy track the key exists but is null

            $newValues = parent::save($newValues, $filter);
            if (!empty($missing)) {
                // We have an insert!
                $foundKeys = array_intersect_key($newValues, $missing);
                // Now get the fieldModel
                $engine = $this->tracker->getTrackEngine($foundKeys['gtr_id_track']);
                $fieldModel = $engine->getFieldsMaintenanceModel(true, 'create');
                $lastOrder = 0;
                foreach ($defaultFields as $field) {
                    // Load defaults
                    $record = $fieldModel->loadNew();
                    $record['gtf_id_order'] = $lastOrder + 10;

                    $record = $field + $record;             // Add defaults to the new field
                    $record = $fieldModel->save($record);
                    $lastOrder = $record['gtf_id_order'];   // Save order for next record
                }
            }
        } else {
            $newValues = parent::save($newValues, $filter);
        }

        return $newValues;
    }
}
