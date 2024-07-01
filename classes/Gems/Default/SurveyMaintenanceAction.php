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
     * @var string Name used in survey model (for export)
     */
    protected $model_name = 'surveymaintenance';

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
     * Check the tokens for a single survey
     */
    public function checkAction()
    {
        $surveyId = $this->getSurveyId();
        $where    = $this->db->quoteInto('gto_id_survey = ?', $surveyId);

        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheck' . $surveyId, $this->currentUser->getUserId(), $where);
        $batch->setProgressTemplate($this->_('Remaining time: {remaining} - {msg}'));        

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
        $batch->setProgressTemplate($this->_('Remaining time: {remaining} - {msg}'));        

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
        $model = $this->loader->getModels()->getSurveyMaintenanceModel($this->model_name);

        if ($detailed) {
            $surveyId = $this->_getIdParam();
            if (('edit' == $action) || ('create' == $action)) {
                $model->applyEditSettings(('create' == $action), $surveyId);
            } else {
                $model->applyDetailSettings($surveyId);
            }
        } else {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Export model settings data
     */
    public function exportSettingsAction()
    {
        $step = $this->request->getParam('step');
        $post['type'] = 'TextExport';

        $this->autofilterParameters = $this->autofilterParameters + array(
                'browse'        => true,
                'containingId'  => 'autofilter_target',
                'keyboard'      => true,
                'onEmpty'       => 'getOnEmptyText',
                'sortParamAsc'  => 'asrt',
                'sortParamDesc' => 'dsrt',
            );

        $this->model_name = 'survey.settings';
        $model = $this->getExportModel();
        foreach (['gsu_survey_languages', 'gso_source_name', 'gsu_surveyor_active', 'gsu_insert_organizations', 'gsu_status_show', 'gsu_survey_warnings', 'track_usage', 'calc_duration'] as $name) {
            $model->remove($name, 'label');
        }
        $model->set('gsu_export_code', 'order', 1);

        if (isset($this->autofilterParameters['sortParamAsc'])) {
            $model->setSortParamAsc($this->autofilterParameters['sortParamAsc']);
        }
        if (isset($this->autofilterParameters['sortParamDesc'])) {
            $model->setSortParamDesc($this->autofilterParameters['sortParamDesc']);
        }

        $model->applyParameters($this->getSearchFilter(false), true);
        $model->addFilter(['gsu_export_code IS NOT NULL']);

        $this->accesslog->logChange($this->request, null, $model->getFilter());

        // Add any defaults.
        if (isset($this->autofilterParameters['extraFilter'])) {
            $model->addFilter($this->autofilterParameters['extraFilter']);
        }
        if (isset($this->autofilterParameters['extraSort'])) {
            $model->addSort($this->autofilterParameters['extraSort']);
        }

        $batch = $this->loader->getTaskRunnerBatch('export_settings');
        if (! $step) {
//            $batch->reset();
//            $step = 'batch';
//            $post['step'] = $step;
        }

        if ((!$step) || ($post && $step == 'form')) {
            $params = $this->_processParameters($this->exportParameters + ['exportClasses' => ['TextExport' => 'Text']]);
            $this->addSnippet($this->exportFormSnippets, $params);
            $batch->reset();
        } elseif ($step == 'batch') {
        // if ($step == 'batch') {
            $batch->setVariable('model', $model);
            if (!$batch->count()) {
                $batch->reset();
                $batch->minimalStepDurationMs = 2000;
                $batch->finishUrl = $this->view->url(array('step' => 'download'));

                $batch->setSessionVariable('files', array());

                $batch->addTask('Export_ExportCommand', $post['type'], 'addExport', $post);
                $batch->addTask('addTask', 'Export_ExportCommand', $post['type'], 'finalizeFiles', $post);

                $export = $this->loader->getExport()->getExport($post['type']);
                if ($snippet = $export->getHelpSnippet()) {
                    $this->addSnippet($snippet);
                }

                $batch->autoStart = true;
            }

            if (\MUtil_Console::isConsole()) {
                // This is for unit tests, if we want to be able to really export from
                // cli we need to place the exported file somewhere.
                // This is out of scope for now.
                $batch->runContinuous();
            } elseif ($batch->run($this->request)) {
                exit;
            } else {
                $controller = $this;

                if ($batch->isFinished()) {
                    /*\MUtil_Echo::track('finished');
                    $file = $batch->getSessionVariable('file');
                    if ((!empty($file)) && isset($file['file']) && file_exists($file['file'])) {
                        // Forward to download action
                        $this->_session->exportFile = $file;
                    }*/
                } else {
                    if ($batch->count()) {
                        $controller->html->append($batch->getPanel($controller->view, $batch->getProgressPercentage() . '%'));
                    } else {
                        $controller->html->pInfo($controller->_('Nothing to do.'));
                    }
                    $url = $this->getExportReturnLink();
                    if ($url) {
                        $controller->html->pInfo()->a(
                            $url,
                            array('class'=>'actionlink'),
                            $this->_('Back')
                        );
                    }
                }
            }
        } elseif ($step == 'download') {
            $file  = $batch->getSessionVariable('file');
            if ($file && is_array($file) && is_array($file['headers'])) {
                $this->view->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);

                foreach($file['headers'] as $header) {
                    header($header);
                }
                while (ob_get_level()) {
                    ob_end_clean();
                }
                readfile($file['file']);
                // Now clean up the file
                unlink($file['file']);

                exit;
            }
            $this->addMessage($this->_('Download no longer available.'), 'warning');
            $batch->reset();
        }
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

        $output[10] = array('gsu_survey_name', $br, 'gsu_survey_description', $br, 'gsu_survey_languages');
        $output[20] = array('gsu_surveyor_active', \MUtil_Html::raw($this->_(' [')), 'gso_source_name',
            \MUtil_Html::raw($this->_(']')), $br, 'gsu_status_show', $br, 'gsu_survey_warnings');

        $mailCodes = $this->util->getDbLookup()->getSurveyMailCodes();
        if (count($mailCodes) > 1) {
            $output[30] = array('gsu_active', \MUtil_Html::raw(' '), 'track_count', $br, 'gsu_mail_code', \MUtil_Html::raw(', '), 'gsu_insertable', $br, 'gsu_id_primary_group');
        } else {
            $output[30] = array('gsu_active', \MUtil_Html::raw(' '), 'track_count', $br, 'gsu_insertable', $br, 'gsu_id_primary_group');
        }
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
                    break;

                case 'anonymous':
                    $filter[] = "(gsu_status IS NOT NULL AND gsu_status NOT IN ('', 'OK') AND gsu_status LIKE '%Uses anonymous answers%')";
                    break;

                case 'datestamp':
                    $filter[] = "(gsu_status IS NOT NULL AND gsu_status NOT IN ('', 'OK') AND gsu_status LIKE '%Not date stamped%')";
                    break;

                case 'persistance':
                    $filter[] = "(gsu_status IS NOT NULL AND gsu_status NOT IN ('', 'OK') AND gsu_status LIKE '%Token-based persistence is disabled%')";
                    break;

                case 'noattributes':
                    $filter[] = "(gsu_status IS NOT NULL AND gsu_status NOT IN ('', 'OK') AND gsu_status LIKE '%Token attributes could not be created%')";
                    break;

                case 'notable':
                    $filter[] = "(gsu_status IS NOT NULL AND gsu_status NOT IN ('', 'OK') AND gsu_status LIKE '%No token table created%')";
                    break;

                case 'removed':
                    $filter[] = "(gsu_status IS NOT NULL AND gsu_status NOT IN ('', 'OK') AND gsu_status LIKE '%Survey was removed from source%')";
                    break;

                // default:

            }
            unset($filter['status']);
        }
        
        if (array_key_exists('survey_warnings', $filter)) {
            switch ($filter['survey_warnings']) {
                case 'withwarning':
                    $filter[] = "(gsu_survey_warnings IS NOT NULL AND gsu_survey_warnings NOT IN ('', 'OK'))";
                    break;
                case 'nowarning':
                    $filter[] = "(gsu_survey_warnings IS NULL OR gsu_survey_warnings IN ('', 'OK'))";
                    break;
                case 'autoredirect':
                    $filter[] = "(gsu_survey_warnings IS NOT NULL AND gsu_survey_warnings LIKE '%Auto-redirect is disabled%')";
                    break;
                case 'alloweditaftercompletion':
                    $filter[] = "(gsu_survey_warnings IS NOT NULL AND gsu_survey_warnings LIKE '%Editing after completion is enabled%')";
                    break;
                case 'allowregister':
                    $filter[] = "(gsu_survey_warnings IS NOT NULL AND gsu_survey_warnings LIKE '%Public registration is enabled%')";
                    break;
                case 'listpublic':
                    $filter[] = "(gsu_survey_warnings IS NOT NULL AND gsu_survey_warnings LIKE '%Public access is enabled%')";
                    break;

                // default:

            }
            unset($filter['survey_warnings']);
        }
        
        if (array_key_exists('survey_languages', $filter)) {
            $lang = trim($this->db->quote($filter['survey_languages']), "'");
            $filter[] = "(gsu_survey_languages IS NOT NULL AND gsu_survey_languages LIKE '%$lang%')";
            
            unset($filter['survey_languages']);
        }

        if (array_key_exists('events', $filter)) {
            
            switch ($filter['events']) {
                case '!Gems_Event_Survey':
                    $filter[] = "(gsu_beforeanswering_event IS NOT NULL OR gsu_completed_event IS NOT NULL OR gsu_display_event IS NOT NULL)";
                    break;
                case '!Gems_Event_SurveyBeforeAnsweringEventInterface':
                    $filter[] = "gsu_beforeanswering_event IS NOT NULL";
                    break;
                case '!Gems_Event_SurveyCompletedEventInterface':
                    $filter[] = "gsu_completed_event IS NOT NULL";
                    break;
                case '!Gems_Event_SurveyDisplayEventInterface':
                    $filter[] = "gsu_display_event IS NOT NULL";
                    break;
                default:
                    $class = $filter['events'];
                    if (class_exists($class, true)) {
                        if (is_subclass_of($class, 'Gems_Event_SurveyBeforeAnsweringEventInterface', true)) {
                            $filter['gsu_beforeanswering_event'] = $class;
                        } elseif (is_subclass_of($class, 'Gems_Event_SurveyCompletedEventInterface', true)) {
                            $filter['gsu_completed_event'] = $class;
                        } elseif (is_subclass_of($class, 'Gems_Event_SurveyDisplayEventInterface', true)) {
                            $filter['gsu_display_event'] = $class;
                        }
                    }
                    break;
            }
            unset($filter['events']);
        }
        
        // \MUtil_Echo::track($filter);
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
     * Import model settings data
     */
    public function importSettingsAction()
    {
        $params   = $this->_processParameters(['model' => $this->getExportModel()]);
        $snippets = [\Gems\Snippets\Import\ImportSurveySettingsSnippet::class];

        $this->addSnippets($snippets, $params);
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