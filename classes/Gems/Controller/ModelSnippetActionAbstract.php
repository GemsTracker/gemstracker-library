<?php

/**
 *
 * @package    Gems
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Controller;

/**
 * Class contains \Gems specific adaptations to parent class.
 *
 * @package    Gems
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.2
 */
abstract class ModelSnippetActionAbstract extends \MUtil\Controller\ModelSnippetActionAbstract
{
    /**
     * \Gems only parameters used for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_autofilterExtraParameters = array(
        'browse'        => true,
        'containingId'  => 'autofilter_target',
        'keyboard'      => true,
        'onEmpty'       => 'getOnEmptyText',
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        'menuActionController'  => 'getControllerName',
        'requestQueryParams'    => 'getRequestQueryParams',
        'requestPost'           => 'getRequestParsedBody',
        'isPost'                => 'getRequestIsPost'
        );

    /**
     * \Gems only parameters used for the create action. Can be overruled
     * by setting $this->createParameters or $this->createEditParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_createExtraParameters = array(
        'formTitle'     => 'getCreateTitle',
        'topicCallable' => 'getTopicCallable',
        );

    /**
     * \Gems only parameters used for the deactivate action. Can be overruled
     * by setting $this->deactivateParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_deactivateExtraParameters = array(
        'confirmQuestion' => 'getDeactivateQuestion',
        'displayTitle'    => 'getDeactivateTitle',
        'formTitle'       => 'getDeactivateTitle',
        'topicCallable'   => 'getTopicCallable',
        );

    /**
     * \Gems only parameters used for the delete action. Can be overruled
     * by setting $this->deleteParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_deleteExtraParameters = array(
        'deleteQuestion' => 'getDeleteQuestion',
        'displayTitle'   => 'getDeleteTitle',
        'formTitle'      => 'getDeleteTitle',
        'topicCallable'  => 'getTopicCallable',
        );

    /**
     * \Gems only parameters used for the edit action. Can be overruled
     * by setting $this->editParameters or $this->createEditParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_editExtraParameters = array(
        'formTitle'     => 'getEditTitle',
        'topicCallable' => 'getTopicCallable',
        );

    /**
     * \Gems only parameters used for the export action. Can be overruled
     * by setting $this->editParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_exportExtraParameters = [
        'exportClasses' => 'getExportClasses',
        ];

    /**
     * \Gems only parameters used for the import action. Can be overruled
     * by setting $this->inmportParameters
     *
     * @var array Mixed key => value array for snippet initializPdfation
     */
    private $_importExtraParameters = array(
        'formatBoxClass'   => 'browser table',
        'importer'         => 'getImporter',
        'tempDirectory'    => 'getImportTempDirectory',
        'topicCallable'    => 'getTopic',
        );

    /**
     * \Gems only parameters used for the deactivate action. Can be overruled
     * by setting $this->deactivateParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_reactivateExtraParameters = array(
        'confirmQuestion' => 'getReactivateQuestion',
        'displayTitle'    => 'getReactivateTitle',
        'formTitle'       => 'getReactivateTitle',
        'topicCallable'   => 'getTopicCallable',
        );

    /**
     *
     * @var \Gems\AccessLog
     */
    public $accesslog;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'ModelTableSnippetGeneric';

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'ModelFormSnippetGeneric';

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'ModelItemYesNoDeleteSnippetGeneric';

    /**
     *
     * @var \Gems\Escort
     */
    public $escort;

    /**
     * The snippets used for the export action
     *
     * @var mixed String or array of snippets name
     */
    protected $exportFormSnippets = 'Export\\ExportFormSnippet';

    /**
     * The parameters used for the export actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $exportParameters = [];

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     *
     * @var \Gems\Menu
     */
    public $menu;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'AutosearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = 'Generic\\CurrentSiblingsButtonRowSnippet';

