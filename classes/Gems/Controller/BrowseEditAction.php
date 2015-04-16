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
 * BrowseEdit controller
 *
 * This controller handles a default model browse / edit / export to excel
 *
 * @package    Gems
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 * @deprecated Since version 1.7.1
 */
abstract class Gems_Controller_BrowseEditAction extends \Gems_Controller_ModelActionAbstract
{
    const RESET_PARAM   = 'reset';
    const SEARCH_BUTTON = 'AUTO_SEARCH_TEXT_BUTTON';

    public $autoFilter = true;

    public $menuCreateIncludeLevel = 0;

    public $menuEditIncludeLevel = 10;

    public $menuIndexIncludeLevel = 4;

    public $menuShowIncludeLevel = 2;

    /**
     * Field id for crsf protection field.
     *
     * @var string
     */
    protected $csrfId = 'no_csrfx';

    /**
     * The timeout for crsf, 300 is default
     *
     * @var int
     */
    protected $csrfTimeout = 300;

    public $filterStandard;

    /**
     * Should Excel output contain formatted data (date fields, select lists)
     *
     * @var boolean
     */
    public $formatExcelData = true;

    /**
     * The snippets used for the import action
     *
     * @var mixed String or array of snippets name
     */
    protected $importSnippets = 'ModelImportSnippet';

    /**
     * The page title at the top of each page
     * @var string Title
     */
    public $pageTitle;

    /**
     *
     * @var \Gems_Util_RequestCache
     */
    public $requestCache;

    public $sortKey;

    public $summarizedActions = array('index', 'autofilter');

    public $tableSnippets;

    /**
     * Use csrf token on form for protection against Cross Site Request Forgery
     *
     * @var boolean
     */
    public $useCsrf = true;

    public $useKeyboardSelector = true;

    public $useMultiRowForm = false;

    public $usePreviousFilter = true;

    public $useTabbedForms = false;

    protected function _applySearchParameters(\MUtil_Model_ModelAbstract $model, $useStored = false)
    {
        $data = $this->getCachedRequestData();

        // Make sure page and items parameters are not added to the search statement
        unset($data['page'], $data['items']);

        $data = $model->applyParameters($data);

        if ($filter = $this->getDataFilter($data)) {
            $model->addFilter($filter);
            // \MUtil_Echo::track($filter, $data, $model->getFilter());
        }

        if ($this->sortKey) {
            $model->addSort($this->sortKey);
        }
    }

    /**
     * Creates a \Zend_Form_Element_Select
     *
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return \Zend_Form_Element_Select
     */
    protected function _createSelectElement($name, $options, $empty = null)
    {
        if ($options instanceof \MUtil_Model_ModelAbstract) {
            $options = $options->get($name, 'multiOptions');
        } elseif (is_string($options)) {
            $options = $this->db->fetchPairs($options);
            natsort($options);
        }
        if ($options || null !== $empty)
        {
            if (null !== $empty) {
                $options = array('' => $empty) + $options;
            }
            $element = $this->form->createElement('select', $name, array('multiOptions' => $options));

            return $element;
        }
    }

