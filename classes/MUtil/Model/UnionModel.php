<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @version    $id: UnionModel.php 203 2012-01-01t 12:51:32Z matijs $
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
class MUtil_Model_UnionModel extends \MUtil_Model_ModelAbstract
{
    /**
     * Identifier for text filter field
     */
    const TEXT_FILTER = 'text_filter__';

    /**
     * When empty nothing is cleared
     *
     * @var array keyname => keyname
     */
    protected $_clearableKeys = array();

    /**
     * The extra field where the submodel is stored
     *
     * @var string
     */
    protected $_modelField;

    /**
     * The sort for the current load.
     *
     * @var array fieldname => SORT_ASC | SORT_DESC
     */
    private $_sorts;

    /**
     * Contains a map from the fields names of a sub model to the fields names
     * of this model.
     *
     * @var array of $name => array map or false when no mapping occurs
     */
    protected $_unionMapsFrom;

    /**
     * Contains a map to the fields names of a sub model from the fields names
     * of this model.
     *
     *
     * @var array of $name => array map or false when no mapping occurs
     */
    protected $_unionMapsTo;

    /**
     *
     * @var array of $name => \MUtil_Model_ModelAbstract
     */
    protected $_unionModels;

    /**
     * Name of the field used for temporary storage of the text filter
     *
     * @var string
     */
    public $textFilterField = self::TEXT_FILTER;

    /**
     *
     * @param string $modelName Hopefully unique model name
     * @param string $modelField The name of the field used to store the sub model
     */
    public function __construct($modelName = 'fields_maintenance', $modelField = 'sub')
    {
        parent::__construct($modelName);

        $this->_modelField = $modelField;
        $this->set($this->_modelField, 'elementClass', 'Hidden');
    }

    /**
     * Get the models that should be checked for this filter
     * (and adjust the filter to prevent query errors).
     *
     * @param array $filter
     * @return array of name => model
     */
    protected function _getFilterModels(array &$filter)
    {
        if (isset($filter[$this->_modelField])) {
            $name = $filter[$this->_modelField];
            unset($filter[$this->_modelField]);
            return array($name => $this->getUnionModel($name));
        }

        return $this->getUnionModels();
    }

    /**
     * Returns a nested array containing the items requested.
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return array Nested array or false
     */
    protected function _load(array $filter, array $sort)
    {
        $setcount = 0;
        $results  = array();

        if (isset($filter[$this->textFilterField])) {
            $textFilter = $filter[$this->textFilterField];
            unset($filter[$this->textFilterField]);
        } else {
            $textFilter = false;
        }

        foreach ($this->_getFilterModels($filter) as $name => $model) {
            if ($model instanceof \MUtil_Model_ModelAbstract) {

                $modelFilter = $this->_map($filter, $name, false, true);

                if (isset($this->_unionMapsTo[$name]) && $this->_unionMapsTo[$name]) {
                    // Translate the texts filters
                    foreach ($modelFilter as $key => $value) {
                        if (is_numeric($key) && is_string($value)) {
                            $modelFilter[$key] = strtr($value, $this->_unionMapsTo[$name]);
                        }
                    }
                }

                if (\MUtil_Model::$verbose) {
                    \MUtil_Echo::r($modelFilter, "Filter for model $name.");
                }
                if ($textFilter) {
                    // Text filter is always on visible fields and uses multiOptions
                    if (isset($this->_unionMapsTo[$name]) && $this->_unionMapsTo[$name]) {
                        foreach ($this->getCol('label') as $fname => $label) {
                            if (isset($this->_unionMapsTo[$name][$fname])) {
                                $mname = $this->_unionMapsTo[$name][$fname];
                            } else {
                                $mname = $fname;
                            }
                            $model->set($mname, 'label', $label, 'multiOptions', $this->get($fname, 'multiOptions'));
                        }
                    } else {
                        foreach ($this->getCol('label') as $fname => $label) {
                            $model->set($fname, 'label', $label, 'multiOptions', $this->get($fname, 'multiOptions'));
                        }
                    }
                    $modelFilter = array_merge($modelFilter, $model->getTextSearchFilter($textFilter));
                }

                $resultset = $model->load($modelFilter, $this->_map($sort, $name, false, false));

                if ($resultset) {
                    $sub = array($this->_modelField => $name);
                    foreach ($resultset as $row) {
                        $results[] = $sub + $this->_map($row, $name, true, false);
                    }
                    $setcount = $setcount + 1;
                }
            }
        }

        if ($setcount && $sort) {
            $results = $this->_sortData($results, $sort);
        }

        return $results;
    }

