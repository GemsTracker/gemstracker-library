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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ArrayModelAbstract.php$
 */

/**
 * Generic model for data storage that does not come with it's own
 * storage engine; e.g. text/xml files, directories, session arrays.
 *
 * The basics are: create an iterable item to walk through the content
 * and then filer / sort that content one row at the time.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
abstract class MUtil_Model_ArrayModelAbstract extends MUtil_Model_ModelAbstract
{
    /**
     * When set to true in a subclass, then the model should be able to
     * save itself.
     *
     * @var boolean
     */
    protected $_saveable = false;

    /**
     * The sort for the current load.
     *
     * @var array fieldname => SORT_ASC | SORT_DESC
     */
    private $_sorts;

    /**
     * Returns true if the passed row passed through the filter
     *
     * @param array $row A row of data
     * @param array $filters An array of filter statements
     * @param boolean $logicalAnd When true this is an AND filter, otherwise OR (switches at each array nesting level)
     * @return boolean
     */
    protected function _applyFiltersToRow(array $row, array $filters, $logicalAnd)
    {
        foreach ($filters as $name => $filter) {
            if (is_callable($filter)) {
                if (is_numeric($name)) {
                    $value = $row;
                } else {
                    $value = isset($row[$name]) ? $row[$name] : null;
                }
                $result = call_user_func($filter, $value);

            } elseif (is_array($filter)) {
                if (is_numeric($name)) {
                    $subFilter = $filter;
                } else {
                    $subFilter = array();
                    foreach ($filter as $key => $val) {
                        if (is_numeric($key)) {
                            $subFilter[$name] = $val;
                        } else {
                            $subFilter[$key] = $val;
                        }
                    }
                }
                $result = $this->_applyFiltersToRow($row, $subFilter, ! $logicalAnd);

            } else {
                if (is_numeric($name)) {
                    // Allow literal value interpretation
                    $result = (boolean) $value;
                } else {
                    $value = isset($row[$name]) ? $row[$name] : null;
                    $result = ($value === $filter);
                }
                // MUtil_Echo::r($value . '===' . $filter . '=' . $result);
            }

            if ($logicalAnd xor $result) {
                return $result;
            }
        }

        // If $logicalAnd is true:
        //   => all filters must have triggered true to arrive here
        //   => the result is true,
        // If $logicalAnd is false:
        //   => all filters must have triggered false to arrive here
        //   => the result is false.
        return $logicalAnd;
    }

    /**
     * Filters the data array using a model filter
     *
     * @param Traversable $data
     * @param array $filters
     * @return Traversable
     */
    protected function _filterData($data, array $filters)
    {
        if ($data instanceof IteratorAggregate) {
            $data = $data->getIterator();
        }

        // If nothing to filter
        if (! $filters) {
            return $data;
        }

        if ($data instanceof Iterator) {
            return new MUtil_Model_Iterator_ArrayModelFilterIterator($data, $this, $filters);
        }

        $filteredData = array();
        foreach ($data as $key => $row) {
            if ($this->_applyFiltersToRow($row, $filters, true)) {
                // print_r($row);
                $filteredData[$key] = $row;
            }
        }

        return $filteredData;
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
        $data = $this->_loadAllTraversable();

        if ($filter) {
            $data = $this->_filterData($data, $filter);
        }

        if (! is_array($data)) {
            $data = iterator_to_array($data);
        }

        if ($sort) {
            $data = $this->_sortData($data, $sort);
        }

        return $data;
    }

    /**
     * An ArrayModel assumes that (usually) all data needs to be loaded before any load
     * action, this is done using the iterator returned by this function.
     *
     * @return Traversable Return an iterator over or an array of all the rows in this object
     */
    abstract protected function _loadAllTraversable();


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
        if ($this->_saveable) {
            $data = $this->_loadAllTraversable();
            if ($data instanceof Traversable) {
                $data = iterator_to_array($this->_loadAllTraversable());
            }

            if ($keys = $this->getKeys()) {
                $search = array();
                $newValues = $newValues + $filter;

                foreach ($keys as $key) {
                    if (isset($newValues[$key])) {
                        $search[$key] = $newValues[$key];
                    } else {
                        // Crude but hey
                        throw new MUtil_Model_ModelException(sprintf('Key value "%s" missing when saving data.', $key));
                    }
                }

                $rowId = MUtil_Ra::findKeys($data, $search);

                if ($rowId) {
                    // Overwrite to new values
                    $data[$rowId] = $newValues + $data[$rowId];
                } else {
                    $data[] = $newValues;
                }


            } else {
                $data[] = $newValues;
            }

            $this->_saveAllTraversable($data);

            return $newValues;
        } else {
            throw new MUtil_Model_ModelException(sprintf('Save not implemented for model "%s".', $this->getName()));
        }
    }

    /**
     * When $this->_saveable is true a child class should either override the
     * delete() and save() functions of this class or override _saveAllTraversable().
     *
     * In the latter case this class will use _loadAllTraversable() and remove / add the
     * data to the data in the delete() / save() functions and pass that data on to this
     * function.
     *
     * @param array $data An array containing all the data that should be in this object
     * @return void
     */
    protected function _saveAllTraversable(array $data)
    {
        throw new MUtil_Model_ModelException(
                sprintf('Function "%s" should be overriden for class "%s".', __FUNCTION__, __CLASS__)
                );
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
     * Returns true if the passed row passed through the filter
     *
     * @param array $row A row of data
     * @param array $filters An array of filter statements
     * @return boolean
     */
    public function applyFiltersToRow(array $row, array $filters)
    {
        return $this->_applyFiltersToRow($row, $filters, true);
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {
        if ($this->_saveable) {
            // TODO: implement
        } else {
            throw new MUtil_Model_ModelException(sprintf('Delete not implemented for model "%s".', $this->getName()));
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
        $filter = array();

        if ($searchText) {
            $fields = array();
            foreach ($this->getItemNames() as $name) {
                // TODO: multiOptions integratie
                if ($this->get($name, 'label')) {
                    $fields[] = $name;
                }
            }

            if ($fields) {
                foreach ($this->getTextSearches($searchText) as $searchOn) {
                    $textFilter = array();

                    // Almost always use, this allows reuse
                    $textFunction = function ($value) use ($searchOn) {
                        // MUtil_Echo::track($value . ' - ' . $searchOn . ' = ' . MUtil_String::contains($value, $searchOn));
                        return MUtil_String::contains($value, $searchOn);
                    };

                    foreach ($fields as $name) {
                        if ($options = $this->get($name, 'multiOptions')) {
                            $items = array();
                            foreach ($options as $value => $label) {
                                if (MUtil_String::contains($label, $searchOn)) {
                                    $items[$value] = $value;
                                }
                            }
                            if ($items) {
                                if (count($items) == count($options)) {
                                    // This filter always returns true, do not add this filter
                                    // MUtil_Echo::track('Always true');
                                    $textFilter = false;
                                    break;
                                }
                                // Function is different for each multiOptions
                                $textFilter[$name] = function ($value) use ($items) {
                                    return array_key_exists($value, $items);
                                };
                            }
                        } else {
                            $textFilter[$name] = $textFunction;
                        }
                    }
                    if ($textFilter) {
                        $filter[] = $textFilter;
                    }
                }
            }
        }

        return $filter;
    }

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    public function hasNew()
    {
        // We assume this to be the case, unless the child model overrules this method.
        return $this->_saveable;
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
        return true;
    }

    /**
     * Returns a Traversable spewing out arrays containing the items requested.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return Traversable
     */
    public function loadIterator($filter = true, $sort = true)
    {
        $data = $this->_loadAllTraversable();

        if ($data && $filter) {
            $data = $this->_filterData($data, $this->_checkFilterUsed($filter));
        }

        if ($this->_checkSortUsed($sort)) {
            throw new MUtil_Model_ModelException("You cannot sort an array iterator.");
        }

        return $data;
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
