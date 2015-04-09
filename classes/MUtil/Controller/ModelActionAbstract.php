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
 * Extends Action with code for working with models.
 *
 * @see \MUtil_Model_ModelAbstract
 *
 * @package    MUtil
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Controller_ModelActionAbstract extends \MUtil_Controller_Action
{
    /**
     *
     * @var boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     */
    public $includeNumericFilters = false;

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
    public $summarizedActions = array();


    /**
     * Set to true in so $this->html is created at startup.
     *
     * @var boolean $useHtmlView true
     */
    public $useHtmlView = true;  // Overrule parent


    /**
     * Created in createModel().
     *
     * Always retrieve using $this->getModel().
     *
     * $var \MUtil_Model_ModelAbstract $_model The model in use
     */
    private $_model;

    /**
     * The request ID value
     *
     * @return string The request ID value
     */
    protected function _getIdParam()
    {
        return $this->_getParam(\MUtil_Model::REQUEST_ID);
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addSortable($name, $label);
            }
        }
    }


    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        foreach($model->getItemsOrdered() as $name) {
            if ($model->has($name, 'label') || $model->has($name, 'elementClass')) {
                $bridge->add($name);
            } else {
                $bridge->addHidden($name);
            }
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
     * @return \MUtil_Model_ModelAbstract
     */
    abstract protected function createModel($detailed, $action);


    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        $form = new \Zend_Form($options);

        return $form;
    }


    /**
     * Creates from the model a \MUtil_Html_TableElement that can display multiple items.
     *
     * @param array $baseUrl
     * @param mixed $sort A valid sort for \MUtil_Model_ModelAbstract->load()
     * @return \MUtil_Html_TableElement
     */
    public function getBrowseTable(array $baseUrl = null, $sort = null, $model = null)
    {
        if (empty($model)) {
            $model  = $this->getModel();
        }

        $bridge = $model->getBridgeFor('table');
        $bridge->getOnEmpty()->raw('&hellip;');
        if ($baseUrl) {
            $bridge->setBaseUrl($baseUrl);
        }

        $this->addBrowseTableColumns($bridge, $model);

        return $bridge->getTable();
    }

    /**
     * Returns the model for the current $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @return \MUtil_Model_ModelAbstract
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

        $bridge = $model->getBridgeFor('form', $this->createForm());

        $this->addFormElements($bridge, $model, $data, $new);

        return $bridge->getForm();
    }

    /**
     * Creates from the model a \MUtil_Html_TableElement for display of a single item.
     *
     * It can and will display multiple items, but that is not what this function is for.
     *
     * @param integer $columns The number of columns to use for presentation
     * @return \MUtil_Html_TableElement
     */
    public function getShowTable($columns = 1)
    {
        $model = $this->getModel();

        $bridge = $model->getBridgeFor('itemTable');
        $bridge->setColumnCount($columns);

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        return $bridge->getTable();
    }


    /**
     * Helper function to determine the ability for the user to create new items
     *
     * return boolean True if the user can add new items
     */
    public function hasNew()
    {
        $model = $this->getModel();

        return $model && $model->hasNew();
    }
}
