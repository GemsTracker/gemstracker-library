<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use MUtil\Validate\RequireOtherField;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class SurveyMaintenanceModel extends \Gems\Model\JoinModel
{

    /**
     * @var array
     */
    protected $config;
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    public $project;

    /**
     * @var \Gems\Util\Translated
     */
    protected $translatedUtil;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     *
     * @param string $name
     */
    public function __construct($name = 'surveymaintenance')
    {
        parent::__construct($name, 'gems__surveys', 'gsu');
        $this->addTable('gems__sources', ['gsu_id_source' => 'gso_id_source']);
        $this->setCreate(false);
    }

    /**
     * Set those settings needed for the browse display
     *
     * @param boolean $addCount Add a count in rounds column
     * @param boolean $editing True when setting editing mode
     * @return \Gems\Model\SurveyMaintenanceModel
     */
    public function applyBrowseSettings($addCount = true, $editing = false)
    {
        $dbLookup   = $this->util->getDbLookup();
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

        $this->resetOrder();

        $this->set('gsu_survey_name',        'label', $this->_('Name'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_external_description', 'label', $this->_('External Name'),
                   'description', $this->_('Optional alternate external description for communication with respondents'),
                   'translate', true
        );
        $this->set('gsu_survey_description', 'label', $this->_('Description'),
                'elementClass', 'Exhibitor',
                'formatFunction', [$this, 'formatDescription']
                );

        $this->set('gsu_survey_languages',        'label', $this->_('Available languages'),
                'elementClass', 'Exhibitor');

        $this->set('gso_source_name',        'label', $this->_('Source'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_surveyor_active',    'label', $this->_('Active in source'),
                'elementClass', 'Exhibitor',
                'multiOptions', $yesNo
                );
        $this->set('gsu_surveyor_id',    'label', $this->_('Source survey id'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_status_show',        'label', $this->_('Status in source'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_survey_warnings',        'label', $this->_('Warnings'),
                'elementClass', 'Exhibitor',
                'formatFunction', [$this, 'formatWarnings']
                );

        $message = $this->_('Active');
        if (isset($config['app']['name'])) {
            $message = sprintf($this->_('Active in %s'), $config['app']['name']);
        }

        $this->set('gsu_active', 'label', sprintf($this->_('Active in %s'), $message),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $this->set('gsu_id_primary_group', 'label', $this->_('Group'),
                'description', $this->_('If empty, survey will never show up!'),
                'multiOptions', $dbLookup->getGroups()
                );
        $this->set('gsu_answers_by_group', 'label', $this->_('Show answers by groups'),
                   'description', $this->_('Answers can be seen only by groups selected.'),
                   'elementClass', 'Checkbox',
                   'multiOptions', $yesNo
        );
        $this->set('gsu_allow_export', 'label', $this->_('Export allowed'),
                   'description', $this->_('Allow the export of answers?'),
                   'elementClass', 'Checkbox',
                   'multiOptions', $yesNo
        );

        $mailCodes = $dbLookup->getSurveyMailCodes();
        if (count($mailCodes) > 1) {
            $this->set('gsu_mail_code', 'label', $this->_('Mail code'),
                       'description', $this->_('When mails are sent for this survey'),
                       'multiOptions', $mailCodes
            );
        } elseif (1 == count($mailCodes)) {
            reset($mailCodes);
            $this->set('gsu_mail_code', 'default', key($mailCodes));
        }

        $this->set('gsu_insertable',         'label', $this->_('Insertable'),
                'description', $this->_('Can this survey be manually inserted into a track?'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );

        if ($addCount) {
            $this->set('track_count',          'label', ' ',
                    'elementClass', 'Exhibitor',
                    'noSort', true,
                    'no_text_search', true
                    );
            $this->setOnLoad('track_count', [$this, 'calculateTrackCount']);
        }

        $this->set('gsu_code',                 'label', $this->_('Survey code'),
                'description', $this->_('Optional code name to link the survey to program code.'),
                'size', 10);

        $this->set('gsu_export_code',               'label', $this->_('Survey export code'),
                'description', $this->_('A unique code indentifying this survey during track import'),
                'size', 20);

        if ($this->project->translateDatabaseFields() && ! $editing) {
            $this->loader->getModels()->addDatabaseTranslations($this);
        }

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @param int $surveyId The survey to show the details for
     * @param boolean $editing True when setting editing mode
     * @return \Gems\Model\SurveyMaintenanceModel
     */
    public function applyDetailSettings($surveyId = null, $editing = false)
    {
        $this->applyBrowseSettings(false, $editing);
        $dbLookup   = $this->util->getDbLookup();

        $this->resetOrder();

        $this->setMulti([
            'gsu_survey_name',
            'gsu_external_description',
            'gsu_survey_description',
            'gsu_survey_languages',
            'gso_source_name',
            'gsu_surveyor_active',
            'gsu_surveyor_id',
            'gsu_status_show',
            'gsu_survey_warnings',
            'gsu_active',
            'gsu_id_primary_group',
            'gsu_answers_by_group',
            'gsu_answer_groups',
            'gsu_allow_export',
            'gsu_mail_code',
            'gsu_insertable'
            ]);

        $this->set('gsu_survey_languages', 'itemDisplay', [$this, 'formatLanguages']);

        $ct = new \MUtil\Model\Type\ConcatenatedRow('|', $this->_(', '));

        $this->set('gsu_answer_groups', 'label', $this->_('Answer groups'),
                   'description', $this->_('The groups that may see the answers or none.'),
                   'elementClass', 'MultiCheckbox',
                   'multiOptions', $dbLookup->getActiveStaffGroups(),
                   'required', false
        );
        $ct->apply($this, 'gsu_answer_groups');

        $this->set('gsu_active',
                'validators[group]', new RequireOtherField(
                        $this->get('gsu_active', 'label'),
                        'gsu_id_primary_group',
                        $this->get('gsu_id_primary_group', 'label')
                        ));

        $this->set('gsu_valid_for_length', 'label', $this->_('Add to inserted end date'),
                'description', $this->_('Add to the start date to calculate the end date when inserting.'),
                'filter', 'Int'
                );
        $this->set('gsu_valid_for_unit',   'label', $this->_('Inserted end date unit'),
                'description', $this->_('The unit used to calculate the end date when inserting the survey.'),
                'multiOptions', $this->translatedUtil->getPeriodUnits()
                );
        $this->set('gsu_insert_organizations', 'label', $this->_('Insert organizations'),
                'description', $this->_('The organizations where the survey may be inserted.'),
                'elementClass', 'MultiCheckbox',
                'multiOptions', $dbLookup->getOrganizationsWithRespondents(),
                'required', true
                );
        $ct->apply($this, 'gsu_insert_organizations');

        $hideElement = ['elementClass' => 'Hidden', 'label' => null];
        $this->addDependency(['ValueSwitchDependency', [0 => ['gsu_answer_groups' => $hideElement]]], 'gsu_answers_by_group');
        $switches = [0 => [
            'gsu_valid_for_length'     => $hideElement,
            'gsu_valid_for_unit'       => $hideElement,
            'gsu_insert_organizations' => $hideElement,
            'toggleOrg'                => $hideElement,
        ],];
        $this->addDependency(['ValueSwitchDependency', $switches], 'gsu_insertable');

        $this->set('track_usage',          'label', $this->_('Usage'),
                'elementClass', 'Exhibitor',
                'noSort', true,
                'no_text_search', true
                );
        $this->setOnLoad('track_usage', [$this, 'calculateTrackUsage']);

        $this->set('calc_duration',        'label', $this->_('Duration calculated'),
                'elementClass', 'Html',
                'noSort', true,
                'no_text_search', true
                );
        $this->setOnLoad('calc_duration', [$this, 'calculateDuration']);

        $this->set('gsu_duration',         'label', $this->_('Duration description'),
                'description', $this->_('Text to inform the respondent, e.g. "20 seconds" or "1 minute".'),
                'translate', true
                );

        $survey = null;
        if ($surveyId) {
            $survey = $this->loader->getTracker()->getSurvey($surveyId);
        }

        if ($survey instanceof \Gems\Tracker\Survey) {
            $surveyFields = $this->translatedUtil->getEmptyDropdownArray() +
                $survey->getQuestionList(null);
            $this->set('gsu_result_field', 'label', $this->_('Result field'),
                    'multiOptions', $surveyFields
                    );
            // $model->set('gsu_agenda_result',         'label', $this->_('Agenda field'));
        }

        $this->set('gsu_code');
        $this->set('gsu_export_code');

        $events = $this->loader->getEvents();
        $beforeOptions = $events->listSurveyBeforeAnsweringEvents();
        if (count($beforeOptions) > 1) {
            $this->set('gsu_beforeanswering_event', 'label', $this->_('Before answering'),
                    'multiOptions', $beforeOptions,
                    'elementClass', 'Select'
                    );
        }
        $completedOptions = $events->listSurveyCompletionEvents();
        if (count($completedOptions) > 1) {
            $this->set('gsu_completed_event',       'label', $this->_('After completion'),
                    'multiOptions', $completedOptions,
                    'elementClass', 'Select'
                    );
        }
        $displayOptions = $events->listSurveyDisplayEvents();
        if (count($displayOptions) > 1) {
            $this->set('gsu_display_event',         'label', $this->_('Answer display'),
                    'multiOptions', $displayOptions,
                    'elementClass', 'Select'
                    );
        }

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param boolean Are we creating a new survey
     * @param int $surveyId The survey to show the details for
     * @return \Gems\Model\SurveyMaintenanceModel
     */
    public function applyEditSettings($create = false, $surveyId = null)
    {
        $this->applyDetailSettings($surveyId, true);

        if ($this->currentUser->hasPrivilege('pr.survey-maintenance.answer-groups')) {
            $this->addDependency('CanEditDependency', 'gsu_answers_by_group', ['gsu_answer_groups']);
        } else {
            $this->setMulti(['gsu_answers_by_group', 'gsu_answer_groups', 'gsu_allow_export'], ['readonly' => 'readonly', 'disabled' => 'disabled']);
        }
        $this->addDependency('CanEditDependency', 'gsu_surveyor_active', ['gsu_active']);

        $order = $this->getOrder('gsu_insert_organizations') + 1;

        $this->set('toggleOrg',
                    'elementClass', 'ToggleCheckboxes',
                    'selectorName', 'gsu_insert_organizations',
                    'order', $order
                    );

        $this->set('gsu_survey_pdf', 'label', 'Pdf',
                        'accept', 'application/pdf',
                        'destination', $this->loader->getPdf()->getUploadDir('survey_pdfs'),
                        'elementClass', 'File',
                        'extension', 'pdf',
                        'filename', $surveyId,
                        'required', false,
                        'validators[pdf]', new \MUtil\Validate\Pdf()
                        );

        if ($this->project->translateDatabaseFields()) {
            $this->loader->getModels()->addDatabaseTranslationEditFields($this);
        }

        return $this;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil\Date|\Zend_Db_Expr|string
     */
    public function calculateDuration($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return $this->_('incalculable');
        }

        $fields['cnt'] = 'COUNT(DISTINCT gto_id_token)';
        $fields['avg'] = 'AVG(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';
        $fields['std'] = 'STDDEV_POP(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';
        $fields['min'] = 'MIN(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';
        $fields['max'] = 'MAX(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';

        $select = $this->loader->getTracker()->getTokenSelect($fields);
        $select->forSurveyId($surveyId)
                ->onlyCompleted();

        $row = $select->fetchRow();
        if ($row) {
            $trs = $this->util->getTranslated();
            $seq = new \MUtil\Html\Sequence();
            $seq->setGlue(\MUtil\Html::create('br'));

            $seq->sprintf($this->_('Answered surveys: %d.'), $row['cnt']);
            $seq->sprintf(
                $this->_('Average answer time: %s.'),
                $row['cnt'] ? $trs->formatTimeUnknown($row['avg']) : $this->_('n/a')
            );
            $seq->sprintf(
                $this->_('Standard deviation: %s.'),
                $row['cnt'] ? $trs->formatTimeUnknown($row['std']) : $this->_('n/a')
            );
            $seq->sprintf(
                $this->_('Minimum time: %s.'),
                $row['cnt'] ? $trs->formatTimeUnknown($row['min']) : $this->_('n/a')
            );
            $seq->sprintf(
                $this->_('Maximum time: %s.'),
                $row['cnt'] ? $trs->formatTimeUnknown($row['max']) : $this->_('n/a')
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
                $med = $this->db->fetchOne($sql, [$surveyId, $surveyId]);
                if ($med) {
                    $seq->sprintf($this->_('Median value: %s.'), $trs->formatTimeUnknown($med));
                }
                // \MUtil\EchoOut\EchoOut::track($row, $med, $sql, $select->getSelect()->__toString());
            } else {
                $seq->append(sprintf($this->_('Median value: %s.'), $this->_('n/a')));
            }

            return $seq;
        }
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil\Date|\Zend_Db_Expr|string
     */
    public function calculateTrackCount($value, $isNew = false, $name = null, array $context = [], $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return 0;
        }

        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__rounds', ['useCnt' => 'COUNT(*)', 'trackCnt' => 'COUNT(DISTINCT gro_id_track)']);
        $select->joinLeft('gems__tracks', 'gtr_id_track = gro_id_track', [])
                ->where('gro_id_survey = ?', $surveyId);
        $counts = $select->query()->fetchObject();

        if ($counts && ($counts->useCnt || $counts->trackCnt)) {
            return sprintf($this->_('%d times in %d track(s).'), $counts->useCnt, $counts->trackCnt);
        } else {
            return $this->_('Not in any track.');
        }
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil\Date|\Zend_Db_Expr|string
     */
    public function calculateTrackUsage($value, $isNew = false, $name = null, array $context = [], $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return 0;
        }

        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__tracks', ['gtr_track_name']);
        $select->joinLeft('gems__rounds', 'gro_id_track = gtr_id_track', ['useCnt' => 'COUNT(*)'])
                ->where('gro_id_survey = ?', $surveyId)
                ->group('gtr_track_name');
        $usage = $this->db->fetchPairs($select);

        if ($usage) {
            $seq = new \MUtil\Html\Sequence();
            $seq->setGlue(\MUtil\Html::create('br'));
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
     * @return \MUtil\Html\Raw
     */
    public static function formatDescription($value)
    {
        return \MUtil\Html::raw(strip_tags((string)$value));
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
            if (\Zend_Locale::isLocale($locale, false)) {
                if ($native) {
                    $localized = \Zend_Locale::getTranslation($locale, 'Language', $locale);
                } else {
                    $localized = \Zend_Locale::getTranslation($locale, 'Language');
                }
            }
            $split[$key] = $localized ? $localized : $locale;
        }

        $seq = new \MUtil\Html\Sequence();
        $seq->setGlue(\MUtil\Html::create('br'));

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
            $value = new \MUtil\Html\HtmlElement('em', '(none)');
        }

        return $value;
    }
}
