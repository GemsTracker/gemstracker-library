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
abstract class Gems_Controller_ModelSnippetActionAbstract extends MUtil_Controller_ModelSnippetActionAbstract
{
    /**
     * Gems only parameters used for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_autofilterExtraParameters = array(
        'browse' => true,
        'containingId' => 'autofilter_target',
        'keyboard' => true,
        'onEmpty' => 'getOnEmpty',
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Generic_ModelTableSnippet';

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Generic_ModelFormSnippet';

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'Generic_ModelItemYesNoDeleteSnippet';

    /**
     *
     * @var GemsEscort
     */
    public $escort;

    /**
     *
     * @var Gems_Menu
     */
    public $menu;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = 'Generic_AutosearchForm';

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = 'Generic_CurrentButtonRow';

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = 'Generic_ModelItemTableSnippet';

    /**
     *
     * @var Gems_Util
     */
    public $util;

    /**
     * The automatically filtered result
     *
     * @param $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        // Already done when this value is false
        if ($resetMvc) {
            $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;
        }

        return parent::autofilterAction($resetMvc);
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
        // Set the request cache to use the search params from the index action
        $requestCache = $this->util->getRequestCache('index', true);
        $filter = $requestCache->getProgramParams();

        $model = $this->getModel();

        $model->applyParameters($filter);

        // $this->addExcelColumns($model);     // Hook to modify the model

        $this->view->result   = $this->getExcelData($model->load(), $model);
        $this->view->filename = $this->getRequest()->getControllerName() . '.xls';
        $this->view->setScriptPath(GEMS_LIBRARY_DIR . '/views/scripts' );

        $this->render('excel', null, true);
    }

    /**
     * Finds the first item with one of the actions specified as parameter and using the current controller
     *
     * @param string $action
     * @param string $action2
     * @return Gems_Menu_SubMenuItem
     */
    protected function firstAllowedMenuItem($action, $action2 = null)
    {
        $actions = MUtil_Ra::args(func_get_args());
        $controller = $this->_getParam('controller');

        foreach ($actions as $action) {
            $menuItem = $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));

            if ($menuItem) {
                return $menuItem;
            }
        }
    }

    /**
     * Returns an array with all columns from the model that have a label
     *
     * @param array                     $data
     * @param MUtil_Model_ModelAbstract $model
     * @return array
     */
    protected function getExcelData($data, MUtil_Model_ModelAbstract $model)
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
     * Return the current request ID, if any.
     *
     * Overrule this function if the last item in the page title
     * should be something other than te value of
     * MUtil_Model::REQUEST_ID.
     *
     * @return mixed
     */
    public function getInstanceId()
    {
        if ($id = $this->_getParam(MUtil_Model::REQUEST_ID)) {
            return $id;
        }
    }

    /**
     * Returns the on empty texts for the autofilter snippets
     *
     * @return string
     */
    public function getOnEmpty()
    {
        return $this->_('Nothing found...');
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
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;

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
            Gems_Html::init();
        }

        parent::initHtml($reset);
    }
}
