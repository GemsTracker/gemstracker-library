<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Generic controller class for showing and editing respondents
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_SurveyMaintenanceAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'columns'   => 'getBrowseColumns',
        'extraSort' => array(
            'gsu_survey_name' => SORT_ASC,
            ),
        'menuEditActions' => array('edit', 'pdf'),
        );

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public $cacheTags = array('surveys', 'tracks');

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = array(
        'surveyId'        => 'getSurveyId',
        );

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = array('ModelFormSnippetGeneric', 'Survey\\SurveyQuestionsSnippet');

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Survey\\SurveyMaintenanceSearchSnippet');

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = array('surveyId' => 'getSurveyId');

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Survey\\SurveyQuestionsSnippet'
        );

    /**
     * Import answers to a survey
     */
    public function answerImportAction()
    {
        $controller   = 'answers';
        $importLoader = $this->loader->getImportLoader();

        $params['defaultImportTranslator'] = $importLoader->getDefaultTranslator($controller);
        $params['formatBoxClass']          = 'browser table';
        $params['importer']                = $importLoader->getImporter($controller);
        $params['importLoader']            = $importLoader;
        $params['tempDirectory']           = $importLoader->getTempDirectory();
        $params['importTranslators']       = $importLoader->getTranslators($controller);

        $this->addSnippets('Survey_AnswerImportSnippet', $params);
    }

    /**
     * Import answers to any survey
     */
    public function answerImportsAction()
    {
        $this->answerImportAction();
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
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

        $select = $this->loader->getTracker()->getTokenSelect($fields);
        $select->forSurveyId($surveyId)
                ->onlyCompleted();

        $row = $select->fetchRow();
        if ($row) {
            $trs = $this->util->getTranslated();
            $seq = new \MUtil_Html_Sequence();
            $seq->setGlue(\MUtil_Html::create('br', $this->view));

            $seq->sprintf($this->_('Answered surveys: %d.'), $row['cnt']);
            $seq->sprintf(
                    $this->_('Average answer time: %s.'),
                    $row['cnt'] ? $trs->formatTimeUnknown($row['avg']) : $this->_('n/a')
                    );
            $seq->sprintf(
                    $this->_('Standard deviation: %s.'),
                    $row['cnt'] ? $trs->formatTimeUnknown($row['std']) : $this->_('n/a')
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
                $med = $this->db->fetchOne($sql, array($surveyId, $surveyId));
                if ($med) {
                    $seq->sprintf($this->_('Median value: %s.'), $trs->formatTimeUnknown($med));
                }
                // \MUtil_Echo::track($row, $med, $sql, $select->getSelect()->__toString());
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
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function calculateTrackCount($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return 0;
        }

        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__rounds', array('useCnt' => 'COUNT(*)', 'trackCnt' => 'COUNT(DISTINCT gro_id_track)'));
        $select->joinLeft('gems__tracks', 'gtr_id_track = gro_id_track', array())
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
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function calculateTrackUsage($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return 0;
        }

        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__tracks', array('gtr_track_name'));
        $select->joinLeft('gems__rounds', 'gro_id_track = gtr_id_track', array('useCnt' => 'COUNT(*)'))
                ->where('gro_id_survey = ?', $surveyId)
                ->group('gtr_track_name');
        $usage = $this->db->fetchPairs($select);

        if ($usage) {
            $seq = new \MUtil_Html_Sequence();
            $seq->setGlue(\MUtil_Html::create('br'));
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
     * Check the tokens for a single survey
     */
    public function checkAction()
    {
        $surveyId = $this->getSurveyId();
        $where    = $this->db->quoteInto('gto_id_survey = ?', $surveyId);

        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheck' . $surveyId, $this->currentUser->getUserId(), $where);

        $title = sprintf($this->_('Checking for the %s survey for answers .'),
                $this->db->fetchOne("SELECT gsu_survey_name FROM gems__surveys WHERE gsu_id_survey = ?", $surveyId));
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Survey\\CheckAnswersInformation',
                'itemDescription', $this->_('This task checks all tokens using this survey for answers.')
                );
    }

    /**
     * Check the tokens for all surveys
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheckAll', $this->currentUser->getUserId());

        $title = $this->_('Checking for all surveys for answers .');
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Survey\\CheckAnswersInformation',
                'itemDescription', $this->_('This task checks all tokens for all surveys for answers.')
                );
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $dbLookup   = $this->util->getDbLookup();
        $survey     = null;
        $translated = $this->util->getTranslated();
        $yesNo      = $translated->getYesNo();

        if ($detailed) {
            $surveyId = $this->_getIdParam();

            if ($surveyId) {
                $survey = $this->loader->getTracker()->getSurvey($surveyId);
            }
        }

        $model = new \Gems_Model_JoinModel('surveys', 'gems__surveys', 'gus');
        $model->addTable('gems__sources', array('gsu_id_source' => 'gso_id_source'));
        $model->setCreate(false);

        $model->addColumn(
                "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
                'gsu_has_pdf'
                );
        $model->addColumn(
                sprintf(
                        "CASE WHEN (gsu_status IS NULL OR gsu_status = '') THEN '%s' ELSE gsu_status END",
                        $this->_('OK')
                        ),
                'gsu_status_show',
                'gsu_status'
                );
        $model->addColumn(
                "CASE WHEN gsu_surveyor_active THEN '' ELSE 'deleted' END",
                'row_class'
                );

        $model->resetOrder();

        $model->set('gsu_survey_name',        'label', $this->_('Name'),
                'elementClass', 'Exhibitor'
                );
        $model->set('gsu_survey_description', 'label', $this->_('Description'),
                'elementClass', 'Exhibitor',
                'formatFunction', array(__CLASS__, 'formatDescription')
                );
        $model->set('gso_source_name',        'label', $this->_('Source'),
                'elementClass', 'Exhibitor');
        $model->set('gsu_surveyor_active',    'label', $this->_('Active in source'),
                'elementClass', 'Exhibitor',
                'multiOptions', $yesNo
                );
        $model->set('gsu_surveyor_id',    'label', $this->_('Source survey id'),
                'elementClass', 'Exhibitor'
                );
        $model->set('gsu_status_show',        'label', $this->_('Status in source'),
                'elementClass', 'Exhibitor');
        $model->set('gsu_active',             'label', sprintf($this->_('Active in %s'), $this->project->getName()),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $model->set('gsu_id_primary_group',   'label', $this->_('Group'),
                'description', $this->_('If empty, survey will never show up!'),
                'multiOptions', $dbLookup->getGroups()
                );

        if ($detailed) {
            $model->addDependency('CanEditDependency', 'gsu_surveyor_active', array('gsu_active'));
            $model->set('gsu_active',
                    'validators[group]', new \MUtil_Validate_Require(
                            $model->get('gsu_active', 'label'),
                            'gsu_id_primary_group',
                            $model->get('gsu_id_primary_group', 'label')
                            ));
        }

        $model->set('gsu_insertable',         'label', $this->_('Insertable'),
                'description', $this->_('Can this survey be manually inserted into a track?'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo,
                'onclick', 'this.form.submit()'
                );
        if ($detailed) {
            $model->set('gsu_valid_for_length', 'label', $this->_('Add to inserted end date'),
                    'description', $this->_('Add to the start date to calculate the end date when inserting.'),
                    'filter', 'Int'
                    );
            $model->set('gsu_valid_for_unit',   'label', $this->_('Inserted end date unit'),
                    'description', $this->_('The unit used to calculate the end date when inserting the survey.'),
                    'multiOptions', $translated->getPeriodUnits()
                    );
            $model->set('gsu_insert_organizations', 'label', $this->_('Insert organizations'),
                    'description', $this->_('The organizations where the survey may be inserted.'),
                    'elementClass', 'MultiCheckbox',
                    'multiOptions', $dbLookup->getOrganizations(),
                    'required', true
                    );
            $ct = new \MUtil_Model_Type_ConcatenatedRow('|', $this->_(', '));
            $ct->apply($model, 'gsu_insert_organizations');

            if (($action == 'create') || ($action == 'edit')) {
                $model->set('toggleOrg',
                        'elementClass', 'ToggleCheckboxes',
                        'selectorName', 'gsu_insert_organizations'
                        );
            }

            $switches = array(
                0 => array(
                    'gsu_valid_for_length'     => array('elementClass' => 'Hidden', 'label' => null),
                    'gsu_valid_for_unit'       => array('elementClass' => 'Hidden', 'label' => null),
                    'gsu_insert_organizations' => array('elementClass' => 'Hidden', 'label' => null),
                    'toggleOrg'                => array('elementClass' => 'Hidden', 'label' => null),
                ),
            );
            $model->addDependency(array('ValueSwitchDependency', $switches), 'gsu_insertable');
        }

        if ($detailed) {
            $model->set('track_usage',          'label', $this->_('Usage'),
                    'elementClass', 'Exhibitor',
                    'noSort', true,
                    'no_text_search', true
                    );
            $model->setOnLoad('track_usage', array($this, 'calculateTrackUsage'));

            $model->set('calc_duration',        'label', $this->_('Duration calculated'),
                    'elementClass', 'Html',
                    'noSort', true,
                    'no_text_search', true
                    );
            $model->setOnLoad('calc_duration', array($this, 'calculateDuration'));

            $model->set('gsu_duration',         'label', $this->_('Duration description'),
                    'description', $this->_('Text to inform the respondent, e.g. "20 seconds" or "1 minute".')
                    );
            if ($survey instanceof \Gems_Tracker_Survey) {
                $surveyFields = $this->util->getTranslated()->getEmptyDropdownArray() +
                    $survey->getQuestionList($this->locale->getLanguage());
                $model->set('gsu_result_field', 'label', $this->_('Result field'),
                        'multiOptions', $surveyFields
                        );
                // $model->set('gsu_agenda_result',         'label', $this->_('Agenda field'));
            }
        } else {
            $model->set('track_count',          'label', ' ',
                    'elementClass', 'Exhibitor',
                    'noSort', true,
                    'no_text_search', true
                    );
            $model->setOnLoad('track_count', array($this, 'calculateTrackCount'));
        }
        $model->set('gsu_code',                 'label', $this->_('Survey code'),
                'description', $this->_('Optional code name to link the survey to program code.'),
                'size', 10);

        $model->set('gsu_export_code',               'label', $this->_('Survey export code'),
                'description', $this->_('A unique code indentifying this survey during track import'),
                'size', 20);

        if ($detailed) {
            $events = $this->loader->getEvents();
            $beforeOptions = $events->listSurveyBeforeAnsweringEvents();
            if (count($beforeOptions) > 1) {
                $model->set('gsu_beforeanswering_event', 'label', $this->_('Before answering'),
                        'multiOptions', $beforeOptions,
                        'elementClass', 'Select'
                        );
            }
            $completedOptions = $events->listSurveyCompletionEvents();
            if (count($completedOptions) > 1) {
                $model->set('gsu_completed_event',       'label', $this->_('After completion'),
                        'multiOptions', $completedOptions,
                        'elementClass', 'Select'
                        );
            }
            $displayOptions = $events->listSurveyDisplayEvents();
            if (count($displayOptions) > 1) {
                $model->set('gsu_display_event',         'label', $this->_('Answer display'),
                        'multiOptions', $displayOptions,
                        'elementClass', 'Select'
                        );
            }

            if (('show' !== $action) || $survey->hasPdf()) {
                // Only the action changes from the current page
                // and the right to see the pdf is the same as
                // the right to see this page.
                $pdfLink = \MUtil_Html::create(
                        'a',
                        array($this->getRequest()->getActionKey() => 'pdf'),
                        array(
                            'class'   => 'pdf',
                            'target'  => '_blank',
                            'type'    => 'application/pdf',
                            'onclick' => 'event.cancelBubble = true;',
                            )
                        );

                $model->set('gsu_survey_pdf', 'label', 'Pdf',
                        'accept', 'application/pdf',
                        'destination', $this->loader->getPdf()->getUploadDir('survey_pdfs'),
                        'elementClass', 'File',
                        'extension', 'pdf',
                        'filename', $surveyId,
                        'required', false,
                        'itemDisplay', $pdfLink,
                        'validators[pdf]', new \MUtil_Validate_Pdf()
                        );
            }
        }

        return $model;
    }

    /**
     * Strip all the tags, but keep the escaped characters
     *
     * @param string $value
     * @return \MUtil_Html_Raw
     */
    public static function formatDescription($value)
    {
        return \MUtil_Html::raw(strip_tags($value));
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns()
    {
        $br = \MUtil_Html::create('br');

        $output[10] = array('gsu_survey_name', $br, 'gsu_survey_description');
        $output[20] = array('gsu_surveyor_active', \MUtil_Html::raw($this->_(' [')), 'gso_source_name',
            \MUtil_Html::raw($this->_(']')), $br, 'gsu_status_show', $br, 'gsu_insertable');
        $output[30] = array('gsu_active', \MUtil_Html::raw(' '), 'track_count', $br, 'gsu_id_primary_group');
        $output[40] = array('gsu_surveyor_id', $br, 'gsu_code', $br, 'gsu_export_code');

        return $output;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Surveys');
    }

     /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        if (array_key_exists('status', $filter)) {
            switch ($filter['status']) {
                case 'sok':
                    $filter['gsu_active'] = 0;
                    $filter[] = "(gsu_status IS NULL OR gsu_status IN ('', 'OK'))";
                    break;

                case 'nok':
                    $filter[] = "(gsu_status IS NOT NULL AND gsu_status NOT IN ('', 'OK'))";
                    break;

                case 'act':
                    $filter['gsu_active'] = 1;

                // default:

            }
            unset($filter['status']);
        }

        return $filter;
    }

    /**
     * Return the survey id (and set a menu var)
     *
     * @return int
     */
    public function getSurveyId()
    {
        $id = $this->_getIdParam();

        $survey = $this->loader->getTracker()->getSurvey($id);

        $this->menu->getParameterSource()->offsetSet('gsu_active', $survey->isActive() ? 1 : 0);

        return $id;
    }

   /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }

    /**
     * Open pdf linked to survey
     */
    public function pdfAction()
    {
        // Make sure nothing else is output
        $this->initRawOutput();

        // Output the PDF
        $this->loader->getPdf()->echoPdfBySurveyId($this->_getParam(\MUtil_Model::REQUEST_ID));
    }

}