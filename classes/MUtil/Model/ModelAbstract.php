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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * A model combines knowedge about a set of data with knowledge required to manipulate
 * that set of data. I.e. it can store data about fields such as type, label, length,
 * etc... and meta data about the object like the current query filter and sort order,
 * with manipulation methods like save(), load(), loadNew() and delete().
 *
 * The field level values are accessed e.g. through the del(), get() and set() methods.
 * Anything can be stored on the field level, it is up to the code working with the
 * model to determine what should be set.
 *
 * The meta data is accessed using getMeta(), isMeta(), setMeta(). What meta data is
 * stored is also up to the application.
 *
 * The manipulation methods ARE defined by ModelAbstract and must be implemented by
 * the any subclass.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Model_ModelAbstract extends MUtil_Registry_TargetAbstract
{
    const ALIAS_OF  = 'alias_of';
    const AUTO_SAVE = 'auto_save';
    const LOAD_TRANSFORMER = 'load_transformer';
    const SAVE_TRANSFORMER = 'save_transformer';
    const SAVE_WHEN_TEST   = 'save_when_test';

    private $_changedCount = 0;
    private $_keys;
    private $_model = array();
    private $_model_meta;
    private $_model_name;
    private $_model_order;
    private $_model_used = false;

    /**
     * The increment for item ordering, default is 10
     *
     * @var int
     */
    public $orderIncrement = 10;

    public function __construct($modelName)
    {
        $this->_model_name = $modelName;
    }

    /**
     * Checks the filter on sematic correctness and replaces the text seacrh filter
     * with the real filter.
     *
     * @param mixed $filter True for the filter stored in this model or a filter array
     * @return array The filter to use
     */
    protected function _checkFilterUsed($filter)
    {
        if (true === $filter) {
            $filter = $this->getFilter();
        }
        if ($filter && is_array($filter)) {

            if ($this->hasTextSearchFilter() && ($param = $this->getTextFilter())) {
                if (isset($filter[$param])) {
                    $textFilter = $this->getTextSearchFilter($filter[$param]);
                    unset($filter[$param]);
                    return array_merge($filter, $textFilter);
                }
            }

            return $filter;
        }

        return array();
    }

    protected function _checkSortUsed($sort)
    {
        if (true === $sort) {
            return $this->getSort();
        }
        if (false === $sort) {
            return array();
        }

        return $this->_checkSortValue($sort);
    }

    protected function _checkSortValue($value)
    {
        if ($value) {
            if (is_array($value)) {
                return $value;
            } else {
                return array($value => SORT_ASC);
            }
        } else {
            return array();
        }
    }

    /**
     * Processes empty strings, filters items that should not be saved
     * according to setSaveWhen() and changes values that have a setOnSave()
     * function.
     *
     * @see setOnSave
     * @set setSaveWhen
     *
     * @param array $data The values to save
     * @param boolean $new True when it is a new item
     * @return array The possibly adapted array of values
     */
    protected function _filterDataAfterLoad(array $data, $new = false)
    {
        foreach ($data as $name => $value) {
            $data[$name] = $this->getOnLoad($value, $new, $name, $data);
        }

        return $data;
    }

    /**
     * Processes empty strings, filters items that should not be saved
     * according to setSaveWhen() and changes values that have a setOnSave()
     * function.
     *
     * @see setOnSave
     * @set setSaveWhen
     *
     * @param array $data The values to save
     * @param boolean $new True when it is a new item
     * @return array The possibly adapted array of values
     */
    protected function _filterDataForSave(array $data, $new = false)
    {
        // MUtil_Echo::r($data, 'preFilter');

        foreach ($data as $name => $value) {
            if ('' === $value) {
                // Remove default empty string values.
                $value = null;
            }

            if ($this->isSaveable($value, $new, $name, $data)) {
                $filteredData[$name] = $this->getOnSave($value, $new, $name, $data);
            }
        }

        // MUtil_Echo::r($filteredData, 'afterFilter');

        return $filteredData;
    }

    protected function _getKeyValue($name, $key)
    {
        if (isset($this->_model[$name][$key])) {
            $value = $this->_model[$name][$key];

            if ($value instanceof MUtil_Lazy_LazyInterface) {
                $value = MUtil_Lazy::rise($value);
            }

            return $value;
        }
        if ($name = $this->getAlias($name)) {
            return $this->_getKeyValue($name, $key);
        }

        return null;
    }

    protected static function _getValueFrom($fieldName, $fromData)
    {
        if ($fromData instanceof MUtil_Lazy_RepeatableInterface) {
            return $fromData->$fieldName;
        } else {
            if (isset($fromData[$fieldName])) {
                return $fromData[$fieldName];
            }
        }
    }

    /**
     * Returns a nested array containing the items requested.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return array Nested array or false
     */
    abstract protected function _load($filter = true, $sort = true);

    protected function addChanged($add = 1)
    {
        $this->_changedCount += $add;

        return $this;
    }

    public function addFilter(array $value)
    {
        if ($old = $this->getFilter()) {
            if (MUtil_Model::$verbose) {
                MUtil_Echo::r($value, 'New filter');
                MUtil_Echo::r($old, 'Old filter');
                MUtil_Echo::r(array_merge($value, $old), 'Merged filter');
            }

            $this->setFilter(array_merge($value, $old));
        } else {
            $this->setFilter($value);
        }

        return $this;
    }

    /**
     * Add's one or more sort fields to the standard sort.
     *
     * @param mixed $value Array of sortfield => SORT_ASC|SORT_DESC or single sortfield for ascending sort.
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function addSort($value)
    {
        $value = $this->_checkSortValue($value);

        if ($old = $this->getSort()) {
            $this->setSort($old + $value);
        } else {
            $this->setSort($value);
        }
        return $this;
    }

    /**
     * Stores the fields that can be used for sorting or filtering in the
     * sort / filter objects attached to this model.
     *
     * @param array $parameters
     * @return array The $parameters minus the sort & textsearch keys
     */
    public function applyParameters(array $parameters)
    {
        if ($parameters) {
            if (MUtil_Model::$verbose) {
                MUtil_Echo::r($parameters, 'Model parameters');
            }

            // Check for sort parameters and apply + remove them from the filter
            $nosort = true;
            if ($param = $this->getSortParamDesc()) {
                if (isset($parameters[$param])) {
                    if ($this->has($parameters[$param])) {
                        $this->addSort(array($parameters[$param] => SORT_DESC));
                        $nosort = false;
                    }
                    unset($parameters[$param]);
                }
            }
            if ($param = $this->getSortParamAsc()) {
                if (isset($parameters[$param])) {
                    if ($nosort && $this->has($parameters[$param])) {
                        $this->addSort(array($parameters[$param] => SORT_ASC));
                    }
                    unset($parameters[$param]);
                }
            }

            // Check for the global TextSearchFilter and apply + remove from filter
            $filter = array();
            if ($this->hasTextSearchFilter() && ($param = $this->getTextFilter())) {
                if (isset($parameters[$param])) {
                    $filter[$param] = $parameters[$param];
                    unset($parameters[$param]);
                }
            }

            // Check for key => param name mappings and apply + remove from filter
            // e.g. ID => pkey of ID1 => pkey1, ID2 => pkey2
            if ($keys = $this->getKeys()) {
                foreach ($keys as $param => $field) {
                    if (isset($parameters[$param])) {
                        $filter[$field] = $parameters[$param];
                        unset($parameters[$param]);
                    }
                }
            }
            if (MUtil_Model::$verbose) {
                MUtil_Echo::r($parameters, 'Model parameters');
            }

            // Apply all other fields...
            foreach ($parameters as $param => $value) {
                // ... when they are fields in the model
                if ($this->has($param)) {
                    $filter[$param] = $value;
                }
            }

            if ($filter) {
                $this->addFilter($filter);
            }

            if (MUtil_Model::$verbose) {
                MUtil_Echo::r($filter, 'Model filter');
            }

        }

        return $parameters;
    }

    /**
     * Filters a request for use with applyParameters, including $_POST parameters.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param boolean $removePost Optional
     * @return MUtil_Model_ModelAbstract
     */
    public function applyPostRequest(Zend_Controller_Request_Abstract $request)
    {
        return $this->applyRequest($request, false);
    }

    /**
     * Filters a request for use with applyParameters.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param boolean $removePost Optional
     * @return MUtil_Model_ModelAbstract
     */
    public function applyRequest(Zend_Controller_Request_Abstract $request, $removePost = true)
    {
        $parameters = $request->getParams();

        // Remove MVC fields
        unset($parameters[$request->getModuleKey()], $parameters[$request->getControllerKey()], $parameters[$request->getActionKey()]);

        if ($removePost) {
            // Do not use POST fields for filter
            $parameters = array_diff_key($parameters, $_POST);
        }

        // Remove all empty values (but not arrays) from the filter
        $parameters = array_filter($parameters, function($i) { return is_array($i) || strlen($i); });

        $this->applyParameters($parameters);

        return $this;
    }

    public function del($name, $arrayOrKey1 = null, $key2 = null)
    {
        if (func_num_args() == 1) {
            unset($this->_model[$name], $this->_model_order[$name], $this->_model_used[$name]);
        } else {
            $args = func_get_args();
            array_shift($args);
            $args = MUtil_Ra::flatten($args);

            foreach ($args as $arg) {
                unset($this->_model[$name][$arg]);
            }
        }
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    abstract public function delete($filter = true);

    /**
     * Gets one or more values for a certain field name.
     *
     * Usage example, with these values set:
     * <code>
     * $this->set('field_x', 'label', 'label_x', 'size', 100, 'maxlength', 120, 'xyz', null);
     * </code>
     *
     * Retrieve one attribute:
     * <code>
     * $label = $this->get('field_x', 'label');
     * </code>
     * Returns the label 'label_x'
     *
     * Retrieve another attribute:
     * <code>
     * $label = $this->get('field_x', 'xyz');
     * </code>
     * Returns null
     *
     * Retrieve all attributes:
     * <code>
     * $fieldx = $this->get('field_x');
     * </code>
     * Returns array('label' => 'label_x', 'size' => 100, 'maxlength' => 120)
     * Note: null value 'xyz' is not returned.
     *
     * Two options for retrieving specific attributes:
     * <code>
     * $list = $this->get('field_x', 'label', 'size', 'xyz');
     * $list = $this->get('field_x' array('label', 'size', 'xyz'));
     * </code>
     * Both return array('label' => 'label_x', 'size' => 100)
     * Note: null value 'xyz' is not returned.
     *
     * @param string $name Field name
     * @param string|array|null $arrayOrKey1 Null or the name of a single attribute or an array of attribute names
     * @param string $key2 Optional a second attribute name.
     * @return mixed
     */
    public function get($name, $arrayOrKey1 = null, $key2 = null)
    {
        $args = func_get_args();
        $args = MUtil_Ra::args($args, 1);

        if ($this->_model_used) {
            $this->_model_used[$name] = $name;
        }

        switch (count($args)) {
            case 0:
                if (isset($this->_model[$name])) {
                    if ($alias = $this->getAlias($name)) {
                        return $this->_model[$name] + $this->get($alias);
                    } else {
                        return $this->_model[$name];
                    }
                } else {
                    return array();
                }

            case 1:
                return $this->_getKeyValue($name, reset($args));

            default:
                $results = array();
                foreach ($args as $key) {
                    $value = $this->_getKeyValue($name, $key);

                    if (null !== $value) {
                        $results[$key] = $value;
                    }
                }
                return $results;
        }
    }

    public function getAlias($name)
    {
        if (isset($this->_model[$name][self::ALIAS_OF])) {
            return $this->_model[$name][self::ALIAS_OF];
        }
    }

    public function getChanged()
    {
        return $this->_changedCount;
    }

    /**
     * Get an array of field names with the value of a certain attribute if set.
     *
     * Example:
     * <code>
     * $this->getCol('label');
     * </code>
     * returns an array of labels set with the field name as key.
     *
     * @param string $column_name Name of the attribute
     * @return array
     */
    public function getCol($column_name)
    {
        $results = array();

        foreach ($this->_model as $name => $row) {
            if ($this->has($name, $column_name)) {
                $results[$name] = $this->get($name, $column_name);
            }
        }

        return $results;
    }

    public function getFilter()
    {
        return $this->getMeta('filter', array());
    }

    public function getItemNames()
    {
        return array_keys($this->_model);
    }

    /**
     * Get an array of items using a previously set custom ordering
     *
     * When two items have the same order value, they both will be included in the resultset
     * but ordering is unpredictable. Fields without an explicitly set order value will be
     * added with increments of $this->orderIncrement (default = 10)
     *
     * Use <code>$this->set('fieldname', 'order', <value>);</code> to set a custom ordering.
     *
     * @see set()
     * @return array
     */
    public function getItemsOrdered()
    {
        $order = (array) $this->_model_order;
        asort($order);
        $result = array_keys($order);
        foreach($this->_model as $field => $element) {
            if (! array_key_exists($field, $order)) {
                $result[] = $field;
            }
        }
        return $result;
    }

    public function getItemsUsed()
    {
        if ($this->_model_used) {
            return $this->_model_used;
        } else {
            $names = array_keys($this->_model);
            return array_combine($names, $names);
        }
    }

    /**
     * Return an identifier the item specified by $forData
     *
     * basically transforms the fieldnames ointo oan IDn => value array
     *
     * @param mixed $forData Array value to vilter on
     * @param array $href Or ArrayObject
     * @return array That can by used as href
     */
    public function getKeyRef($forData, $href = array())
    {
        $keys = $this->getKeys();

        if (count($keys) == 1) {
            $key = reset($keys);
            if ($value = self::_getValueFrom($key, $forData)) {
                $href[MUtil_Model::REQUEST_ID] = $value;
            }
        } else {
            $i = 1;
            foreach ($keys as $key) {
                if ($value = self::_getValueFrom($key, $forData)) {
                    $href[MUtil_Model::REQUEST_ID . $i] = $value;
                }
            }
        }

        return $href;
    }

    /**
     * Returns an array containing the currently defined keys for this
     * model.
     *
     * When no keys are defined, the keys are derived from the model.
     *
     * @param boolean $reset If true, derives the key from the model.
     * @return array
     */
    public function getKeys($reset = false)
    {
        if ((! $this->_keys) || $reset) {
            $keys = array();
            foreach ($this->_model as $name => $info) {
                if (isset($info['key']) && $info['key']) {
                    $keys[] = $name;
                }
            }
            $this->setKeys($keys);
        }
        return $this->_keys;
    }

    public function getMeta($key, $default = null)
    {
        if (isset($this->_model_meta[$key])) {
            return $this->_model_meta[$key];
        }
        return $default;
    }

    public function getName()
    {
        return $this->_model_name;
    }

    /**
     * Checks for and executes any actions to perform on a value after
     * loading the value
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return mixed The value to save
     */
    public function getOnLoad($value, $new, $name, array $context = array())
    {
        if ($call = $this->get($name, self::LOAD_TRANSFORMER)) {

             if (is_callable($call)) {
                 $value = call_user_func($call, $value, $new, $name, $context);
             } else {
                 $value = $call;
             }
        }

        return $value;
    }

    /**
     * Checks for and executes any actions to perform on a value before
     * saving the value
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return mixed The value to save
     */
    public function getOnSave($value, $new, $name, array $context = array())
    {
        if ($call = $this->get($name, self::SAVE_TRANSFORMER)) {

            if (is_callable($call)) {
                $value = call_user_func($call, $value, $new, $name, $context);
            } else {
                $value = $call;
            }
        }

        return $value;
    }

    /**
     * Find out the order of the requested $name in the model
     *
     * @param string $name
     * @return int|null The order value of the requeste item or null if not defined
     */
    public function getOrder($name) {
        if (isset($this->_model_order[$name])) {
            return $this->_model_order[$name];
        }
    }

    public function getRequestSort(Zend_Controller_Request_Abstract $request, $ascParam = null, $descParam = null)
    {
        // DEPRECIATED

        if (null === $ascParam) {
            $ascParam = $this->getSortParamAsc();
        }
        if (null === $descParam) {
            $descParam = $this->getSortParamDesc();
        }

        if ($sortValue = $request->getParam($ascParam)) {
            if ($this->has($sortValue)) {
                return array($sortValue => SORT_ASC);
            }
        }

        if ($sortValue = $request->getParam($descParam)) {
            if ($this->has($sortValue)) {
                return array($sortValue => SORT_DESC);
            }
        }

        return array();
    }

    public function getSort()
    {
        return $this->getMeta('sort', array());
    }

    public function getSortParamAsc()
    {
        return $this->getMeta('sortParamAsc', MUtil_Model::SORT_ASC_PARAM);
    }

    public function getSortParamDesc()
    {
        return $this->getMeta('sortParamDesc', MUtil_Model::SORT_DESC_PARAM);
    }

    public function getTextFilter()
    {
        return $this->getMeta('textFilter', MUtil_Model::TEXT_FILTER);
    }

    /**
     * Splits a wildcard search text into its constituent parts.
     *
     * @param string $searchText
     * @return array
     */
    public function getTextSearches($searchText)
    {
        // Replace -/ with space, trim & remove all double spaces
        return explode(' ', str_replace('  ', ' ', trim(strtr($searchText, '-+/\\',  '    '))));
    }

    /**
     * Creates a filter for this model for the given wildcard search text.
     *
     * @param string $searchText
     * @return array An array of filter statements for wildcard text searching for this model type
     */
    public function getTextSearchFilter($searchText)
    { }

    public function has($name, $subkey = null)
    {
        if (null === $subkey) {
            return isset($this->_model[$name]);
        } else {
            return isset($this->_model[$name][$subkey]);
        }
    }

    public function hasFilter()
    {
        return $this->hasMeta('filter');
    }

    public function hasItemsUsed()
    {
        return (boolean) $this->_model_used;
    }

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    abstract public function hasNew();

    public function hasMeta($key)
    {
        return isset($this->_model_meta[$key]);
    }

    public function hasOnSave($name)
    {
        return $this->has($name, self::SAVE_TRANSFORMER);
    }

    public function hasSaveWhen($name)
    {
        return $this->has($name, self::SAVE_WHEN_TEST);
    }

    public function hasSort()
    {
        return $this->hasMeta('sort');
    }

    public function hasTextSearchFilter()
    {
        return false;
    }

    public function is($name, $key, $value)
    {
        return $value == $this->_getKeyValue($name, $key);
    }

    /**
     * Is the value of the field $name calculated automatically (returns true) or
     * only available when supplied in the data to be saved (returns false).
     *
     * @param string $name  The name of a field
     * @return boolean
     */
    public function isAutoSave($name)
    {
        return $this->_getKeyValue($name, self::AUTO_SAVE);
    }

    public function isMeta($key, $value)
    {
        return $this->getMeta($key) == $value;
    }

    /**
     * Must the model save field $name with this $value and / or this $new values.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean True if the data can be saved
     */
    public function isSaveable($value, $new, $name, array $context = array())
    {
        if ($test = $this->get($name, self::SAVE_WHEN_TEST)) {

             if (is_callable($test)) {
                 return call_user_func($test, $value, $new, $name, $context);
             }

             return $test;
        }

        return true;
    }

    public function isString($name)
    {
        if ($type = $this->get($name, 'type')) {
            return MUtil_Model::TYPE_STRING == $type;
        }

        return true;
    }

    /**
     * Returns a nested array containing the items requested.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return array Nested array or false
     */
    public function load($filter = true, $sort = true)
    {
        $data = $this->_load($filter, $sort);

        if (is_array($data) && $this->getMeta(self::LOAD_TRANSFORMER)) {
            foreach ($data as $key => $row) {
                $data[$key] = $this->_filterDataAfterLoad($row, false);
            }
        }

        return $data;
    }

    /**
     * Returns an array containing the first requested item.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filteloa
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return array An array or false
     */
    public function loadFirst($filter = true, $sort = true)
    {
        $data = $this->_load($filter, $sort);
        // Return the first row or null.
        $data = reset($data);
        if (is_array($data) && $this->getMeta(self::LOAD_TRANSFORMER)) {
            $data = $this->_filterDataAfterLoad($data, false);
        }
        return $data;
    }

    /**
     * Creates new items - in memory only.
     *
     * @param int $count When null a single new item is return, otherwise a nested array with $count new items
     * @return array Nested when $count is not null, otherwise just a simple array
     */
    public function loadNew($count = null)
    {
        $empty = array();
        foreach ($this->_model as $name => $mdata) {
            if (array_key_exists('default', $mdata)) {
                $empty[$name] = $mdata['default'];
            } else {
                $empty[$name] = null;
            }
        }
        $empty = $this->_filterDataAfterLoad($empty, true);

        // Return only a single row when no count is specified
        if (null === $count) {
            return $empty;
        }

        $empties = array();
        for ($i = 0; $i < $count; $i++) {
            $empties[] = $empty;
        }

        return $empties;
    }

    /**
     * Returns a Zend_Paginator for the items in the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return Zend_Paginator
     */
    public function loadPaginator($filter = true, $sort = true)
    {
        return Zend_Paginator::factory($this->load($filter, $sort));
    }

    /**
     * Returns a MUtil_Lazy_RepeatableInterface for the items in the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return MUtil_Lazy_RepeatableInterface
     */
    public function loadRepeatable($filter = true, $sort = true)
    {
        return MUtil_Lazy::repeat($this->load($filter, $sort));
    }


    /**
     * Remove one attribute for a field name in the model.
     *
     * Example:
     * <code>
     * $this->remove('field_x', 'label') ;
     * </code>
     * This will remove the label attribute from the field_x
     *
     * @param string $name The fieldname
     * @param string $key The name of the key
     * @return $this
     */
    public function remove($name, $key = null)
    {
        if (isset($this->_model[$name][$key])) unset($this->_model[$name][$key]);

        return $this;
    }

    /**
     * Reset the processing / display order for getItemsOrdered().
     *
     * Model items are displayed in the order they are first set() by the code.
     * Using this functions resets this list and allows you to start over
     * and determine the display order by the order you set() the items from
     * now on.
     *
     * @see getItemsOrdered()
     *
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function resetOrder()
    {
        $this->_model_order = null;
        return $this;
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    abstract public function save(array $newValues, array $filter = null);

    /**
     * Calls $this->save() multiple times for each element
     * in the input array and returns the number of saved rows.
     *
     * @param array $newValues A nested array
     * @return int The number of changed rows
     */
    public function saveAll(array $newValues)
    {
        $savedValues = array();

        foreach ($newValues as $key => $newValue) {
            if ($saved = $this->save($newValue)) {
                $savedValues[$key] = $saved;
            }
        }

        return $savedValues;
    }

    /**
     * Set one or more attributes for a field names in the model.
     *
     * Example:
     * <code>
     * $this->set('field_x', 'save', true) ;
     * $this->set('field_x', array('save' => true)) ;
     * </code>
     * Both set the attribute 'save' to true for 'field_x'.
     *
     * @param string $name The fieldname
     * @param string|array $arrayOrKey1 A key => value array or the name of the first key
     * @param mixed $value1 The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2 Optional second key when $arrayOrKey1 is a string
     * @param mixed $value2 Optional second value when $arrayOrKey1 is a string, an unlimited number of $key values pairs can be given.
     * @return $this
     */
    public function set($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $args = func_get_args();
        $args = MUtil_Ra::pairs($args, 1);

        foreach ($args as $key => $value) {
            // If $key end with ] it is array value
            if (substr($key, -1) == ']') {
                if (substr($key, -2) == '[]') {
                    // If $key ends with [], append it to array
                    $key    = substr($key, 0, -2);
                    $this->_model[$name][$key][] = $value;
                } else {
                    // Otherwise extract subkey
                    $pos    = strpos($key, '[');
                    $subkey = substr($key, $pos + 1, -1);
                    $key    = substr($key, 0, $pos);

                    $this->_model[$name][$key][$subkey] = $value;
                }
            } else {
                $this->_model[$name][$key] = $value;
            }
        }

        //Now set the order (repeat always, because order can be changed later on)
        if (isset($this->_model[$name]['order'])) {
            $order = $this->_model[$name]['order'];
        } elseif (isset($this->_model_order[$name]) && is_int($this->_model_order[$name])) {
            $order = $this->_model_order[$name];
        } else {
            $order = 0;
            if (is_array($this->_model_order)) {
                $order = max(array_values($this->_model_order));
            }
            $order += $this->orderIncrement;
        }
        $this->_model_order[$name] = $order;

        return $this;
    }

    public function setAlias($name, $alias)
    {
        if ($this->has($alias)) {
            $this->set($name, self::ALIAS_OF, $alias);
            return $this;
        }
        throw new MUtil_Model_ModelException("Alias for '$name' set to non existing field '$alias'");
    }

    /**
     * Is the value of the field $name calculated automatically (set to true) or
     * only available when supplied in the data to be saved (set to false).
     *
     * @param string $name  The name of a field
     * @param boolean $value
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setAutoSave($name, $value = true)
    {
        $this->set($name, self::AUTO_SAVE, $value);
        return $this;
    }

    protected function setChanged($changed = 0)
    {
        $this->_changedCount = $changed;

        return $this;
    }

    /**
     * Set attributes for all fields in the model.
     *
     * Example:
     * <code>
     * $this->setCol('save', true) ;
     * $this->setCol(array('save' => true)) ;
     * </code>
     * both set the attribute 'save' to true for all fields.
     *
     * @param string|array $arrayOrKey1 A key => value array or the name of the first key
     * @param mixed $value1 The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2 Optional second key when $arrayOrKey1 is a string
     * @param mixed $value2 Optional second value when $arrayOrKey1 is a string, an unlimited number of $key values pairs can be given.
     * @return $this
     */
    public function setCol($arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $args = func_get_args();
        $args = MUtil_Ra::pairs($args);

        foreach ($this->_model as $name => $row) {
            $this->set($name, $args);
        }

        return $this;
    }

    public function setFilter(array $filter)
    {
        return $this->setMeta('filter', $filter);
    }

    public function setIfExists($name, $arrayOrKey1, $value1 = null, $key2 = null, $value2 = null)
    {
        if ($this->has($name)) {
            $args = func_get_args();
            $args = MUtil_Ra::pairs($args, 1);

            $this->set($name, $args);

            return true;
        }

        return false;
    }

    /**
     * Sets the keys, processing the array key.
     *
     * When an array key is numeric MUtil_Model::REQUEST_ID is used.
     * When there is more than one key a increasing number is added to
     * MUtil_Model::REQUEST_ID starting with 1.
     *
     * String key names ar eleft as is.
     *
     * @param array $keys
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setKeys(array $keys)
    {
        $this->_keys = array();

        if (count($keys) == 1) {
            $name = reset($keys);
            if (is_numeric(key($keys))) {
                $this->_keys[MUtil_Model::REQUEST_ID] = $name;
            } else {
                $this->_keys[key($keys)] = $name;
            }
        } else {
            $i = 1;
            foreach ($keys as $idx => $name) {
                if (is_numeric($idx)) {
                    $this->_keys[MUtil_Model::REQUEST_ID . $i] = $name;
                    $i++;
                } else {
                    $this->_keys[$idx] = $name;
                }
            }
        }

        return $this;
    }

    public function setMeta($key, $value)
    {
        $this->_model_meta[$key] = $value;
        return $this;
    }

    /**
     * Set attributes for a specified array of field names in the model.
     *
     * Example:
     * <code>
     * $this->setMulti(array('field_x', 'field_y'), 'save', true) ;
     * $this->setMulti(array('field_x', 'field_y'), array('save' => true)) ;
     * </code>
     * both set the attribute 'save' to true for 'field_x' and 'field_y'.
     *
     * @param array $names An array of fieldnames
     * @param string|array $arrayOrKey1 A key => value array or the name of the first key
     * @param mixed $value1 The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2 Optional second key when $arrayOrKey1 is a string
     * @param mixed $value2 Optional second value when $arrayOrKey1 is a string, an unlimited number of $key values pairs can be given.
     * @return $this
     */
    public function setMulti(array $names, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $args = func_get_args();
        $args = MUtil_Ra::pairs($args, 1);

        foreach ($names as $name) {
            $this->set($name, $args);
        }

        return $this;
    }

    /**
     * Sets a name to automatically change a value after a load.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type: callable($value, $isNew = false, $name = null, array $context = array())
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setOnLoad($name, $callableOrConstant)
    {
        // Make sure we store that there is some OnLoad function.
        $this->setMeta(self::LOAD_TRANSFORMER, true);
        $this->set($name, self::LOAD_TRANSFORMER, $callableOrConstant);
        return $this;
    }

    /**
     * Sets a name to an automatically determined or changed of value before a save.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type: callable($value, $isNew = false, $name = null, array $context = array())
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setOnSave($name, $callableOrConstant)
    {
        $this->set($name, self::SAVE_TRANSFORMER, $callableOrConstant);
        return $this;
    }

    /**
     * Set this field to be saved whenever there is anything to save at all.
     *
     * @param string $name The fieldname
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setSaveOnChange($name)
    {
        $this->setAutoSave($name);
        return $this->setSaveWhen($name, true);
    }

    /**
     * Set this field to be saved whenever a constant is true or a callable returns true.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type: callable($value, $isNew = false, $name = null, array $context = array()) boolean
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setSaveWhen($name, $callableOrConstant)
    {
        $this->set($name, self::SAVE_WHEN_TEST, $callableOrConstant);
        return $this;
    }

    /**
     * Set this field to be saved only when it is a new item.
     *
     * @param string $name The fieldname
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setSaveWhenNew($name)
    {
        $this->setAutoSave($name);
        return $this->setSaveWhen($name, array(__CLASS__, 'whenNew'));
    }

    /**
     * Set this field to be saved only when it is not empty.
     *
     * @param string $name The fieldname
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setSaveWhenNotNull($name)
    {
        return $this->setSaveWhen($name, array(__CLASS__, 'whenNotNull'));
    }

    public function setSort($value)
    {
        return $this->setMeta('sort', $this->_checkSortValue($value));
    }

    public function setSortParamAsc($value)
    {
        return $this->setMeta('sortParamAsc', $value);
    }

    public function setSortParamDesc($value)
    {
        return $this->setMeta('sortParamDesc', $value);
    }

    public function setTextFilter($value)
    {
        return $this->setMeta('textFilter', $value);
    }

    public function trackUsage($value = true)
    {
        if ($value) {
            // Restarts the tracking
            $this->_model_used = $this->getKeys();
        } else {
            $this->_model_used = false;
        }
    }

    /**
     * A ModelAbstract->setSaveWhen() function that true when a new item is saved..
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public static function whenNew($value, $isNew = false, $name = null, array $context = array())
    {
        return $isNew;
    }

    /**
     * A ModelAbstract->setSaveWhen() function that true when the value
     * is not null.
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public static function whenNotNull($value, $isNew = false, $name = null, array $context = array())
    {
        return null !== $value;
    }
}
