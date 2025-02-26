<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model as GemsModel;
use Gems\Model\GemsMaskedModel;
use Gems\Model\MetaModelLoader;
use Gems\Model\Transform\OrganizationAccessTransformer;
use Gems\Repository\MailRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use MUtil\Model;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * The RespondentTrackModel is the model used to display and edit
 * respondent tracks in snippets.
 *
 * The main additions to a standard JoinModel are for filling in the
 * respondent and track info while creating new tracks and key
 * fiddling code for the different use cases.
 *
 * The respondent track model combines all possible information
 * about the respondents track from the tables:
 * - gems__reception_codes
 * - gems__respondent2org
 * - gems__respondent2track
 * - gems__respondents
 * - gems__staff (on created by)
 * - gems__tracks
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class RespondentTrackModel extends GemsMaskedModel
{
    /**
     *
     * @var boolean When true the labels of wholly masked items are removed
     */
    protected bool $hideWhollyMasked = true;

    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        MaskRepository $maskRepository,
        protected readonly GemsModel $gemsModel,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly Translated $translatedUtil,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly MailRepository $mailRepository,
        protected readonly OrganizationRepository $organizationRepository,
    ) {
        parent::__construct(
            'gems__respondent2track',
            $metaModelLoader,
            $sqlRunner,
            $translate,
            $maskRepository,
            'surveys',
        );

        $metaModelLoader->setChangeFields($this->metaModel, 'gr2t');

        $this->addTable('gems__respondents', ['gr2t_id_user' => 'grs_id_user'], false, 'grs');
        $this->addTable(
            'gems__respondent2org',
            ['gr2t_id_user' => 'gr2o_id_user', 'gr2t_id_organization' => 'gr2o_id_organization'],
            false,
            'gr2o'
        );
        $this->addTable('gems__tracks', ['gr2t_id_track' => 'gtr_id_track'], false, 'gtr');
        $this->addTable('gems__reception_codes', ['gr2t_reception_code' => 'grc_id_reception_code'], false, 'grc');
        $this->addLeftTable('gems__staff', ['gr2t_created_by' => 'gsf_id_user']);

        // No need to send all this information to the user
        $this->metaModel->setCol(
            $this->metaModel->getItemsFor(['table' => 'gems__staff']),
            ['elementClass' => 'None']
        );

        $this->metaModel->setKeys([
            GemsModel::RESPONDENT_TRACK => 'gr2t_id_respondent_track',
            MetaModelInterface::REQUEST_ID1 => 'gr2o_patient_nr',
            MetaModelInterface::REQUEST_ID2 => 'gr2t_id_organization',
        ]);

        $this->addColumn(
            "CASE WHEN gsf_id_user IS NULL
                THEN '-'
                ELSE
                    CONCAT(
                        COALESCE(gsf_last_name, ''),
                        ', ',
                        COALESCE(gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                    )
                END",
            'assigned_by'
        );
        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END",
            'row_class'
        );

        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN 1 ELSE 0 END",
            'can_edit'
        );

        $this->addColumn(
            "CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, ''))",
            'respondent_name'
        );

        $this->metaModel->addTransformer(new OrganizationAccessTransformer($this->organizationRepository, 'gr2t_id_organization'));
    }

    /**
     * Set those settings needed for the browse display
     */
    public function applyBrowseSettings(): self
    {
        $formatDate = $this->translatedUtil->formatDate;

        $this->metaModel->resetOrder();

        $this->metaModel->setKeys([
            \Gems\Model::RESPONDENT_TRACK => 'gr2t_id_respondent_track',
            MetaModelInterface::REQUEST_ID1     => 'gr2o_patient_nr',
            MetaModelInterface::REQUEST_ID2 => 'gr2t_id_organization',
        ]);

        $this->metaModel->set('gtr_track_name', ['label' => $this->_('Track')]);
        $this->metaModel->set('gr2t_track_info', [
            'label' => $this->_('Description'),
            'description' => $this->_('Enter the particulars concerning the assignment to this respondent.')
        ]);
        $this->metaModel->set('assigned_by', ['label' => $this->_('Assigned by')]);
        $this->metaModel->set('gr2t_start_date', [
            'label' => $this->_('Start'),
            'dateFormat' => 'd-m-Y',
            'formatFunction' => $formatDate,
            'default' => new \DateTimeImmutable(),
            'type' => MetaModelInterface::TYPE_DATE
        ]);
        $this->metaModel->set('gr2t_start_date', ['description' => $this->_('dd-mm-yyyy')]);

        $this->metaModel->set('gr2t_end_date', [
            'label' => $this->_('Ending on'),
            'dateFormat' => 'd-m-Y',
            'formatFunction' => $formatDate
        ]);
        $this->metaModel->set('gr2t_reception_code');
        $this->metaModel->set('gr2t_comment', ['label' => $this->_('Comment')]);

        $this->addColumn('CONCAT(gr2t_completed, \'' . $this->_(' of ') . '\', gr2t_count)', 'progress');
        $this->metaModel->set('progress', ['label' => $this->_('Progress')]);

        $this->gemsModel->addDatabaseTranslations($this->metaModel);

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     */
    public function applyDetailSettings(TrackEngineInterface $trackEngine, bool $edit = false): self
    {
        $this->metaModel->resetOrder();

        $formatDate = $this->translatedUtil->formatDate;

        $this->metaModel->set('gr2o_patient_nr', 'label', $this->_('Respondent number'));
        $this->metaModel->set('respondent_name', 'label', $this->_('Respondent name'));
        $this->metaModel->set('gtr_track_name', 'label', $this->_('Track'));

        $mailCodes = $this->mailRepository->getRespondentTrackMailCodes();
        end($mailCodes);
        $defaultMailCode = key($mailCodes);
        $this->metaModel->set('gr2t_mailable', [
            'label' => $this->_('May be mailed'),
            'default' => $defaultMailCode,
            'elementClass' => 'radio',
            'separator' => ' ',
            'multiOptions' => $mailCodes
        ]);

        $this->metaModel->set('assigned_by', ['label' => $this->_('Assigned by')]);
        $this->metaModel->set('gr2t_start_date', [
            'label' => $this->_('Start'),
            'dateFormat' => 'd-m-Y',
            'formatFunction' => $formatDate,
            'description' => $this->_('dd-mm-yyyy')
        ]);

        // Integrate fields
        $trackEngine->addFieldsToModel($this->metaModel, $edit);

        $this->metaModel->set('gr2t_end_date_manual', [
            'label' => $this->_('Set ending on'),
            'description' => $this->_('Manually set dates are fixed and will never be (re)calculated.'),
            'elementClass' => 'OnOffEdit',
            'multiOptions' => $this->translatedUtil->getDateCalculationOptions(),
            'separator' => ' ',
        ]);
        $this->metaModel->set('gr2t_end_date', [
            'label' => $this->_('Ending on'),
            'dateFormat' => 'd-m-Y',
            'formatFunction' => $formatDate
        ]);
        $this->metaModel->set('gr2t_track_info', ['label' => $this->_('Description')]);
        $this->metaModel->set('gr2t_comment', ['label' => $this->_('Comment')]);
        $this->metaModel->set('grc_description', [
            'label' => $this->_('Reception code'),
            'elementClass' => 'Exhibitor'
        ]);

        $this->applyMask();

        return $this;
    }

    /**
     * Set those values needed for editing
     */
    public function applyEditSettings(TrackEngineInterface $trackEngine): self
    {
        $this->applyDetailSettings($trackEngine, true);

        $this->metaModel->set('gr2o_patient_nr', ['elementClass' => 'Exhibitor']);
        $this->metaModel->set('respondent_name', ['elementClass' => 'Exhibitor']);
        $this->metaModel->set('gtr_track_name', ['elementClass' => 'Exhibitor']);

        $this->metaModel->set('gr2t_id_respondent_track', ['elementClass' => 'None']);
        $this->metaModel->set('gr2t_id_organization', ['elementClass' => 'None']);
        $this->metaModel->set('gr2t_id_user', ['elementClass' => 'None']);
        $this->metaModel->set('gr2t_id_track', ['elementClass' => 'Hidden']);
        // Fields set in details

        $this->metaModel->set('gr2t_track_info', ['elementClass' => 'None']);
        $this->metaModel->set('assigned_by', ['elementClass' => 'None']);
        $this->metaModel->set('gr2t_reception_code', ['elementClass' => 'None']);
        $this->metaModel->set('gr2t_start_date', [
            'elementClass' => 'Date',
            'default' => new \DateTimeImmutable(),
            'required' => true,
            'size' => 30
        ]);
        $this->metaModel->set('gr2t_end_date', [
            'elementClass' => 'Date',
            'default' => null,
            'size' => 30
        ]);
        $this->metaModel->set('gr2t_comment', [
            'elementClass' => 'Textarea',
            'cols' => 80,
            'rows' => 5
        ]);

        $this->gemsModel->addDatabaseTranslations($this->metaModel);

        return $this;
    }

    /**
     * Creates new items - in memory only. Extended to load information from linked table using $filter().
     *
     * When $filter contains the keys gr2o_patient_nr and gr2o_id_organization the corresponding respondent
     * information is loaded into the new item.
     *
     * When $filter contains the key gtr_id_track the corresponding track information is loaded.
     *
     * The $filter values are also propagated to the corresponding key values in the new item.
     *
     * @param int $count When null a single new item is return, otherwise a nested array with $count new items
     * @param array|null $filter Allowed key values: gr2o_patient_nr, gr2o_id_organization and gtr_id_track
     * @return array Nested when $count is not null, otherwise just a simple array
     */
    public function loadNew($count = null, array $filter = null): array
    {
        $values = array();

        // Get the defaults
        foreach ($this->metaModel->getItemNames() as $name) {
            $value = $this->metaModel->get($name, 'default');

            // Load 'Value' if set
            if (null === $value) {
                $value = $this->metaModel->get($name, 'value');
            }
            $values[$name] = $value;
        }

        // Create the extra values for the result
        if ($filter) {
            if (isset($filter['gr2o_patient_nr'], $filter['gr2o_id_organization'])) {
                $sql = "SELECT *,
                            CONCAT(
                                COALESCE(CONCAT(grs_last_name, ', '), '-, '),
                                COALESCE(CONCAT(grs_first_name, ' '), ''),
                                COALESCE(grs_surname_prefix, '')) AS respondent_name
                        FROM gems__respondents INNER JOIN gems__respondent2org ON grs_id_user = gr2o_id_user
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?";
                $values = $this->resultFetcher
                        ->fetchRow($sql, [$filter['gr2o_patient_nr'], $filter['gr2o_id_organization']]) + $values;
                $values['gr2t_id_user'] = $values['gr2o_id_user'];
                $values['gr2t_id_organization'] = $values['gr2o_id_organization'];
            } elseif (isset($filter['gr2o_id_user'], $filter['gr2o_id_organization'])) {
                $sql = "SELECT *,
                            CONCAT(
                                COALESCE(CONCAT(grs_last_name, ', '), '-, '),
                                COALESCE(CONCAT(grs_first_name, ' '), ''),
                                COALESCE(grs_surname_prefix, '')) AS respondent_name
                        FROM gems__respondents INNER JOIN gems__respondent2org ON grs_id_user = gr2o_id_user
                        WHERE gr2o_id_user = ? AND gr2o_id_organization = ?";
                $values = $this->resultFetcher
                        ->fetchRow($sql, [$filter['gr2o_id_user'], $filter['gr2o_id_organization']]) + $values;
                $values['gr2t_id_user'] = $values['gr2o_id_user'];
                $values['gr2t_id_organization'] = $values['gr2o_id_organization'];
            }
            if (isset($filter['gtr_id_track'])) {
                $sql = 'SELECT * FROM gems__tracks WHERE gtr_id_track = ?';
                $values = $this->resultFetcher->fetchRow($sql, [$filter['gtr_id_track']]) + $values;
                $values['gr2t_id_track'] = $values['gtr_id_track'];
                $values['gr2t_count'] = $values['gtr_survey_rounds'];
            }
            if (isset($filter['gr2t_id_user'])) {
                $values['gr2t_id_user'] = $filter['gr2t_id_user'];
            }
            if (isset($filter['gr2t_id_organization'])) {
                $this->currentUserRepository->assertAccessToOrganizationId($filter['gr2t_id_organization']);
                $values['gr2t_id_organization'] = $filter['gr2t_id_organization'];
            }
        }

        // \MUtil\EchoOut\EchoOut::track($filter, $values);
        $rows = $this->metaModel->processAfterLoad([$values], true);
        $row = reset($rows);

        // Return only a single row when no count is specified
        if (null === $count) {
            return $row;
        }

        return array_fill(0, $count, $row);
    }


    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array|null $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null, array $saveTables = null): array
    {
        $keys = $this->metaModel->getKeys();

        // This is the only key to save on, no matter
        // the keys used to initiate the model.
        $this->metaModel->setKeys($this->metaModel->getItemsFor(['table' => 'gems__respondent2track']));

        // Change the end date until the end of the day
        if (isset($newValues['gr2t_end_date']) && $newValues['gr2t_end_date']) {
            $displayFormat = $this->metaModel->get('gr2t_end_date', 'dateFormat');
            if (!$displayFormat) {
                $displayFormat = Model::getTypeDefault(Model::TYPE_DATE, 'dateFormat');
            }

            // Of course do not do so when we got a time format so check for those chars
            if (strpbrk($displayFormat, 'aAgGhHis') === false) {
                if ($newValues['gr2t_end_date'] instanceof DateTimeInterface) {
                    $date = $newValues['gr2t_end_date'];
                } else {
                    $date = DateTimeImmutable::createFromFormat(
                        $displayFormat,
                        $newValues['gr2t_end_date']
                    );
                    if (!$date instanceof DateTimeImmutable) {
                        $storageFormat = $this->metaModel->get('gr2t_end_date', 'storageFormat');
                        if (!$storageFormat) {
                            $storageFormat = Model::getTypeDefault(Model::TYPE_DATE, 'storageFormat');
                        }
                        $date = DateTimeImmutable::createFromFormat(
                            $storageFormat,
                            $newValues['gr2t_end_date']
                        );

                        if (! $date instanceof DateTimeImmutable) {
                            $date = new \DateTimeImmutable($newValues['gr2t_end_date']);
                        }
                    }
                }
                // @phpstan-ignore-next-line
                $newValues['gr2t_end_date'] = $date->setTime(23, 59, 59);
            }
        }

        $newValues = parent::save($newValues, $filter);

        $this->metaModel->setKeys($keys);

        return $newValues;
    }
}
