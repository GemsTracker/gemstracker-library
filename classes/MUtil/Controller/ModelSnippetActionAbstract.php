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
 * @package    MUtil
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Controller class with standard model and snippet based browse (index), IN THE NEAR FUTURE show, edit and delete actions.
 *
 * To change the behaviour of this class the prime method is changing the snippets used for an action.
 *
 * @package    MUtil
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.2
 */
abstract class MUtil_Controller_ModelSnippetActionAbstract extends MUtil_Controller_Action
{
    /**
     * Default parameters for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_defaultAutofilterParameters = array(
        'searchData'    => 'getSearchData',
        'searchFilter'  => 'getSearchFilter',
        );

    /**
     * Default parameters for createAction, can be overruled by $this->createParameters
     * or $this->createEditParameters values with the same key.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array
     */
    private $_defaultCreateParameters = array(
        'createData' => true,
        );

    /**
     * Default parameters for editAction, can be overruled by $this->editParameters
     * or $this->createEditParameters values with the same key.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array
     */
    private $_defaultEditParameters = array(
        'createData' => false,
        );

    /**
     * Default parameters for all actions, unless overruled by values with the same key at
     * the action level
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array
     */
    private $_defaultParameters = array(
        'includeNumericFilters' => 'getIncludeNumericFilters',
        'model'                 => 'getModel',
        'request'               => 'getRequest',
        );

    /**
     * Created in createModel().
     *
     * Always retrieve using $this->getModel().
     *
     * $var MUtil_Model_ModelAbstract $_model The model in use
     */
    private $_model;

    /**
     *
     * @var array The search data
     */
    private $_searchData = false;

    /**
     *
     * @var array The search data
     */
    private $_searchFilter = false;

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
    protected $autofilterParameters = array('columns' => 'getBrowseColumns');

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'ModelTableSnippet';

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
    protected $createEditParameters = array();

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'ModelFormSnippet';

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createParameters = array();

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = array();

    /**
     * The parameters used for the delete action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deleteParameters = array();

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'ModelYesNoDeleteSnippet';

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $editParameters = array();

    /**
     * The parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $importParameters = array(
        'defaultImportTranslator' => 'getDefaultImportTranslator',
        'importTranslators'       => 'getImportTranslators',
    );

    /**
     * The snippets used for the import action
     *
     * @var mixed String or array of snippets name
     */
    protected $importSnippets = 'ModelImportSnippet';

    /**
     *
     * @var boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     */
    public $includeNumericFilters = false;

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $indexParameters = array();

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = null;

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = null;

    /**
     * Optional search field renames
     *
     * The optional sharing of searches between action using searchSessionId's means that sometimes
     * the fields in the search have to be renamed for a specific action.
     *
     * @var array
     */
    protected $searchFieldRenames = array();

    /**
     * An optional search session id.
     *
     * When set, autosearch gets a session memory. Multiple controllers can share one session id
     *
     * @var string
     */
    protected $searchSessionId;

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
    protected $showParameters = array();

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = 'ModelVerticalTableSnippet';

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
    public $summarizedActions = array('index', 'autofilter');

    /**
     * Set to true in so $this->html is created at startup.
     *
     * @var boolean $useHtmlView true
     */
    public $useHtmlView = true;  // Overrule parent

