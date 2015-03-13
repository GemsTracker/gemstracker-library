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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Contains base functionality to use a model in a snippet.
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see \MUtil_Registry_TargetInterface} mechanism.
 * Description of ModelSnippet
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class MUtil_Snippets_ModelSnippetAbstract extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * Set a fixed model filter.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter;

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort;

    /**
     * The model, use $this->getModel() to fill
     *
     * @var \MUtil_Model_ModelAbstract
     */
    private $_model;

    /**
     * Optional extra filter
     *
     * @var array
     */
    public $extraFilter;

    /**
     * Optional extra sort(s)
     *
     * @var array
     */
    public $extraSort;

    /**
     *
     * @var boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     */
    public $includeNumericFilters = false;

    /**
     * When true the post parameters are removed from the request while filtering
     *
     * @var boolean Should post variables be removed from the request?
     */
    public $removePost = true;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Searchfilter to use including model sorts, etcc..
     *
     * The default is false, to signal that no data was passed. Any other value including
     * null means the value is used.
     *
     * @var array
     */
    protected $searchFilter = false;

    /**
     * The $request param that stores the ascending sort
     *
     * @var string
     */
    protected $sortParamAsc;

    /**
     * The $request param that stores the descending sort
     *
     * @var string
     */
    protected $sortParamDesc;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    abstract protected function createModel();

    /**
     * Returns the model, always use this function
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function getModel()
    {
        if (! $this->_model) {
            $this->_model = $this->createModel();

            $this->prepareModel($this->_model);
        }

        return $this->_model;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return (boolean) $this->getModel();
    }

    /**
     * Default processing of $model from standard settings
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected final function prepareModel(\MUtil_Model_ModelAbstract $model)
    {
        if ($this->sortParamAsc) {
            $model->setSortParamAsc($this->sortParamAsc);
        }
        if ($this->sortParamDesc) {
            $model->setSortParamDesc($this->sortParamDesc);
        }

        $this->processFilterAndSort($model);

        if ($this->_fixedFilter) {
            $model->addFilter($this->_fixedFilter);
        }
        if ($this->extraFilter) {
            $model->addFilter($this->extraFilter);
        }
        if ($this->extraSort) {
            $model->addSort($this->extraSort);
        }
        if ($this->_fixedSort) {
            $model->addSort($this->_fixedSort);
        }
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        if (false !== $this->searchFilter) {
            if (isset($this->searchFilter['limit'])) {
                $model->addFilter(array('limit' => $this->searchFilter['limit']));
                unset($this->searchFilter['limit']);
            }
            $model->applyParameters($this->searchFilter, true);

        } elseif ($this->request instanceof \Zend_Controller_Request_Abstract) {
            $model->applyRequest($this->request, $this->removePost, $this->includeNumericFilters);
        }
    }

    /**
     * Use this when overruling processFilterAndSort()
     *
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processSortOnly(\MUtil_Model_ModelAbstract $model)
    {
        if ($this->request) {
            if ($sort = $this->request->getParam($model->getSortParamAsc())) {
                $model->addSort(array($sort => SORT_ASC));
            } elseif ($sort = $this->request->getParam($model->getSortParamDesc())) {
                $model->addSort(array($sort => SORT_DESC));
            }
        }
    }
}
