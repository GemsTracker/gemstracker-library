<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class contains Gems specific adaptations to parent class.
 *
 * @package    Gems
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.2
 */
abstract class Gems_Controller_ModelSnippetActionAbstract extends \MUtil_Controller_ModelSnippetActionAbstract
{
    /**
     * Gems only parameters used for the autofilter action. Can be overruled
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
        );

    /**
     * Gems only parameters used for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_createExtraParameters = array(
        'formTitle'     => 'getCreateTitle',
        'topicCallable' => 'getTopicCallable',
        );

    /**
     * Gems only parameters used for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_deleteExtraParameters = array(
        'displayTitle'  => 'getDeleteTitle',
        'formTitle'     => 'getDeleteTitle',
        'topicCallable' => 'getTopicCallable',
        );

    /**
     * Gems only parameters used for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_editExtraParameters = array(
        'formTitle'     => 'getEditTitle',
        'topicCallable' => 'getTopicCallable',
        );

    /**
     * Gems only parameters used for the import action. Can be overruled
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
     * @var GemsEscort
     */
    public $escort;

    /**
     * Should Excel output contain formatted data (date fields, select lists)
     *
     * @var boolean
     */
    public $formatExcelData = true;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic_ContentTitleSnippet', 'AutosearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = 'Generic_CurrentButtonRowSnippet';

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array('Generic_ContentTitleSnippet', 'ModelItemTableSnippetGeneric');

    /**
     *
     * @var \Gems_Util
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
     * Outputs the model to excel, applying all filters and searches needed
     *
     * When you want to change the output, there are two places to check:
     *
     * 1. $this->addExcelColumns($model), where the model can be changed to have labels for columns you
     * need exported
     *
     * 2. $this->getExcelData($data, $model) where the supplied data and model are merged to get output
     * (by default all fields from the model that have a label)
     */
    public function excelAction()
    {
        // Make sure we have all the parameters used by the model
        $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;

        $model = $this->getModel();

        // Set any defaults.
        if (isset($this->autofilterParameters['sortParamAsc'])) {
            $model->setSortParamAsc($this->autofilterParameters['sortParamAsc']);
        }
        if (isset($this->autofilterParameters['sortParamDesc'])) {
            $model->setSortParamDesc($this->autofilterParameters['sortParamDesc']);
        }

        $model->applyParameters($this->getSearchFilter(), true);

        // Add any defaults.
        if (isset($this->autofilterParameters['extraFilter'])) {
            $model->addFilter($this->autofilterParameters['extraFilter']);
        }
        if (isset($this->autofilterParameters['extraSort'])) {
            $model->addSort($this->autofilterParameters['extraSort']);
        }

        // $this->addExcelColumns($model);     // Hook to modify the model

        // Use $this->formatExcelData to switch between formatted and unformatted data
        $excelData = new \Gems_FormattedData($this->getExcelData($model->load(), $model), $model, $this->formatExcelData);

        $this->view->result   = $excelData;

        $this->view->filename = $this->getRequest()->getControllerName() . '.xls';
        $this->view->setScriptPath(GEMS_LIBRARY_DIR . '/views/scripts' );

        $this->render('excel', null, true);
    }

    /**
     * Finds the first item with one of the actions specified as parameter and using the current controller
     *
     * @param string $action
     * @param string $action2
     * @return \Gems_Menu_SubMenuItem
     */
    protected function firstAllowedMenuItem($action, $action2 = null)
    {
        $actions = \MUtil_Ra::args(func_get_args());
        $controller = $this->_getParam('controller');

        foreach ($actions as $action) {
            $menuItem = $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));

            if ($menuItem) {
                return $menuItem;
            }
        }
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

    /**
     * Returns an array with all columns from the model that have a label
     *
     * @param array                     $data
     * @param \MUtil_Model_ModelAbstract $model
     * @return array
     */
    protected function getExcelData($data, \MUtil_Model_ModelAbstract $model)
    {
        $headings = array();
        $emptyMsg = $this->_('No data found.');
        foreach ($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $headings[$name] = (string) $label;
            }
        }
        $results = array();
        $results[] = $headings;
        if ($headings) {
            if ($data) {
                foreach ($data as $row) {
                    foreach ($headings as $key => $value) {
                        $result[$key] = isset($row[$key]) ? $row[$key] : null;
                    }
                    $results[] = $result;
                }
                return $results;
            } else {
                foreach ($headings as $key => $value) {
                    $result[$key] = $emptyMsg;
                }
                $results[] = $result;
                return $results;
            }
        } else {
            return array($emptyMsg);
        }
    }

    /**
     * Get an Importer object for this actions
     *
     * @return \MUtil_Model_Importer
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
     * @return array of \MUtil_Model_ModelTranslatorInterface objects
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
        return ucfirst($this->getTopic(100));
    }

    /**
     * Return the current request ID, if any.
     *
     * Overrule this function if the last item in the page title
     * should be something other than te value of
     * \MUtil_Model::REQUEST_ID.
     *
     * @return mixed
     */
    public function getInstanceId()
    {
        if ($id = $this->_getParam(\MUtil_Model::REQUEST_ID)) {
            return $id;
        }
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
    public function getTitle($separator = null)
    {
        if ($title_set = parent::getTitle($separator)) {
            return $title_set;
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
    public function initHtml($reset = false)
    {
        if (! $this->html) {
            \Gems_Html::init();
        }

        parent::initHtml($reset);
    }

    /**
     * Stub for overruling default snippet loader initiation.
     */
    protected function loadSnippetLoader()
    {
        // Create the snippet with this controller as the parameter source
        $this->snippetLoader = $this->loader->getSnippetLoader($this);
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
