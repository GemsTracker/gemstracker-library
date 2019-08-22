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
        $model = $this->loader->getModels()->getSurveyMaintenanceModel();

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
        $output[30] = array('gsu_active', \MUtil_Html::raw(' '), 'track_count', $br, 'gsu_insertable', $br, 'gsu_id_primary_group');
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
            $filter[] = "(gsu_survey_languages IS NOT NULL AND gsu_survey_languages LIKE '%" . $filter['survey_languages'] . "%')";
            
            // default:

            unset($filter['survey_languages']);
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