    /**
     * Map the fields in a row of values from|to a sub model
     *
     * @param array $row The row of values to map
     * @param string $name Union sub model name
     * @param boolean $from When true map from the fields names in the sub model to the fields names of this model
     * @param boolean $recursive When true sub arrays are mapped as well (only used for filter renaming)
     * @return array
     */
    protected function _map(array $row, $name, $from = true, $recursive = false)
    {
        if ($from) {
            $mapStore = $this->_unionMapsFrom;
        } else {
            $mapStore = $this->_unionMapsTo;
        }

        if (! (isset($mapStore[$name]) && $mapStore[$name])) {
            return $row;
        }

        return \MUtil_Ra::map($row, $mapStore[$name], $recursive);
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
        $newValues = $this->_filterDataForSave($newValues);

        $newName = $this->getModelNameForRow($newValues);

        if (isset($filter[$this->_modelField])) {
            $oldName = $filter[$this->_modelField];
        } elseif (isset($newValues[$this->_modelField])) {
            $oldName = $newValues[$this->_modelField];
        } else {
            $oldName = false;
        }

        if ($oldName && ($oldName != $newName)) {
            $model     = $this->getUnionModel($oldName);
            $modelKeys = $this->_map($model->getKeys(), $oldName, false, false);

            // Make sure both the names and the keys are in the keys of the array
            $modelKeys    = $modelKeys + array_combine($modelKeys, $modelKeys);
            $deleteFilter = array_intersect_key($this->_map($newValues, $oldName, false, false), $modelKeys);

            if ($deleteFilter) {
                $model->delete($deleteFilter);

                $cleanup = $this->getClearableKeys($newValues);
                if ($cleanup) {
                    // Make sure both the names and the keys are in the keys of the array
                    $newValues = array_diff_key($newValues, $cleanup, array_combine($cleanup, $cleanup));
                }
            }
        }
        if ($newName) {
            $model  = $this->getUnionModel($newName);
            $result = $model->save(
                    $this->_map($newValues, $newName, false, false),
                    $this->_map((array) $filter, $newName, false, true)
                    );

            $this->setChanged($model->getChanged());

            return array($this->_modelField => $newName) + $this->_map($result, $newName, true, false);
        }

        throw new \MUtil_Model_ModelException('Could not save to union model as values do not belong to a sub model.');
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
     * Add an extra model to the union
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $fieldMap Map from the sub model field names to this models names
     * @param string $name
     * @return \MUtil_Model_UnionModelAbstract (continuation pattern)
     */
    public function addUnionModel(\MUtil_Model_ModelAbstract $model, array $fieldMap = null, $name = null)
    {
        if (null === $name) {
            $name = $model->getName();
        }

        $this->_unionModels[$name] = $model;

        if ($fieldMap) {
            $this->_unionMapsFrom[$name] = $fieldMap;
            $this->_unionMapsTo[$name]   = array_flip($fieldMap);
        } else {
            $this->_unionMapsFrom[$name] = false;
            $this->_unionMapsTo[$name]   = false;
            $fieldMap = array();
        }
        foreach ($model->getItemsOrdered() as $subName) {
            if (isset($fieldMap[$subName])) {
                $mainName = $fieldMap[$subName];
            } else {
                $mainName = $subName;
            }
            $this->set($mainName, $model->get($subName));
        }

        return $this;
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {
        $filter  = $this->_checkFilterUsed($filter);
        $deleted = 0;
        foreach ($this->_getFilterModels($filter) as $name => $model) {
            if ($model instanceof \MUtil_Model_ModelAbstract) {
                $deleted = $deleted + $model->delete($this->_map($filter, $name, false, true));
            }
        }
        return $deleted;
    }

    /**
     * Gets the keys that should be cleared when moving a field from one submodel to another
     *
     * @param array $rows An optional row, this allows submodels to specify the clearable keys per row
     * @return array name => name
     */
    public function getClearableKeys(array $row = null)
    {
        return $this->_clearableKeys;
    }

    /**
     * Get the name of the union model that should be used for this row.
     *
     * Not overruling this role means that the content of the row can never
     * result in a switch from one sub-model to another sub-model.
     *
     * Also you will have to handle the setting of the correct model manually
     *
     * @param array $row
     * @return string
     */
    public function getModelNameForRow(array $row)
    {
        if (isset($row[$this->_modelField])) {
            return $row[$this->_modelField];
        }
    }

    /**
     * Creates a filter for this model for the given wildcard search text.
     *
     * @param string $searchText
     * @return array An array of filter statements for wildcard text searching for this model type
     */
    public function getTextSearchFilter($searchText)
    {
        return array($this->textFilterField => $searchText);
    }

    /**
     * Return a union model
     *
     * @param string The name of the
     * @return \MUtil_Model_ModelAbstract
     */
    public function getUnionModel($name)
    {
        if (isset($this->_unionModels[$name])) {
            return $this->_unionModels[$name];
        }
    }

    /**
     *
     * @return array of $name => \MUtil_Model_ModelAbstract
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
        // All sub models must allow new rows
        foreach ($this->_unionModels as $model) {
            if (! ($model instanceof \MUtil_Model_ModelAbstract && $model->hasNew())) {
                return false;
            }
        }

        return true;
    }

    /**
     * True when the model supports general text filtering on all
     * labelled fields.
     *
     * This must be implemented by each sub model on it's own.
     *
     * @return boolean
     */
    public function hasTextSearchFilter()
    {
        // All sub models must allow new rows
        foreach ($this->_unionModels as $model) {
            if (! ($model instanceof \MUtil_Model_ModelAbstract && $model->hasTextSearchFilter())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sets the keys that should be cleared when moving a field from one submodel to another
     *
     * @param array $keys name => name, when empty the whole key of the model is cleared,
     * an empty array means nothing is cleared.
     * @return \MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setClearableKeys(array $keys = null)
    {
        if (null === $keys) {
            $keys = $this->getKeys();
        }

        $this->_clearableKeys = $keys;
    }

    /**
     * Sets the keys, processing the array key.
     *
     * When an array key is numeric \MUtil_Model::REQUEST_ID is used.
     * When there is more than one key a increasing number is added to
     * \MUtil_Model::REQUEST_ID starting with 1.
     *
     * String key names are left as is.
     *
     * @param array $keys [alternative_]name or number => name
     * @return \MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setKeys(array $keys)
    {
        if (! isset($keys[$this->_modelField])) {
            $keys[$this->_modelField] = $this->_modelField;
        }

        return parent::setKeys($keys);
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
                // \MUtil_Echo::r($key . ': [' . (SORT_ASC == $direction ? 'up' : 'down') . '] ' . $a[$key] . '-' . $b[$key]);
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