    /**
     *
     * @var \Zend_Controller_Action_Helper_FlashMessenger
     */
    public $messenger;

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array('Generic\\ContentTitleSnippet', 'ModelItemTableSnippetGeneric');

    /**
     * Array of the actions that use a summarized version of the model.
     *
     * This determines the value of $detailed in createAction(). As it is usually
     * less of a problem to use a $detailed model with an action that should use
     * a summarized model and I guess there will usually be more detailed actions
     * than summarized ones it seems less work to specify these.
     *
     * @var array $summarizedActions Array of the actions that use a
     * summarized version of the model.
     */
    public $summarizedActions = array('index', 'autofilter', 'export');

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * The automatically filtered result
     *
     * @param $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        $htmlOrig = $this->html;
        $div      = $this->html->div(array('id' => 'autofilter_target', 'class' => 'table-container'));

        // Already done when this value is false
        if ($resetMvc) {
            $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;
        }

        $this->html = $div;
        parent::autofilterAction($resetMvc);
        $this->html = $htmlOrig;
    }

    /**
     * Action for showing a create new item page with extra title
     */
    public function createAction()
    {
        $this->createEditParameters = $this->createEditParameters + $this->_createExtraParameters;

        parent::createAction();
    }

    /**
     * Action for showing a deactivate item page with extra titles
     */
    public function deactivateAction()
    {
        $this->deactivateParameters = $this->deactivateParameters + $this->_deactivateExtraParameters;

        parent::deactivateAction();
    }

    /**
     * Action for showing a delete item page with extra titles
     */
    public function deleteAction()
    {
        $this->deleteParameters = $this->deleteParameters + $this->_deleteExtraParameters;

        parent::deleteAction();
    }

    /**
     * Action for showing a edit item page with extra title
     */
    public function editAction()
    {
        $this->createEditParameters = $this->createEditParameters + $this->_editExtraParameters;

        parent::editAction();
    }

    /**
     * Export model data
     */
    public function exportAction()
    {
        $step = $this->request->getParam('step');
        $post = $this->request->getPost();

        $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;

        $model = $this->getExportModel();

        if (isset($this->autofilterParameters['sortParamAsc'])) {
            $model->setSortParamAsc($this->autofilterParameters['sortParamAsc']);
        }
        if (isset($this->autofilterParameters['sortParamDesc'])) {
            $model->setSortParamDesc($this->autofilterParameters['sortParamDesc']);
        }

        $model->applyParameters($this->getSearchFilter(false), true);

        if (!empty($post)) {
            $this->accesslog->logChange($this->request, null, $post + $model->getFilter());
        }

        // Add any defaults.
        if (isset($this->autofilterParameters['extraFilter'])) {
            $model->addFilter($this->autofilterParameters['extraFilter']);
        }
        if (isset($this->autofilterParameters['extraSort'])) {
            $model->addSort($this->autofilterParameters['extraSort']);
        }

        if ((!$step) || ($post && $step == 'form')) {
            $params = $this->_processParameters($this->exportParameters + $this->_exportExtraParameters);
            $this->addSnippet($this->exportFormSnippets, $params);
            $batch = $this->loader->getTaskRunnerBatch('export_data');
            $batch->reset();
        } elseif ($step == 'batch') {
            $batch = $this->loader->getTaskRunnerBatch('export_data');


            $batch->setVariable('model', $model);
            if (!$batch->count()) {
                $batch->minimalStepDurationMs = 2000;
                $batch->finishUrl = $this->view->url(array('step' => 'download'));

                $batch->setSessionVariable('files', array());

                if (! isset($post['type'])) {
                    // Export type is needed, use most basic type
                    $post['type'] = 'CsvExport';
                }
                $batch->addTask('Export\\ExportCommand', $post['type'], 'addExport', $post);
                $batch->addTask('addTask', 'Export\\ExportCommand', $post['type'], 'finalizeFiles', $post);

                $export = $this->loader->getExport()->getExport($post['type']);
                if ($snippet = $export->getHelpSnippet()) {
                    $this->addSnippet($snippet);
                }

                $batch->autoStart = true;
            }

            if (\MUtil\Console::isConsole()) {
                // This is for unit tests, if we want to be able to really export from
                // cli we need to place the exported file somewhere.
                // This is out of scope for now.
                $batch->runContinuous();
            } elseif ($batch->run($this->request)) {
                exit;
            } else {
                $controller = $this;

                if ($batch->isFinished()) {
                    /*\MUtil\EchoOut\EchoOut::track('finished');
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
            $batch = $this->loader->getTaskRunnerBatch('export_data');
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
        }
    }


    /**
     * Finds the first item with one of the actions specified as parameter and using the current controller
     *
     * @param string $action
     * @param string $action2
     * @return \Gems\Menu\SubMenuItem
     */
    protected function firstAllowedMenuItem($action, $action2 = null)
    {
        $actions = \MUtil\Ra::args(func_get_args());
        $controller = $this->_getParam('controller');

        foreach ($actions as $action) {
            $menuItem = $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));

            if ($menuItem) {
                return $menuItem;
            }
        }
    }

    public function getControllerName()
    {
        return $this->requestHelper->getControllerName();
    }

    /**
     * Helper function to get the title for the create action.
     *
     * @return $string
     */
    public function getCreateTitle()
    {
        return sprintf($this->_('New %s...'), $this->getTopic(1));
    }

    /**
     * Name of the default import translator
     *
     * @return string
     */
    public function getDefaultImportTranslator()
    {
        return $this->loader->getImportLoader()->getDefaultTranslator($this->getRequest()->getControllerName());
    }

    /**
     * Helper function to get the question for the deactivate action.
     *
     * @return $string
     */
    public function getDeactivateQuestion()
    {
        return sprintf($this->_('Do you want to deactivate this %s?'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the deactivate action.
     *
     * @return $string
     */
    public function getDeactivateTitle()
    {
        return sprintf($this->_('Deactivate %s'), $this->getTopic(1));
    }

    /**
     * Helper function to get the question for the delete action.
     *
     * @return $string
     */
    public function getDeleteQuestion()
    {
        return sprintf($this->_('Do you want to delete this %s?'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the delete action.
     *
     * @return $string
     */
    public function getDeleteTitle()
    {
        return sprintf($this->_('Delete %s'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return $string
     */
    public function getEditTitle()
    {
        return sprintf($this->_('Edit %s'), $this->getTopic(1));
    }

    public function getExportClasses()
    {
        return $this->loader->getExport()->getExportClasses();
    }

    /**
     * Get the model for export and have the option to change it before using for export
     * @return
     */
    protected function getExportModel()
    {
        $model = $this->getModel();
        $noExportColumns = $model->getColNames('noExport');
        foreach($noExportColumns as $colName) {
            $model->remove($colName, 'label');
        }
        return $model;
    }

    /**
     * Get the return url
     *
     * @return array
     */
    protected function getExportReturnLink()
    {
       return \MUtil\Html\UrlArrayAttribute::rerouteUrl($this->getRequest(), array('action'=>'index', 'step' => false));
    }

    /**
     * Get an Importer object for this actions
     *
     * @return \MUtil\Model\Importer
     */
    public function getImporter()
    {
        return $this->loader->getImportLoader()->getImporter(
                $this->getRequest()->getControllerName(),
                $this->getModel()
                );
    }

    /**
     * The directory to use for temporary storage
     *
     * @return string
     */
    public function getImportTempDirectory()
    {
        return $this->loader->getImportLoader()->getTempDirectory();
    }

    /**
     * Get the possible translators for the import snippet.
     *
     * @return \MUtil\Model\ModelTranslatorInterface[]
     */
    public function getImportTranslators()
    {
        return $this->loader->getImportLoader()->getTranslators($this->getRequest()->getControllerName());
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return ucfirst((string) $this->getTopic(100));
    }

    /**
     * Return the current request ID, if any.
     *
     * Overrule this function if the last item in the page title
     * should be something other than te value of
     * \MUtil\Model::REQUEST_ID.
     *
     * @return mixed
     */
    public function getInstanceId()
    {
        return $this->request->getAttribute(\MUtil\Model::REQUEST_ID);
    }

    /**
     * Returns the on empty texts for the autofilter snippets
     *
     * @return string
     */
    public function getOnEmptyText()
    {
        return sprintf($this->_('No %s found...'), $this->getTopic(0));
    }

    /**
     * Helper function to get the question for the reactivate action.
     *
     * @return $string
     */
    public function getReactivateQuestion()
    {
        return sprintf($this->_('Do you want to reactivate this %s?'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the reactivate action.
     *
     * @return $string
     */
    public function getReactivateTitle()
    {
        return sprintf($this->_('Reactivate %s'), $this->getTopic(1));
    }

    public function getRequestIsPost(): bool
    {
        return $this->requestHelper->isPost();
    }

    public function getRequestParsedBody(): array
    {
        return $this->request->getParsedBody();
    }

    public function getRequestQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * Helper function to get the title for the show action.
     *
     * @return $string
     */
    public function getShowTitle()
    {
        return sprintf($this->_('Showing %s'), $this->getTopic(1));
    }

    /**
     * Returns the current html/head/title for this page.
     *
     * If the title is an array the seperator concatenates the parts.
     *
     * @param string $separator
     * @return string
     */
    public function getTitle(string $separator = null): string
    {
        if ($titleSet = parent::getTitle($separator)) {
            return $titleSet;
        }

        $title = array();
        foreach($this->menu->getActivePath($this->getRequest()) as $menuItem) {
            $title[] = $menuItem->get('label');
        }
        if ($id = $this->getInstanceId()) {
            $title[] = $id;
        }

        return implode($separator, $title);
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('item', 'items', $count);
    }

    /**
     * Get a callable for the gettopic function
     * @return callable
     */
    public function getTopicCallable()
    {
        return array($this, 'getTopic');
    }

    /**
     * Generic model based import action
     */
    public function importAction()
    {
        $this->importParameters = $this->importParameters + $this->_importExtraParameters;

        parent::importAction();
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;
        if (! isset($this->indexParameters['contentTitle'])) {
            $this->indexParameters['contentTitle'] = $this->getIndexTitle();
        }

        return parent::indexAction();
    }

    /**
     * Intializes the html component.
     *
     * @param boolean $reset Throws away any existing html output when true
     * @return void
     */
    public function initHtml(bool $reset = false): void
    {
        if (! $this->html) {
            \Gems\Html::init();
        }

        parent::initHtml($reset);
    }

    /**
     * Stub for overruling default snippet loader initiation.
     */
    protected function loadSnippetLoader(): void
    {
        // Create the snippet with this controller as the parameter source
        $this->snippetLoader = $this->loader->getSnippetLoader($this);
    }

    /**
     * Action for showing a reactivate item page with extra titles
     */
    public function reactivateAction()
    {
        $this->reactivateParameters = $this->reactivateParameters + $this->_reactivateExtraParameters;

        parent::reactivateAction();
    }

    /**
     * Set the session based message store.
     *
     * @param \Zend_Controller_Action_Helper_FlashMessenger $messenger
     * @return \MUtil\Controller\Action
     */
    public function setMessenger(\Zend_Controller_Action_Helper_FlashMessenger $messenger): self
    {
        $this->messenger = $messenger;

        return $this;
    }

    /**
     * Action for showing an item page with title
     */
    public function showAction()
    {
        if (! isset($this->showParameters['contentTitle'])) {
            $this->showParameters['contentTitle'] = $this->getShowTitle();
        }

        parent::showAction();
    }
}
