<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use Gems\Db\ResultFetcher;
use Gems\Event\Application\SurveyModelSetEvent;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Gems\Pdf;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\SurveyRepository;
use Gems\SnippetsActions\ApplyLegacyActionInterface;
use Gems\SnippetsActions\ApplyLegacyActionTrait;
use Gems\Tracker;
use Gems\Tracker\Model\Dependency\SurveyMaintenanceDependency;
use Gems\Tracker\TrackEvents;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Filter\Digits;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Html\HtmlElement;
use Zalt\Html\Raw;
use Zalt\Html\Sequence;
use Zalt\Model\Dependency\ValueSwitchDependency;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ConcatenatedType;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\Model\RequireOtherFieldValidator;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class SurveyMaintenanceModel extends GemsJoinModel implements ApplyLegacyActionInterface
{
    use ApplyLegacyActionTrait;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @param string $name
     */
    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly AccessRepository $accessRepository,
        protected readonly array $config,
        CurrentUserRepository $currentUserRepository,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly Locale $locale,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly Pdf $pdf,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly SurveyRepository $surveyRepository,
        protected readonly Tracker $tracker,
        protected readonly TrackEvents $trackEvents,
        protected readonly Translated $translatedUtil,
    )
    {
        $this->currentUser = $currentUserRepository->getCurrentUser();

        parent::__construct('gems__surveys', $metaModelLoader, $sqlRunner, $translate, 'surveyMantenanceModel', true);

        $this->metaModelLoader->setChangeFields($this->metaModel, 'gsu');
        $this->addTable('gems__sources', ['gsu_id_source' => 'gso_id_source'], false);
        $this->applySettings();
    }

    public function applyAction(SnippetActionInterface $action): void
    {
        if ($action->isDetailed()) {
            $hideSetting = ['elementClass' => 'Hidden', 'label' => ''];

            /**
             *
             * @var ValueSwitchDependency $hiderGroup
             */
            $hiderGroup = $this->metaModelLoader->createDependency(ValueSwitchDependency::class);
            $hiderGroup->setSwitches([0 => ['gsu_answer_groups' => $hideSetting]]);
            $this->metaModel->addDependency($hiderGroup, ['gsu_answers_by_group']);

            /**
             * @var ValueSwitchDependency $hiderOrg
             */
            $hiderOrg = $this->metaModelLoader->createDependency(ValueSwitchDependency::class);
            $switches = [0 => [
                'gsu_valid_for_length'     => $hideSetting,
                'gsu_valid_for_unit'       => $hideSetting,
                'gsu_insert_organizations' => $hideSetting,
                'toggleOrg'                => $hideSetting,
            ],];
            $hiderOrg->setSwitches($switches);
            $this->metaModel->addDependency($hiderOrg, 'gsu_insertable');

            $this->metaModel->set('track_usage', [
                'label' => $this->_('Usage'),
                'elementClass' => 'Exhibitor',
                'noSort' => true,
                'no_text_search' => true,
            ]);
            $this->metaModel->setOnLoad('track_usage', [$this, 'calculateTrackUsage']);

            $this->metaModel->set('calc_duration', [
                'label' => $this->_('Duration calculated'),
                'elementClass' => 'Html',
                'noSort' => true,
                'no_text_search' => true,
            ]);
            $this->metaModel->setOnLoad('calc_duration', [$this, 'calculateDuration']);

            $this->metaModel->set('gsu_duration', [
                'label' => $this->_('Duration description'),
                'description' => $this->_('Text to inform the respondent, e.g. "20 seconds" or "1 minute".'),
                'translate' => true
            ]);

            $this->metaModel->set('gsu_result_field', [
                'label' => $this->_('Result field'),
                'escape' => false,
                'multiOptions' => [],
            ]);

            // $this->metaModel->->set('gsu_agenda_result', ['label' => $this->_('Agenda field').]);

            $beforeOptions = $this->trackEvents->listSurveyBeforeAnsweringEvents();
            if (count($beforeOptions) > 1) {
                $this->metaModel->set('gsu_beforeanswering_event', [
                    'label' => $this->_('Before answering'),
                    'multiOptions' => $beforeOptions,
                    'elementClass' => 'Select',
                ]);
            }
            $completedOptions = $this->trackEvents->listSurveyCompletionEvents();
            if (count($completedOptions) > 1) {
                $this->metaModel->set('gsu_completed_event', [
                    'label' => $this->_('After completion'),
                    'multiOptions' => $completedOptions,
                    'elementClass' => 'Select',
                ]);
            }
            $displayOptions = $this->trackEvents->listSurveyDisplayEvents();
            if (count($displayOptions) > 1) {
                $this->metaModel->set('gsu_display_event', [
                    'label' => $this->_('Answer display'),
                    'multiOptions' => $displayOptions,
                    'elementClass' => 'Select',
                ]);
            }

            $this->metaModel->addDependency(SurveyMaintenanceDependency::class);

        } else {
            $this->metaModel->set('track_count', [
                'label' => ' ',
                'elementClass' => 'Exhibitor',
                'noSort' => true,
                'no_text_search' => true,
            ]);
            $this->metaModel->setOnLoad('track_count', [$this, 'calculateTrackCount']);
        }

        if ($action->isEditing()) {
            if (true || $this->currentUser->hasPrivilege('pr.survey-maintenance.answer-groups')) {
                $this->metaModel->addDependency('CanEditDependency', 'gsu_answers_by_group', ['gsu_answer_groups']);
            } else {
                $this->metaModel->setMulti(['gsu_answers_by_group', 'gsu_answer_groups', 'gsu_allow_export'], ['readonly' => 'readonly', 'disabled' => 'disabled']);
            }
            $this->metaModel->addDependency('CanEditDependency', 'gsu_surveyor_active', ['gsu_active']);

            $this->metaModel->set('toggleOrg', [
                'elementClass' => 'ToggleCheckboxes',
                'selectorName' => 'gsu_insert_organizations',
                'order'        => $this->metaModel->getOrder('gsu_insert_organizations') + 1,
            ]);

            $this->metaModel->set('gsu_survey_pdf', [
                'label' => 'Pdf',
                'accept' => 'application/pdf',
                'destination' => $this->pdf->getUploadDir('survey_pdfs'),
                'elementClass' => 'File',
                'extension' => 'pdf',
                'filename' => '',
                'required' => false,
                'validators[pdf]' => \MUtil\Validator\Pdf::class,
            ]);

//             $this->metaModelLoader->addDatabaseTranslationEditFields($this->metaModel);
        } else {
            $this->metaModelLoader->addDatabaseTranslations($this->metaModel);
        }

        $event = new SurveyModelSetEvent($this, $action->isDetailed(), $action->isEditing());
        $this->eventDispatcher->dispatch($event, SurveyModelSetEvent::class);
    }
    
    /**
     * Set those settings needed for the browse display
     *
     * @param boolean $addCount Add a count in rounds column
     * @param boolean $editing True when setting editing mode
     * @return \Gems\Model\SurveyMaintenanceModel
     */
    public function applySettings()
    {
        $survey     = null;
        $yesNo      = $this->translatedUtil->getYesNo();

        $this->addColumn(
                "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
                'gsu_has_pdf'
                );
        $this->addColumn(
                sprintf(
                        "CASE WHEN (gsu_status IS NULL OR gsu_status = '') THEN '%s' ELSE gsu_status END",
                        $this->_('OK')
                        ),
                'gsu_status_show',
                'gsu_status'
                );
        $this->addColumn(
                "CASE WHEN gsu_surveyor_active THEN '' ELSE 'deleted' END",
                'row_class'
                );

        $this->metaModel->resetOrder();

        $this->metaModel->set('gsu_survey_name', [
            'label' => $this->_('Name'),
            'elementClass' => 'Exhibitor',
        ]);
        $this->metaModel->set('gsu_external_description', [
            'label' => $this->_('External Name'),
            'description' => $this->_('Optional alternate external description for communication with respondents'),
            'translate' => true,
        ]);
        $this->metaModel->set('gsu_survey_description', [
            'label' => $this->_('Description'),
            'elementClass' => 'Exhibitor',
            'formatFunction' => [$this, 'formatDescription'],
        ]);

        $this->metaModel->set('gsu_survey_languages', [
            'label' => $this->_('Available languages'),
            'elementClass' => 'Exhibitor',
            'itemDisplay' => [$this, 'formatLanguages'],
        ]);

        $this->metaModel->set('gso_source_name', [
            'label' => $this->_('Source'),
            'elementClass' => 'Exhibitor',
        ]);
        $this->metaModel->set('gsu_surveyor_active', [
            'label' => $this->_('Active in source'),
            'elementClass' => 'Exhibitor',
            'multiOptions' => $yesNo,
        ]);
        $this->metaModel->set('gsu_surveyor_id',    'label', $this->_('Source survey id'),
                'elementClass', 'Exhibitor'
                );
        $this->metaModel->set('gsu_status_show',        'label', $this->_('Status in source'),
                'elementClass', 'Exhibitor'
                );
        $this->metaModel->set('gsu_survey_warnings',        'label', $this->_('Warnings'),
                'elementClass', 'Exhibitor',
                'formatFunction', [$this, 'formatWarnings']
                );

        $message = $this->_('Active');
        if (isset($this->config['app']['name'])) {
            $message = sprintf($this->_('Active in %s'), $this->config['app']['name']);
        }
        $this->metaModel->set('gsu_active', [
            'label' => $message,
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
            RequireOtherFieldValidator::$otherField => 'gsu_id_primary_group',
            'validators[requirePrim]' => RequireOtherFieldValidator::class
        ]);

        $this->metaModel->set('gsu_id_primary_group', [
            'label' => $this->_('Group'),
            'description' => $this->_('If empty, survey will never show up!'),
            'multiOptions' => $this->accessRepository->getGroups()
        ]);
        $this->metaModel->set('gsu_answers_by_group', [
            'label' => $this->_('Show answers by groups'),
            'description' => $this->_('Answers can be seen only by groups selected.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo
        ]);
        $this->metaModel->set('gsu_answer_groups', [
            'label' => $this->_('Answer groups'),
            'description' => $this->_('The groups that may see the answers or none.'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $this->accessRepository->getActiveStaffGroups(),
            'required' => false,
            'type' => new ConcatenatedType('|', $this->_(', ')),
        ]);

        $this->metaModel->set('gsu_allow_export', [
            'label' => $this->_('Export allowed'),
            'description' => $this->_('Allow the export of answers?'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);

        $mailCodes = $this->surveyRepository->getSurveyMailCodes();
        if (count($mailCodes) > 1) {
            $this->metaModel->set('gsu_mail_code', [
                'label' => $this->_('Mail code'),
                'description' => $this->_('When mails are sent for this survey'),
                'multiOptions' => $mailCodes,
            ]);
        } elseif (1 == count($mailCodes)) {
            reset($mailCodes);
            $this->metaModel->set('gsu_mail_code', [
                'default' => key($mailCodes),
            ]);
        }

        $this->metaModel->set('gsu_insertable', [
            'label'  => $this->_('Insertable'),
            'description' => $this->_('Can this survey be manually inserted into a track?'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);

        $this->metaModel->set('gsu_valid_for_length', [
            'label' => $this->_('Add to inserted end date'),
            'description' => $this->_('Add to the start date to calculate the end date when inserting.'),
            'filter' => Digits::class,
        ]);
        $this->metaModel->set('gsu_valid_for_unit', [
            'label' => $this->_('Inserted end date unit'),
            'description' => $this->_('The unit used to calculate the end date when inserting the survey.'),
            'multiOptions' => $this->translatedUtil->getPeriodUnits(),
        ]);
        $this->metaModel->set('gsu_insert_organizations', [
            'label' => $this->_('Insert organizations'),
            'description' => $this->_('The organizations where the survey may be inserted.'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $this->organizationRepository->getOrganizationsWithRespondents(),
            'required' => true,
            'type' => new ConcatenatedType('|', $this->_(', ')),
        ]);

        $this->metaModel->setMulti(['track_count', 'track_usage', 'calc_duration'], [SqlRunnerInterface::NO_SQL => true]);
        $this->metaModel->setMulti(['gsu_duration', 'gsu_survey_pdf', 'gsu_result_field']);

        $this->metaModel->set('gsu_code', [
            'label' => $this->_('Survey code'),
            'description' => $this->_('Optional code name to link the survey to program code.'),
            'size' => 10,
        ]);

        $this->metaModel->set('gsu_export_code', [
            'label' => $this->_('Survey export code'),
            'description' => $this->_('A unique code indentifying this survey during track import'),
            'size' => 20,
        ]);

        return $this;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a value
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \Zend_Db_Expr|string
     */
    public function calculateDuration($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return $this->_('incalculable');
        }

        $fields['cnt'] = 'COUNT(DISTINCT gto_id_token)';
        $fields['avg'] = 'ROUND(AVG(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END))';
        $fields['std'] = 'STDDEV_POP(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';
        $fields['min'] = 'MIN(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';
        $fields['max'] = 'MAX(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';

        $select = $this->tracker->getTokenSelect($fields);
        $select->forSurveyId($surveyId)
                ->onlyCompleted();

        $row = $select->fetchRow();
        if ($row) {
            $seq = new Sequence();
            $seq->setGlue(Html::create('br'));

            $seq->sprintf($this->_('Answered surveys: %d.'), $row['cnt']);
            $seq->sprintf(
                $this->_('Average answer time: %s.'),
                $row['cnt'] ? $this->translatedUtil->formatTimeFromSeconds($row['avg']) : $this->_('n/a')
            );
            $seq->sprintf(
                $this->_('Standard deviation: %s.'),
                $row['cnt'] ? $this->translatedUtil->formatTimeFromSeconds($row['std']) : $this->_('n/a')
            );
            $seq->sprintf(
                $this->_('Minimum time: %s.'),
                $row['cnt'] ? $this->translatedUtil->formatTimeFromSeconds($row['min']) : $this->_('n/a')
            );
            $seq->sprintf(
                $this->_('Maximum time: %s.'),
                $row['cnt'] ? $this->translatedUtil->formatTimeFromSeconds($row['max']) : $this->_('n/a')
            );

            if ($row['cnt']) {
                // Picked solution from http://stackoverflow.com/questions/1291152/simple-way-to-calculate-median-with-mysql
                $sql = "SELECT t1.gto_duration_in_sec as median_val
                            FROM (SELECT @rownum:=@rownum+1 as `row_number`, gto_duration_in_sec
                                    FROM gems__tokens, (SELECT @rownum:=0) r
                                    WHERE gto_id_survey = ? AND gto_completion_time IS NOT NULL
                                    ORDER BY gto_duration_in_sec
                                ) AS t1,
                                (SELECT count(*) as total_rows
                                    FROM gems__tokens
                                    WHERE gto_id_survey = ? AND gto_completion_time IS NOT NULL
                                ) as t2
                            WHERE t1.row_number = floor(total_rows / 2) + 1";
                $med = $this->resultFetcher->fetchOne($sql, [$surveyId, $surveyId]);
                if ($med) {
                    $seq->sprintf($this->_('Median value: %s.'), $this->translatedUtil->formatTimeUnknown($med));
                }
                // \MUtil\EchoOut\EchoOut::track($row, $med, $sql, $select->getSelect()->__toString());
            } else {
                $seq->append(sprintf($this->_('Median value: %s.'), $this->_('n/a')));
            }

            return $seq;
        }
        return $this->_('incalculable');
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a value
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return string
     */
    public function calculateTrackCount($value, $isNew = false, $name = null, array $context = [], $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return '';
        }

        $select = $this->resultFetcher->getSelect('gems__rounds');
        $select->columns(['useCnt' => new Expression('COUNT(*)'), 'trackCnt' => new Expression('COUNT(DISTINCT gro_id_track)')]);
        $select->join('gems__tracks', 'gtr_id_track = gro_id_track', [], Select::JOIN_LEFT)
                ->where(['gro_id_survey' => $surveyId]);
        $counts = $this->resultFetcher->fetchRow($select);
        //dump($surveyId, $counts, $select->getSqlString($this->resultFetcher->getPlatform()));

        if (isset($counts['useCnt']) || isset($counts['trackCnt'])) {
            return sprintf($this->_('%d times in %d track(s).'), $counts['useCnt'], $counts['trackCnt']);
        } else {
            return $this->_('Not in any track.');
        }
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a value
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return string
     */
    public function calculateTrackUsage($value, $isNew = false, $name = null, array $context = [], $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return 0;
        }

        $select = $this->resultFetcher->getSelect('gems__tracks');
        $select->columns(['gtr_track_name'])
            ->join('gems__rounds', 'gro_id_track = gtr_id_track', ['useCnt' => new Expression('COUNT(*)')], Select::JOIN_LEFT)
            ->where(['gro_id_survey' => $surveyId])
            ->group('gtr_track_name');
        $usage = $this->resultFetcher->fetchPairs($select);

        if ($usage) {
            $seq = new Sequence();
            $seq->setGlue(Html::create('br'));
            foreach ($usage as $track => $count) {
                $seq[] = sprintf($this->plural(
                        '%d time in %s track.',
                        '%d times in %s track.',
                        $count), $count, $track);
            }
            return $seq;

        } else {
            return $this->_('Not in any track.');
        }
    }


    /**
     * Strip all the tags, but keep the escaped characters
     *
     * @param string $value
     * @return Raw
     */
    public static function formatDescription($value)
    {
        return Html::raw(strip_tags((string)$value));
    }

    /**
     * Divide available languages between base and additional languages and format output
     *
     * @param string $value
     * @param boolean $native Return native translation
     * @return string $seq
     */
    public function formatLanguages($value, $native = false)
    {
        $split = explode(', ', (string)$value);

        foreach ($split as $key => $locale) {
            $localized = '';
            if (Locales::exists($locale)) {
                if ($native) {
                    $localized = Languages::getName($locale, $locale);
                } else {
                    $localized = Languages::getName($locale, $this->locale->getLanguage());
                }
            }
            $split[$key] = $localized ? $localized : $locale;
        }

        $seq = new Sequence();
        $seq->setGlue(Html::create('br'));

        $seq->append(sprintf($this->_('Base: %s'), $split[0]));
        if (isset($split[1])) {
            $seq->append(sprintf($this->_('Additional: %s'), implode(', ', array_slice($split, 1))));
        }

        return $seq;
    }

    /**
     * Return string on empty value
     *
     * @param mixed $value
     * @return mixed $value
     */
    public static function formatWarnings($value)
    {
        if (is_null($value)) {
            $value = new HtmlElement('em', '(none)');
        }

        return $value;
    }

    public function hasNew(): bool
    {
        return (bool) $this->saveTables;
    }
}