    /**
     * The request ID value
     *
     * @return string The request ID value
     */
    protected function _getIdParam()
    {
        return $this->_getParam(MUtil_Model::REQUEST_ID);
    }

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processParameters(array $input)
    {
        $output = array();

        foreach ($input + $this->_defaultParameters as $key => $value) {
            if (is_string($value) && method_exists($this, $value)) {
                $value = $this->$value($key);

                if (is_integer($key) || ($value === null)) {
                    continue;
                }
            }
            $output[$key] = $value;
        }

        return $output;
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

    /**
     * Apply this source to the target.
     *
     * @param MUtil_Registry_TargetInterface $target
     * @return boolean True if $target is OK with loaded requests
     */
    public function applySource(MUtil_Registry_TargetInterface $target)
    {
        return $this->getSnippetLoader()->getSource()->applySource($target);
    }


    /**
     * The automatically filtered result
     *
     * @param $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        // MUtil_Model::$verbose = true;

        // We do not need to return the layout, just the above table
        if ($resetMvc) {
            // Make sure all links are generated as if the current request was index.
            $this->aliasAction('index');

            Zend_Layout::resetMvcInstance();
        }

        if ($this->autofilterSnippets) {
            $params = $this->_processParameters($this->autofilterParameters + $this->_defaultAutofilterParameters);

            $this->addSnippets($this->autofilterSnippets, $params);
        }

        if ($resetMvc) {
            // Lazy call here, because any echo calls in the snippets have not yet been
            // performed. so they will appear only in the next call when not lazy.
            $this->html->raw(MUtil_Lazy::call(array('MUtil_Echo', 'out')));
        }
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction()
    {
        if ($this->createEditSnippets) {
            $params = $this->_processParameters($this->createParameters + $this->createEditParameters + $this->_defaultCreateParameters);

            $this->addSnippets($this->createEditSnippets, $params);
        }
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
     * @return MUtil_Model_ModelAbstract
     */
    abstract protected function createModel($detailed, $action);

    /**
     * Action for showing a delete item page
     */
    public function deleteAction()
    {
        if ($this->deleteSnippets) {
            $params = $this->_processParameters($this->deleteParameters);

            $this->addSnippets($this->deleteSnippets, $params);
        }
    }

    /**
     * Action for showing a edit item page
     */
    public function editAction()
    {
        if ($this->createEditSnippets) {
            $params = $this->_processParameters($this->editParameters + $this->createEditParameters + $this->_defaultEditParameters);

            $this->addSnippets($this->createEditSnippets, $params);
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
        return false;
    }

    /**
     * Name of the default import translator
     *
     * @return string
     */
    public function getDefaultImportTranslator()
    {
        return 'default';
    }

    /**
     * Get the possible translators for the import snippet.
     *
     * @return array of MUtil_Model_ModelTranslatorInterface objects
     */
    public function getImportTranslators()
    {
        $trs = new MUtil_Model_Translator_StraightTranslator($this->_('Direct import'));
        $this->applySource($trs);

        return array('default' => $trs);
    }

    /**
     *
     * @return boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     */
    public function getIncludeNumericFilters()
    {
        return $this->includeNumericFilters;
    }

    /**
     * Returns the model for the current $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function getModel()
    {
        $request = $this->getRequest();
        $action  = null === $request ? '' : $request->getActionName();

        // Only get new model if there is no model or the model was for a different action
        if (! ($this->_model && $this->_model->isMeta('action', $action))) {
            $detailed = ! in_array($action, $this->summarizedActions);

            $this->_model = $this->createModel($detailed, $action);
            $this->_model->setMeta('action', $action);

            // Detailed models DO NOT USE $_POST for filtering,
            // multirow models DO USE $_POST parameters for filtering.
            $this->_model->applyRequest($request, $detailed, $this->includeNumericFilters);
        }

        return $this->_model;
    }

    /**
     * Get the data to use for searching: the values passed in the request + any defaults
     * used in the search form (or any other search request mechanism).
     *
     * It does not return the actual filter used in the query.
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchData()
    {
        if ($this->_searchData) {
            return $this->_searchData;
        }

        $data = $this->request->getParams();

        // remove controler/action/module
        unset($data[$this->request->getModuleKey()],
                $data[$this->request->getControllerKey()],
                $data[$this->request->getActionKey()]);

        if ($this->searchSessionId) {
            $sessionId = $this->searchSessionId;
        } else {
            // Always use a search id
            $sessionId = get_class($this);
        }

        $searchSession = new \Zend_Session_Namespace('ModelSnippetActionAbstract_getSearchData');
        if (isset($searchSession->$sessionId)) {
            $sessionData = $searchSession->$sessionId;
            // \MUtil_Echo::track($sessionData);
        } else {
            $sessionData = array();
        }

        if (isset($data[\MUtil_Model::AUTOSEARCH_RESET]) && $data[\MUtil_Model::AUTOSEARCH_RESET]) {
            // Clean up values
            $sessionData = array();

            $this->request->setParam(\MUtil_Model::AUTOSEARCH_RESET, null);
        } else {
            $data = $data + $sessionData;
        }

        // Always remove
        unset($data[\MUtil_Model::AUTOSEARCH_RESET]);

        // Store cleaned values in session (we do not store the defaults as they may change
        // depending on the request and this way the filter data responds to that).
        $searchSession->$sessionId = array_filter($data, function($i) { return is_array($i) || strlen($i); });

        // Add defaults to data without cleanup
        if ($this->defaultSearchData) {
            $data = $data + $this->defaultSearchData;
        }

        // \MUtil_Echo::track($data, $this->searchSessionId);

        // Remove empty strings and nulls HERE as they are not part of
        // the filter itself, but the values should be stored in the session.
        //
        // Remove all empty values (but not arrays) from the filter
        $this->_searchData = array_filter($data, function($i) { return is_array($i) || strlen($i); });

        // \MUtil_Echo::track($this->_searchData, $this->searchSessionId);

        return $this->_searchData;
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @return array or false
     */
    public function getSearchFilter()
    {
        if (false !== $this->_searchFilter) {
            return $this->_searchFilter;
        }

        $filter = $this->getSearchData();
        $this->_searchFilter = array();

        foreach ($filter as $field => $value) {
            if (isset($this->searchFieldRenames[$field])) {
                $field = $this->searchFieldRenames[$field];
            }

            $this->_searchFilter[$field] = $value;
        }

        return $this->_searchFilter;
    }

    /**
     * Generic model based import action
     */
    public function importAction()
    {
        if ($this->importSnippets) {
            $params = $this->_processParameters($this->importParameters);

            $this->addSnippets($this->importSnippets, $params);
        }
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        if ($this->indexStartSnippets || $this->indexStopSnippets) {
            $params = $this->_processParameters(
                    $this->indexParameters + $this->autofilterParameters + $this->_defaultAutofilterParameters
                    );

            if ($this->indexStartSnippets) {
                $this->addSnippets($this->indexStartSnippets, $params);
            }
        }

        $this->autofilterAction(false);

        if ($this->indexStopSnippets) {
            $this->addSnippets($this->indexStopSnippets, $params);
        }
    }

    /**
     * Action for showing an item page
     */
    public function showAction()
    {
        if ($this->showSnippets) {
            $params = $this->_processParameters($this->showParameters);

            $this->addSnippets($this->showSnippets, $params);
        }
    }
}
