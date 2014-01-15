<?php

/**
 * Copyright (c) 201e, Erasmus MC
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
 * @subpackage UnionModelAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $id: UnionModelAbstract.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * A model that uses two or more submodels as a source of data
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
abstract class MUtil_Model_UnionModelAbstract extends MUtil_Model_ModelAbstract
{
    /**
     * The sort for the current load.
     *
     * @var array fieldname => SORT_ASC | SORT_DESC
     */
    private $_sorts;

    /**
     *
     * @var array of $name => MUtil_Model_ModelAbstract
     */
    protected $_unionModels;

    /**
     *
     * @var array of $name => MUtil_Model_ModelTranslatorInterface
     */
    protected $_unionTranslators;

    /**
     * Returns a nested array containing the items requested.
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return array Nested array or false
     */
    protected function _load(array $filter, array $sort)
    {
        $count   = 0;
        $results = array();
        foreach ($this->getUnionModels() as $name => $model) {
            if ($model instanceof MUtil_Model_ModelAbstract) {
                $resultset = $model->load($filter, $sort);

                if ($resultset) {
                    $results = array_merge($results, $resultset);
                    $count   = $count + 1;
                }
            }
        }

        if ($count && $sort) {
            $results = $this->_sortData($results, $sort);
        }

        return $results;
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    protected function _save(array $newValues, array $filter = null)
    {
        $name = $this->getModelNameForRow($newValues);

        if ($name) {
            $model = $this->getUnionModel($name);

            if ($model instanceof MUtil_Model_ModelAbstract) {
                return $model->save($newValues, $filter);
            }
        }
    }

    /**
     * Sorts the output
     *
     * @param array $data
     * @param mixed $sorts
     * @return array
     */
    protected function _sortData(array $data, $sorts)
    {
        $this->_sorts = array();

        foreach ($sorts as $key => $order) {
            if (is_numeric($key) || is_string($order)) {
                if (strtoupper(substr($order,  -5)) == ' DESC') {
                    $order     = substr($order,  0,  -5);
                    $direction = SORT_DESC;
                } else {
                    if (strtoupper(substr($order,  -4)) == ' ASC') {
                        $order = substr($order,  0,  -4);
                    }
                    $direction = SORT_ASC;
                }
                $this->_sorts[$order] = $direction;

            } else {
                switch ($order) {
                    case SORT_DESC:
                        $this->_sorts[$key] = SORT_DESC;
                        break;

                    case SORT_ASC:
                    default:
                        $this->_sorts[$key] = SORT_ASC;
                        break;
                }
            }
        }

        usort($data, array($this, 'sortCmp'));

        return $data;
    }

    /**
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param MUtil_Model_ModelTranslatorInterface $translator
     * @param string $name
     */
    public function addUnionModel(MUtil_Model_ModelAbstract $model, MUtil_Model_ModelTranslatorInterface $translator = null, $name = null)
    {
        if (null === $name) {
            $name = $model->getName();
        }

        $this->_unionModels[$name] = $model;
        $this->_unionTranslators[$name] = $translator;
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {

    }

    /**
     * Get the name of the union model that should be used for this row.
     *
     * @param array $row
     * @return string
     */
    abstract protected function getModelNameForRow(array $row);

    /**
     * Return a union model
     *
     * @return MUtil_Model_ModelAbstract
     */
    public function getUnionModel($name)
    {
        if (isset($this->_unionModels[$name])) {
            return $this->_unionModels[$name];
        }
    }

    /**
     *
     * @return array of $name => MUtil_Model_ModelAbstract
     */
    public function getUnionModels()
    {
        return $this->_unionModels;
    }

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    public function hasNew()
    {
    }

    /**
     * Sort function for sorting array on defined sort order
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public function sortCmp(array $a, array $b)
    {
        foreach ($this->_sorts as $key => $direction) {
            if ($a[$key] !== $b[$key]) {
                // MUtil_Echo::r($key . ': [' . $direction . ']' . $a[$key] . '-' . $b[$key]);
                if (SORT_ASC == $direction) {
                    return $a[$key] > $b[$key] ? 1 : -1;
                } else {
                    return $a[$key] > $b[$key] ? -1 : 1;
                }
            }
        }

        return 0;
    }
}