    protected function _createTable()
    {
        $model   = $this->getModel();
        $request = $this->getRequest();
        $search  = $this->getCachedRequestData(false);
        $params  = array('baseUrl' => $search);
        // \MUtil_Echo::track($search);

        // Load the filters
        $this->_applySearchParameters($model);

        //* Actually we should apply the marker after the columns used have been determined. Takes more work though.
        $textKey = $model->getTextFilter();
        if (isset($search[$textKey])) {
            $searchText = $search[$textKey];
            // \MUtil_Echo::r('[' . $searchText . ']');
            $marker = new \MUtil_Html_Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
            foreach ($model->getItemNames() as $name) {
                if ($model->get($name, 'label')) {
                    $model->set($name, 'markCallback', array($marker, 'mark'));
                }
            }
        } // */

        if ($this->tableSnippets) {
            $snippets = $this->getSnippets($this->tableSnippets, $params);
            $sequence = new \MUtil_Html_Sequence();
            foreach ($snippets as $snippet) {
                if ($snippet->hasHtmlOutput()) {
                    $sequence[] = $snippet;
                }
            }
        }

        $model->trackUsage();
        $table     = $this->getBrowseTable($search);
        $paginator = $model->loadPaginator();
        $table->setRepeater($paginator);
        $table->tfrow()->pagePanel($paginator, $request, $this->translate, $params);

        if (isset($sequence)) {
            $sequence[] = $table;
            return $sequence;
        } else {
            return $table;
        }
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('show')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        parent::addBrowseTableColumns($bridge, $model);

        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('edit')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }
    }

    /**
     * This is where you can modify the model for excel export
     *
     * Only columns that have a label will be exported.
     *
     * example:
     * <code>
     * $model->set('columnname', 'label', $this->_('Excel label'));
     * </code>
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addExcelColumns(\MUtil_Model_ModelAbstract $model) {}

    /**
     * Hook to alter the formdata just before the form is populated
     *
     * @param array $data
     * @param bool  $isNew
     */
    public function afterFormLoad(array &$data, $isNew)
    {
    }

    /**
     * Hook to perform action after a record (with changes) was saved
     *
     * As the data was already saved, it can NOT be changed anymore
     *
     * @param array $data
     * @param boolean $isNew
     * @return boolean  True when you want to display the default 'saved' messages
     */
    public function afterSave(array $data, $isNew)
    {
        return true;
    }

    /**
     * Get the afterSaveRoute and execute it
     *
     * @param mixed $data data array or Zend Request
     * @return boolean
     */
    public function afterSaveRoute($data)
    {
        $this->accesslog->logChange($this->request, null, $data);

        //Get default routing
        $url = $this->getAfterSaveRoute($data);

        //If we have a route, reroute
        if ($url !== null && $url !== false) {
            $this->_helper->redirector->gotoRoute($url, null, true);
            return true;
        }

        // Do not reroute
        return false;
    }

    /**
     * Set the action key in request
     *
     * Use this when an action is a Ajax action for retrieving
     * information for use within the screen of another action
     *
     * @param string $alias
     */
    protected function aliasAction($alias)
    {
        $request = $this->getRequest();
        $request->setActionName($alias);
        $request->setParam($request->getActionKey(), $alias);
    }

    public function autofilterAction()
    {
        // Make sure all links are generated as if the current request was index.
        $this->aliasAction('index');

        // \MUtil_Model::$verbose = true;

        // We do not need to return the layout, just the above table
        $this->disableLayout();

        $this->html[] = $this->_createTable();
        $this->html->raw(\MUtil_Echo::out());
    }

    /**
     * Perform some actions on the form, right before it is displayed but already populated
     *
     * Here we add the table display to the form.
     *
     * @param \Zend_Form $form
     * @param bool      $isNew
     * @return \Zend_Form
     */
    public function beforeFormDisplay ($form, $isNew)
    {
        if ($this->useTabbedForms || $form instanceof \Gems_Form_TableForm) {
            //If needed, add a row of link buttons to the bottom of the form
            if ($links = $this->createMenuLinks($isNew ? $this->menuCreateIncludeLevel : $this->menuEditIncludeLevel)) {
                $linkContainer = \MUtil_Html::create()->div(array('class' => 'element-container-labelless'));
                $linkContainer[] = $links;

                $element = $this->_form->createElement('html', 'formLinks');
                $element->setValue($linkContainer);
                $element->setOrder(999);
                if ($form instanceof \Gems_TabForm)  {
                    $form->resetContext();
                }
                $form->addElement($element);
                $form->addDisplayGroup(array('formLinks'), 'form_buttons');
            }
        } else {
            if (\MUtil_Bootstrap::enabled() !== true) {
                $table = new \MUtil_Html_TableElement(array('class' => 'formTable'));
                $table->setAsFormLayout($form, true, true);
                $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.

                if ($links = $this->createMenuLinks($isNew ? $this->menuCreateIncludeLevel : $this->menuEditIncludeLevel)) {
                    $table->tf(); // Add empty cell, no label
                    $linksCell = $table->tf($links);
                }
            } elseif ($links = $this->createMenuLinks($isNew ? $this->menuCreateIncludeLevel : $this->menuEditIncludeLevel)) {
                $element = $form->createElement('html', 'menuLinks');
                $element->setValue($links);
                $element->setOrder(999);
                $form->addElement($element);
            }
        }

        return $form;
    }

    /**
     * Hook to alter formdata before saving
     *
     * @param array $data The data that will be saved.
     * @param boolean $isNew
     * $param \Zend_Form $form
     * @return boolean Returns true if flow should continue
     */
    public function beforeSave(array &$data, $isNew, \Zend_Form $form = null)
    {
        return true;
    }

    /**
     * Creates a form for a new record
     *
     * Uses $this->getModel()
     *      $this->addFormElements()
     */
    public function createAction()
    {
        if ($form = $this->processForm()) {
            $this->setPageTitle(sprintf($this->_('New %s...'), $this->getTopic()));
            $this->html[] = $form;
        }
    }

    /**
     * Retrieve a form object and add extra decorators
     *
     * @param array $options
     * @return \Gems_Form
     */
    public function createForm($options = null) {
        if ($this->useTabbedForms) {
            $form = new \Gems_TabForm($options);
        } else {
            $form = parent::createForm($options);
            //$form = new \Gems_Form_TableForm($options);
        }

        return $form;
    }

    // Still abstract
    // abstract protected function createModel($detailed, $action);

    /**
     * Creates a form to delete a record
     *
     * Uses $this->getModel()
     *      $this->addFormElements()
     */
    public function deleteAction()
    {
        if ($this->isConfirmedItem($this->_('Delete %s'))) {
            $model   = $this->getModel();
            $deleted = $model->delete();

            $this->addMessage(sprintf($this->_('%2$u %1$s deleted'), $this->getTopic($deleted), $deleted), 'success');
            $this->_reroute(array('action' => 'index'), true);
        }
    }

    /**
     * Creates a form to edit
     *
     * Uses $this->getModel()
     *      $this->addFormElements()
     */
    public function editAction()
    {
        if ($form = $this->processForm()) {
            if ($this->useTabbedForms && method_exists($this, 'getSubject')) {
                $data = $this->getModel()->loadFirst();
                $subject = $this->getSubject($data);
                $this->setPageTitle(sprintf($this->_('Edit %s %s'), $this->getTopic(1), $subject));
            } else {
                $this->setPageTitle(sprintf($this->_('Edit %s'), $this->getTopic(1)));
            }
            $this->html[] = $form;
        }
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
        ini_set('max_execution_time', 90);  // Quickfix as long as it is not in a batchtask

        // Set the request cache to use the search params from the index action
        $this->getCachedRequestData(true, 'index');

        $model = $this->getModel();

        $this->_applySearchParameters($model, true);

        $this->addExcelColumns($model);     // Hook to modify the model

        // Use $this->formatExcelData to switch between formatted and unformatted data
        $excelData = new \Gems_FormattedData($this->getExcelData($model->load(), $model), $model, $this->formatExcelData);

        $this->view->result   = $excelData;
        $this->view->filename = $this->getRequest()->getControllerName() . '.xls';
        $this->view->setScriptPath(GEMS_LIBRARY_DIR . '/views/scripts' );

        $this->render('excel', null, true);
     }

    /**
     * Return an array with route options depending on de $data given.
     *
     * @param mixed $data array or \Zend_Controller_Request_Abstract
     * @return mixed array with route options or false when no redirect is found
     */
    public function getAfterSaveRoute($data) {
        if ($currentItem = $this->menu->getCurrent()) {
            $controller = $this->_getParam('controller');
            $url        = null;

            if ($data instanceof \Zend_Controller_Request_Abstract) {
                $refData = $data;
            } elseif (is_array($data)) {
                $refData = $this->getModel()->getKeyRef($data) + $data;
            } else {
                throw new \Gems_Exception_Coding('The variable $data must be an array or a ' . 'Zend_Controller_Request_Abstract object.');
            }

            if ($parentItem = $currentItem->getParent()) {
                if ($parentItem instanceof \Gems_Menu_SubMenuItem) {
                    $controller = $parentItem->get('controller');
                }
            }

            // Look for allowed show
            if ($menuItem = $this->menu->find(array('controller' => $controller, 'action' => 'show', 'allowed' => true))) {
                $url = $menuItem->toRouteUrl($refData);
            }

            if (null === $url) {
                // Look for allowed index
                if ($menuItem = $this->menu->find(array('controller' => $controller, 'action' => 'index', 'allowed' => true))) {
                    $url = $menuItem->toRouteUrl($refData);
                }
            }

            if ((null === $url) && ($parentItem instanceof \Gems_Menu_SubMenuItem)) {
                // Still nothing? Try parent item.
                $url = $parentItem->toRouteUrl($refData);
            }

            if (null !== $url) {
                return $url;
            }
        }
        return false;
    }

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(\MUtil_Model_ModelAbstract $model, array $data)
    {
        if ($model->hasTextSearchFilter()) {
            // Search text
            $element = $this->form->createElement('text', \MUtil_Model::TEXT_FILTER, array('label' => $this->_('Free search text'), 'size' => 20, 'maxlength' => 30));

            return array($element);
        }
    }

    /**
     * Creates an autosearch form for indexAction.
     *
     * @param string $targetId
     * @return \Gems_Form|null
     */
    protected function getAutoSearchForm($targetId)
    {
        if ($this->autoFilter) {
            $model = $this->getModel();
            $data  = $this->getCachedRequestData();

            $this->form = $form = $this->createForm(array('name' => 'autosubmit', 'class' => 'form-inline', 'role' => 'form')); // Assign a name so autosubmit will only work on this form (when there are others)
            $elements = $this->getAutoSearchElements($model, $data);

            if ($elements) {
                //$form = $this->createForm(array('name' => 'autosubmit')); // Assign a name so autosubmit will only work on this form (when there are others)
                $form->setHtml('div');

                $div = $form->getHtml();
                $div->class = 'search';

                $span = $div->div(array('class' => 'panel panel-default'))->div(array('class' => 'inputgroup panel-body'));

                $elements[] = $this->getAutoSearchSubmit($model, $form);

                if ($reset = $this->getAutoSearchReset()) {
                    $elements[] = $reset;
                }

                foreach ($elements as $element) {
                    if ($element instanceof \Zend_Form_Element) {
                        if ($element->getLabel()) {
                            $span->label($element);
                        }
                        $span->input($element);
                        // TODO: Elementen automatisch toevoegen in \MUtil_Form
                        $form->addElement($element);
                    } elseif (null === $element) {
                        $span = $div->div(array('class' => 'panel panel-default'))->div(array('class' => 'inputgroup panel-body'));
                    } else {
                        $span[] = $element;
                    }
                }

                if ($this->_request->isPost()) {
                    if (! $form->isValid($data)) {
                        $this->addMessage($form->getErrorMessages(), 'danger');
                        $this->addMessage($form->getMessages());
                    }
                } else {
                    $form->populate($data);
                }

                $href = $this->getAutoSearchHref();
                $form->setAutoSubmit($href, $targetId);
                //$div[] = new \Gems_JQuery_AutoSubmitForm($href, $targetId, $form);

                return $form;
            }
        }
    }

    protected function getAutoSearchHref()
    {
        return \MUtil_Html::attrib('href', array('action' => 'autofilter', 'RouteReset' => true));
    }

    /**
     * Creates a reset button for the search form
     *
     * @return \Zend_Form_Element_Submit
     */
    protected function getAutoSearchReset()
    {
        if ($menuItem = $this->menu->getCurrent()) {
            $link    = $menuItem->toActionLink($this->request, array('reset' => 1), $this->_('Reset search'));
            //$link->appendAttrib('class', 'btn-xs');
            $element = new \MUtil_Form_Element_Html('reset');
            $element->setValue($link);

            return $element;
        }
    }

    protected function getAutoSearchSubmit(\MUtil_Model_ModelAbstract $model, \MUtil_Form $form)
    {
        return $form->createElement('submit', self::SEARCH_BUTTON, array('label' => $this->_('Search'), 'class' => 'button small'));

        //return new \Zend_Form_Element_Submit(self::SEARCH_BUTTON, array('label' => $this->_('Search'), 'class' => 'button small'));
    }

    /**
     * Creates from the model a \MUtil_Html_TableElement that can display multiple items.
     *
     * Overruled to add css classes for Gems
     *
     * @param array $baseUrl
     * @return \MUtil_Html_TableElement
     */
    public function getBrowseTable(array $baseUrl = array(), $sort = null, $model = null)
    {
        $table = parent::getBrowseTable($baseUrl, $sort, $model);

        $table->class = 'browser table';
        $table->setOnEmpty(sprintf($this->_('No %s found'), $this->getTopic(0)));
        $table->getOnEmpty()->class = 'centerAlign';

        return $table;
    }

    /**
     *
     * @param boolean $includeDefaults Include the default values (yes for filtering, no for urls
     * @param string  $sourceAction    The action to get the cache from if not the current one.
     * @param boolean $readonly        Optional, tell the cache not to store any new values
     * @param boolean $filterEmpty     Optional, filter empty values from cache
     * @return array
     */
    public function getCachedRequestData($includeDefaults = true, $sourceAction = null, $readonly = false, $filterEmpty = true)
    {
        if (! $this->requestCache) {
            $this->requestCache = $this->util->getRequestCache($sourceAction, $readonly);
            $this->requestCache->setMenu($this->menu);
            $this->requestCache->setRequest($this->request);

            // Button text should not be stored.
            $this->requestCache->removeParams(self::SEARCH_BUTTON, 'action');
        }

        $data = $this->requestCache->getProgramParams();
        if ($includeDefaults) {
            $data = $data + $this->getDefaultSearchData();
        }
        if ($filterEmpty) {
            // Clean up empty values
            //
            // We do this here because empty values can be valid filters that overrule the default
            foreach ($data as $key => $value) {
                if ((is_array($value) && empty($value)) || (is_string($value) && 0 === strlen($value))) {
                    unset($data[$key]);
                }
            }
        }

        // Make sure to update the request
        $this->getRequest()->setParams($data);

        return $data;
    }

    /**
     * Additional data filter statements for the user input.
     *
     * User input that has the same name as a model field is automatically
     * used as a filter, but if the name is different processing is needed.
     * That processing should happen here.
     *
     * @param array $data The current user input
     * @return array New filter statements
     */
    protected function getDataFilter(array $data)
    {
        if ($this->filterStandard) {
            return (array) $this->filterStandard;
        }

        return array();
    }

    /**
     * Returns the default search values for this class instance.
     *
     * Used to specify the filter when no values have been entered by the user.
     *
     * @return array
     */
    public function getDefaultSearchData()
    {
        return array();
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
        $emptyMsg = sprintf($this->_('No %s found.'), $this->getTopic(0));
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
                    $results[] = array_intersect_key($row, $headings);
                }
                return $results;
            } else {
                $result = array();
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
     * Creates from the model a \Zend_Form using createForm and adds elements
     * using addFormElements().
     *
     * @param array $data The data that will later be loaded into the form, can be changed
     * @param optional boolean $new Form should be for a new element
     * @return \Zend_Form
     */
    public function getModelForm(array &$data, $new = false)
    {
        $model = $this->getModel();

        $baseform = $this->createForm();

        if ($this->useMultiRowForm) {
            $bridge    = $model->getBridgeFor('form', new \Gems_Form_SubForm());
            $newData   = $this->addFormElements($bridge, $model, $data, $new);
            $formtable = new \MUtil_Form_Element_Table($bridge->getForm(), $model->getName(), array('class' => $this->editTableClass));

            $baseform->setMethod('post')
                ->setDescription($this->getTopicTitle())
                ->addElement($formtable);

            $form = $baseform;
        } else {
            $bridge  = $model->getBridgeFor('form', $baseform);
            $newData = $this->addFormElements($bridge, $model, $data, $new);
            $form    = $bridge->getForm();
        }

        if ($newData && is_array($newData)) {
            $data = $newData + $data;
        }

        if ($form instanceof \Gems_TabForm)  {
            $form->resetContext();
        }
        return $form;
    }

    /**
     * Creates from the model a \MUtil_Html_TableElement for display of a single item.
     *
     * Overruled to add css classes for Gems
     *
     * @param integer $columns The number of columns to use for presentation
     * @param mixed $filter A valid filter for \MUtil_Model_ModelAbstract->load()
     * @param mixed $sort A valid sort for \MUtil_Model_ModelAbstract->load()
     * @return \MUtil_Html_TableElement
     */
    public function getShowTable($columns = 1, $filter = null, $sort = null)
    {
        $table = parent::getShowTable($columns, $filter, $sort);

        $table->class = 'displayer table';

        return $table;
    }

    /**
     * Generic model based import action
     */
    public function importAction()
    {
        $controller   = $this->getRequest()->getControllerName();
        $importLoader = $this->loader->getImportLoader();
        $model        = $this->getModel();

        $params = array();
        $params['defaultImportTranslator'] = $importLoader->getDefaultTranslator($controller);
        $params['formatBoxClass']          = 'browser table';
        $params['importer']                = $importLoader->getImporter($controller, $model);
        $params['model']                   = $model;
        $params['tempDirectory']           = $importLoader->getTempDirectory();
        $params['importTranslators']       = $importLoader->getTranslators($controller);

        $this->addSnippets($this->importSnippets, $params);
    }

    public function indexAction()
    {
        // \MUtil_Model::$verbose = true;
        $this->setPageTitle($this->getTopicTitle(), array('class' => 'title'));

        if (! $this->useMultiRowForm) {
            $id = 'autofilter_target';

            $this->html[] = $this->getAutoSearchForm($id);

            $this->html->div(array('id' => $id), $this->_createTable());
            if ($this->useKeyboardSelector) {
                $this->html[] = new \Gems_JQuery_TableRowKeySelector($id);
            }

            $this->html->buttonDiv($this->createMenuLinks($this->menuIndexIncludeLevel), array('class' => 'leftAlign', 'renderWithoutContent' => false));
        } else {
            if ($form = $this->processForm()) {
                $this->html[] = $form;
            }
        }
    }

    public function isConfirmedItem($title, $question = null, $info = null)
    {
        if ($this->_getParam('confirmed')) {
            return true;
        }

        if (null === $question) {
            $question = $this->_('Are you sure?');
        }

        $this->setPageTitle(sprintf($title, $this->getTopic()));

        if ($info) {
            $this->html->pInfo($info);
        }

        $model    = $this->getModel();
        $repeater = $model->applyRequest($this->getRequest())->loadRepeatable();
        $table    = $this->getShowTable();
        $table->caption($question);
        $table->setRepeater($repeater);

        $footer = $table->tfrow($question, ' ', array('class' => 'centerAlign'));
        $footer->actionLink(array('confirmed' => 1), $this->_('Yes'), array('class' => 'btn-success'));
        $footer->actionLink(array('action' => 'show', 'class' => 'btn-warning'), $this->_('No'), array('class' => 'btn-danger'));

        $this->html[] = $table;
        $this->html->buttonDiv($this->createMenuLinks());

        return false;
    }

    /**
     * Performs actions when the form is submitted, but the submit button was not checked
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param \Zend_Form $form   The populated form
     * @param array     $data   The data-array we are working on
     */
    public function onFakeSubmit(&$form, &$data) {
        if ($this->useMultiRowForm) {
            //Check if the insert button was pressed and act upon that
            if ($this->hasNew() && isset($data['insert_button']) && $data['insert_button']) {
                    // Add a row
                    $model          = $this->getModel();
                    $mname          = $model->getName();
                    $data[$mname][] = $model->loadNew();
            }
        }
    }

    /**
     * Handles a form, including population and saving to the model
     *
     * @param string        $saveLabel  A label describing the form
     * @param array         $data       An array of data to use, adding to the data from the post
     * @return \Zend_Form|null           Returns a form to display or null when finished
     */
    protected function processForm($saveLabel = null, $data = null)
    {
        $model   = $this->getModel();
        $mname   = $model->getName();
        $request = $this->getRequest();
        $isNew   = $request->getActionName() === 'create';

        //\MUtil_Echo::r($data);
        if ($request->isPost()) {
            $data = $request->getPost() + (array) $data;
        } else {
            if (!$this->useMultiRowForm) {
                if (! $data)  {
                    if ($isNew) {
                        $data = $model->loadNew();
                    } else {
                        $data = $model->loadFirst();
                        if (! $data) {
                            $this->addMessage(sprintf($this->_('Unknown %s requested'), $this->getTopic()));
                            $this->afterSaveRoute(array());
                            return false;
                        }
                    }
                }
            } else {
                $data[$mname] = $model->load();

                if (! $data[$mname]) {
                    $data[$mname] = $model->loadNew(2);
                }
            }
        }
        // \MUtil_Echo::r($data, __CLASS__ . '->' . __FUNCTION__ . '(): ' . __LINE__);

        $form = $this->getModelForm($data, $isNew);

        //Handle insert button on multirow forms
        if ($this->useMultiRowForm) {
            if ($this->hasNew()) {
                $form->addElement($form->createElement('fakeSubmit', array(
                    'name' => 'insert_button',
                    'label' => sprintf($this->_('New %1$s...'), $this->getTopic()))));
            }
        }

        //If not already there, add a save button
        $saveButton = $form->getElement('save_button');
        if (! $saveButton) {
            if (null === $saveLabel) {
                $saveLabel = $this->_('Save');
            }

            $saveButton = $form->createElement('submit', 'save_button', array('label' => $saveLabel));
            $saveButton->setAttrib('class', 'button btn-success');
            $form->addElement($saveButton);
        }

        $csrf = $form->getElement($this->csrfId);
        if ($this->useCsrf && (! $csrf)) {
            $form->addElement('hash', $this->csrfId, array(
                'salt' => 'gems_' . $request->getControllerName() . '_' . $request->getActionName(),
                'timeout' => $this->csrfTimeout,
                ));
            $csrf = $form->getElement($this->csrfId);
        }

        if ($request->isPost()) {
            //First populate the form, otherwise the saveButton will never be 'checked'!
            $form->populate($data);
            if ($saveButton->isChecked()) {
                // \MUtil_Echo::r($_POST, 'POST');
                // \MUtil_Echo::r($data, 'data');
                // \MUtil_Echo::r($form->getValues(), 'values');

                // \MUtil_Model::$verbose = true;
                if ($form->isValid($data, false)) {
                    /*
                     * Now that we validated, the form should be populated. I think the step
                     * below is not needed as the values in the form come from the data array
                     * but performing a getValues() cleans the data array so data in post but
                     * not in the form is removed from the data variable
                     */
                    $data = $form->getValues();

                    if ($this->beforeSave($data, $isNew, $form)) {
                        //Save the data
                        if (!$this->useMultiRowForm) {
                            $data = $model->save($data); // Do not add filter, all values are in $_POST
                        } else {
                            $data[$mname] = $model->saveAll($data[$mname]);
                        }
                        //Now check if there were changes
                        if (($changed = $model->getChanged())) {
                            if ($this->afterSave($data, $isNew)) {
                                $this->addMessage(sprintf($this->_('%2$u %1$s saved'), $this->getTopic($changed), $changed), 'success');
                            }
                        } else {
                            $this->addMessage($this->_('No changes to save.'));
                        }

                        //\MUtil_Echo::r($data, 'after process');
                        if ($this->afterSaveRoute($data)) {
                            return null;
                        }
                    }
                } else {
                    $this->addMessage($this->_('Input error! No changes saved!'), 'danger');
                    if ($csrf && $csrf->getMessages()) {
                        $this->addMessage($this->_('The form was open for too long or was opened in multiple windows.'));
                    }
                }
            } else {
                //The default save button was NOT used, so we have a fakesubmit button
                $this->onFakeSubmit($form, $data);
            }
        }
        if (is_array($data)) {
            $this->afterFormLoad($data, $isNew);
        }

        if ($data) {
            $form->populate($data);

            $form = $this->beforeFormDisplay($form, $isNew);

            if ($csrf) {
                $csrf->initCsrfToken();
            }
            return $form;
        }
    }

    /**
     * Set the page title on top of a page, also store it in a public var
     * @param string $title Title
     */
    protected function setPageTitle($title) {

        $this->pageTitle = $title;

        $args = array('class' => 'title');
        $titleTag = \MUtil_Html::create('h3', $title, $args);

        $this->html->append($titleTag);
    }

    /**
     * Shows a table displaying a single record from the model
     *
     * Uses: $this->getModel()
     *       $this->getShowTable();
     */
    public function showAction()
    {
        $this->setPageTitle(sprintf($this->_('Show %s'), $this->getTopic()));

        $model    = $this->getModel();
        // NEAR FUTURE:
        // $this->addSnippet('ModelVerticalTableSnippet', 'model', $model, 'class', 'displayer');
        $repeater = $model->loadRepeatable();
        $table    = $this->getShowTable();
        $table->setOnEmpty(sprintf($this->_('Unknown %s.'), $this->getTopic(1)));
        $table->setRepeater($repeater);
        $table->tfrow($this->createMenuLinks($this->menuShowIncludeLevel), array('class' => 'centerAlign'));

        if ($menuItem = $this->findAllowedMenuItem('edit')) {
            $table->tbody()->onclick = array('location.href=\'', $menuItem->toHRefAttribute($this->getRequest()), '\';');
        }

        $tableContainer = \MUtil_Html::create('div', array('class' => 'table-container'), $table);
        $this->html[] = $tableContainer;
    }
}