<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Batch\BatchRunnerLoader;
use Gems\Db\ResultFetcher;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Locale\Locale;
use Gems\MenuNew\RouteHelper;
use Gems\Pdf;
use Gems\Repository\SurveyRepository;
use Gems\Tracker;
use Mezzio\Session\SessionInterface;
use MUtil\Model\ModelAbstract;
use MUtil\Translate\Translator;
use Psr\Http\Message\ResponseInterface;
use Zalt\Html\Html;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Generic controller class for showing and editing respondents
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class SurveyMaintenanceHandler extends ModelSnippetLegacyHandlerAbstract
{
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
    protected array $autofilterParameters = [
        'columns'   => 'getBrowseColumns',
        'extraSort' => [
            'gsu_survey_name' => SORT_ASC,
        ],
        'menuEditRoutes' => ['edit', 'pdf'],
    ];

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = ['surveys', 'tracks'];

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
    protected array $createEditParameters = [
        'surveyId'        => 'getSurveyId',
    ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = [
        'ModelFormSnippet',
        'Survey\\SurveyQuestionsSnippet'
    ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        'Generic\\ContentTitleSnippet',
        'Survey\\SurveyMaintenanceSearchSnippet'
    ];

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
    protected array $showParameters = [
        'surveyId' => 'getSurveyId'
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelDetailTableSnippet',
        'Survey\\SurveyQuestionsSnippet'
    ];

    public function __construct(
        RouteHelper $routeHelper,
        SnippetResponderInterface $responder,
        Translator $translate,
        protected Tracker $tracker,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected ResultFetcher $resultFetcher,
        protected ProjectOverloader $overLoader,
        protected Pdf $pdfEditor,
        protected SurveyRepository $surveyRepository,
        protected Locale $locale,
    ) {
        parent::__construct($routeHelper, $responder, $translate);
    }

    /**
     * Import answers to a survey
     */
    public function answerImportAction()
    {
        /*$controller   = 'answers';
        $importLoader = $this->loader->getImportLoader();

        $params['defaultImportTranslator'] = $importLoader->getDefaultTranslator($controller);
        $params['formatBoxClass']          = 'browser table';
        $params['importer']                = $importLoader->getImporter($controller);
        $params['importLoader']            = $importLoader;
        $params['tempDirectory']           = $importLoader->getTempDirectory();
        $params['importTranslators']       = $importLoader->getTranslators($controller);

        $this->addSnippets('Survey\\AnswerImportSnippet', $params);*/
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
    public function checkAction(): ?ResponseInterface
    {
        $session = $this->request->getAttribute(SessionInterface::class);

        $surveyId = $this->getSurveyId();
        $where    = 'gto_id_survey = ?';

        $batch = $this->tracker->recalculateTokens($session, 'surveyCheck' . $surveyId, $this->currentUserId, $where, $surveyId);
        $batch->setProgressTemplate($this->_('Remaining time: {remaining} - {msg}'));        

        $title = sprintf($this->_('Checking for the %s survey for answers .'),
                $this->resultFetcher->fetchOne("SELECT gsu_survey_name FROM gems__surveys WHERE gsu_id_survey = ?", [$surveyId]));


        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_('This task checks all tokens using this survey for answers.')
        ]);

        return $batchRunner->getResponse($this->request);
    }

    /**
     * Check the tokens for all surveys
     */
    public function checkAllAction(): ?ResponseInterface
    {
        $session = $this->request->getAttribute(SessionInterface::class);

        $batch = $this->tracker->recalculateTokens($session,'surveyCheckAll', $this->currentUserId);
        $batch->setProgressTemplate($this->_('Remaining time: {remaining} - {msg}'));        

        $title = $this->_('Checking for all surveys for answers .');

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle($title);
        $batchRunner->setJobInfo([
            $this->_('This task checks all tokens for all surveys for answers.')
        ]);

        return $batchRunner->getResponse($this->request);
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
     * @return ModelAbstract
     */
    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        /**
         * @var $model ModelAbstract
         */
        $model = $this->overLoader->create('Model\\SurveyMaintenanceModel');

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
    public function getBrowseColumns(): array|bool
    {
        $br = Html::create('br');

        $output[10] = ['gsu_survey_name', $br, 'gsu_survey_description', $br, 'gsu_survey_languages'];
        $output[20] = ['gsu_surveyor_active', Html::raw($this->_(' [')), 'gso_source_name',
            Html::raw($this->_(']')), $br, 'gsu_status_show', $br, 'gsu_survey_warnings'];

        $mailCodes = $this->surveyRepository->getSurveyMailCodes();
        if (count($mailCodes) > 1) {
            $output[30] = ['gsu_active', Html::raw(' '), 'track_count', $br, 'gsu_mail_code', Html::raw(', '), 'gsu_insertable', $br, 'gsu_id_primary_group'];
        } else {
            $output[30] = ['gsu_active', Html::raw(' '), 'track_count', $br, 'gsu_insertable', $br, 'gsu_id_primary_group'];
        }
        $output[40] = ['gsu_surveyor_id', $br, 'gsu_code', $br, 'gsu_export_code'];

        return $output;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Surveys');
    }

     /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useRequest = true): array
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
            $lang = $filter['survey_languages'];
            if (in_array($lang, $this->locale->getAvailableLanguages())) {
                $filter[] = "(gsu_survey_languages IS NOT NULL AND gsu_survey_languages LIKE '%$lang%')";
            }
            
            unset($filter['survey_languages']);
        }

        if (array_key_exists('events', $filter)) {
            
            switch ($filter['events']) {
                case '!Gems_Event_Survey':
                    $filter[] = "(gsu_beforeanswering_event IS NOT NULL OR gsu_completed_event IS NOT NULL OR gsu_display_event IS NOT NULL)";
                    break;
                case '!Gems\Event\SurveyBeforeAnsweringEventInterface':
                    $filter[] = "gsu_beforeanswering_event IS NOT NULL";
                    break;
                case '!Gems\Event\SurveyCompletedEventInterface':
                    $filter[] = "gsu_completed_event IS NOT NULL";
                    break;
                case '!Gems\Event\SurveyDisplayEventInterface':
                    $filter[] = "gsu_display_event IS NOT NULL";
                    break;
                default:
                    $class = $filter['events'];
                    if (class_exists($class, true)) {
                        if (is_subclass_of($class, '\\Gems\\Event\\SurveyBeforeAnsweringEventInterface', true)) {
                            $filter['gsu_beforeanswering_event'] = $class;
                        } elseif (is_subclass_of($class, '\\Gems\\Event\\SurveyCompletedEventInterface', true)) {
                            $filter['gsu_completed_event'] = $class;
                        } elseif (is_subclass_of($class, '\\Gems\\Event\\SurveyDisplayEventInterface', true)) {
                            $filter['gsu_display_event'] = $class;
                        }
                    }
                    break;
            }
            unset($filter['events']);
        }
        
        // \MUtil\EchoOut\EchoOut::track($filter);
        return $filter;
    }

    /**
     * Return the survey id
     *
     * @return int
     */
    public function getSurveyId(): int
    {
        return (int)$this->_getIdParam();
    }

   /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('survey', 'surveys', $count);
    }

    /**
     * Open pdf linked to survey
     */
    public function pdfAction(): void
    {
        // Output the PDF
        $this->pdfEditor->echoPdfBySurveyId($this->getSurveyId());

    }

}