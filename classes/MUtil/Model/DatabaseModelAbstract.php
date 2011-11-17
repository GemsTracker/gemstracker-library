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
 * Class contains standard helper functions for using models
 * that store information using Zend_Db_Adapter.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Model_DatabaseModelAbstract extends MUtil_Model_ModelAbstract
{
    /**
     * Default value for $keyKopier.
     *
     * As this is the name a hidden Zend_Element we can use only letters and the underscore for
     * the first character and letters, the underscore and numbers for the later characters.
     *
     * Zend_Element allows some other extended characters, but those may not work
     * with some browsers.
     */
    const KEY_COPIER = '__c_1_3_copy__%s__key_k_0_p_1__';
    // If there exists a table containing two fields that map to these, shoot the table designer!!!

    /**
     * @var array When specified delete() updates the selected rows with these values, instead of physically deleting the rows.
     */
    protected $_deleteValues;

    /**
     * Child classes may technically be able or not able to add extra rows,
     * but the data model or specific circumstances may require a specific
     * instance of that class to deviate from the default.
     *
     * @var boolean $canCreate True if the model can create new rows.
     */
    public $canCreate = true;

    /**
     * A standard rename scaffold for hidden kopies of primary key fields.
     *
     * As this is the name a hidden Zend_Element we can use only letters and the underscore for
     * the first character and letters, the underscore and numbers for the later characters.
     *
     * Zend_Element allows some other extended characters, but those may not work
     * with some browsers.
     *
     * @var string $keyKopier String into which the original keyname is sprintf()-ed.
     */
    public $keyCopier = self::KEY_COPIER;

    /**
     * Get a select statement using a filter and sort
     *
     * @param array $filter
     * @param array $sort
     * @return Zend_Db_Table_Select
     */
    protected function _createSelect($filter = null, $sort = null)
    {
        $select  = $this->getSelect();


        if ($this->hasItemsUsed()) {
            // Add expression columns by default
            // getColumn() triggers the columns as 'used'
            $this->getCol('column_expression');

            // Add each column to the select statement
            foreach ($this->getItemsUsed() as $name) {
                if ($expression = $this->get($name, 'column_expression')) {
                    $select->columns(array($name => $expression));
                } else {
                    if ($table = $this->get($name, 'table')) {
                        $select->columns($name, $table);
                    }
                }
            }
        } else {
            // Add only the columns, all other fields are returned already.
            foreach ($this->getCol('column_expression') as $name => $expression) {
                $select->columns(array($name => $expression));
            }
        }

        $adapter = $this->getAdapter();

        // Filter
        foreach ($this->_checkFilterUsed($filter) as $name => $value) {
            if (is_int($name)) {
                $select->where($value);
            } else {
                if ($expression = $this->get($name, 'column_expression')) {
                    //The brackets tell Zend_Db_Select that this is an epression in a sort.
                    $name = '(' . $expression . ')';
                } else {
                    $name = $adapter->quoteIdentifier($name);
                }
                if (null === $value) {
                    $select->where($name . ' IS NULL');
                } else {
                    $select->where($name . ' = ?', $value);
                }
            }
        }

        // Sort
        if ($sort = $this->_checkSortUsed($sort)) {
            foreach ($sort as $key => $order) {
                if (is_numeric($key) || is_string($order)) {
                    $sqlsort[] = $order;
                } else {
                    // Code not needed at least for MySQL, a named calculated column can be used in
                    // an ORDER BY. However, it does work.
                    /*
                    if ($expression = $this->get($key, 'column_expression')) {
                        //The brackets tell Zend_Db_Select that this is an epression in a sort.
                        $key = '(' . $expression . ')';
                    } // */
                    switch ($order) {
                        case SORT_ASC:
                            $sqlsort[] = $key . ' ASC';
                            break;
                        case SORT_DESC:
                            $sqlsort[] = $key . ' DESC';
                            break;
                        default:
                            $sqlsort[] = $order;
                            break;
                    }
                }
            }

            $select->order($sqlsort);
        }

        if (MUtil_Model::$verbose) {
            MUtil_Echo::pre($select, get_class($this) . ' select');
        }

        return $select;
    }

    /**
     * Helper function to delete data from a table.
     *
     * @param Zend_Db_Table_Abstract $table The table to delete from.
     * @param array $filter The filter for deleting. This is required to prevent deleting all data in a table.
     * @param array $deleteUpdates Does not do a real delete, but updates the database instead.
     * @return int The number of rows deleted / updated
     */
    protected function _deleteTableData(Zend_Db_Table_Abstract $table, array $filter, array $deleteUpdates = null)
    {
        if ($filter) {
            $adapter = $this->getAdapter();

            $wheres = array();
            foreach ($filter as $name => $value) {
                if (is_int($name)) {
                    $wheres[] = $value;
                } else {
                    $wheres[$adapter->quoteIdentifier($name) . ' = ?'] = $value;
                }
            }

            if ($deleteUpdates) {
                return $table->update($deleteUpdates, $wheres);
            } else {
                return $table->delete($wheres);
            }
        }

        return 0;
    }

    /**
     * Filters the list of values and returns only those that should be used for this table.
     *
     * @param string $table_name The current table
     * @param array $data All the data, including those for other tables
     * @param boolean $isNew True when creating
     * @return array An array containting the values that should be saved for this table.
     */
    protected function _filterDataFor($table_name, array $data, $isNew)
    {
        $tableData = array();

        foreach ($this->getItemNames() as $name) {

            // Is current table?
            if ($this->is($name, 'table', $table_name)) {
                if (array_key_exists($name, $data)) {

                    if ($data[$name] && ($len = $this->get($name, 'maxlength'))) {
                        $tableData[$name] = substr($data[$name], 0, $len);
                    } else {
                        $tableData[$name] = $data[$name];
                    }

                } elseif ($this->isAutoSave($name)) {
                    // Add a value for on auto save values
                    $tableData[$name] = null;
                }
            }
        }

        return $this->_filterDataForSave($tableData, $isNew);
    }

    protected function _getKeysFor($table_name)
    {
        $keys = array();

        foreach ($this->getItemNames() as $name) {
            if ($this->is($name, 'table', $table_name) && $this->get($name, 'key')) {
                $keys[] = $name;
            }
        }
        return $keys;
    }

    protected function _getTableName(Zend_Db_Table_Abstract $table)
    {
        return $table->info(Zend_Db_Table_Abstract::NAME);
    }

    /**
     * Returns a nested array containing the items requested.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return array Nested array or false
     */
    protected function _load($filter = true, $sort = true)
    {
        return $this->_createSelect($filter, $sort)->query(Zend_Db::FETCH_ASSOC)->fetchAll();
    }

    protected function _loadTableMetaData(Zend_Db_Table_Abstract $table)
    {
        $table_name = $this->_getTableName($table);

        // MUtil_Echo::r($table->info('metadata'));
        foreach ($table->info('metadata') as $field) {
            $name = $field['COLUMN_NAME'];
            $finfo = array('table' => $table_name);

            switch (strtolower($field['DATA_TYPE'])) {
                case 'date':
                    $finfo['type'] = MUtil_Model::TYPE_DATE;
                    $this->set($name, 'storageFormat', 'yyyy-MM-dd');
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    break;

                case 'datetime':
                case 'timestamp':
                    $finfo['type'] = MUtil_Model::TYPE_DATETIME;
                    $this->set($name, 'storageFormat', 'yyyy-MM-dd HH:mm:ss');
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    break;

                case 'time':
                    $finfo['type'] = MUtil_Model::TYPE_TIME;
                    $this->set($name, 'storageFormat', 'HH:mm:ss');
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    break;

                case 'int':
                case 'integer':
                case 'mediumint':
                case 'smallint':
                case 'tinyint':
                case 'bigint':
                case 'serial':
                case 'dec':
                case 'decimal':
                case 'double':
                case 'double precision':
                case 'fixed':
                case 'float':
                    $finfo['type'] = MUtil_Model::TYPE_NUMERIC;
                    break;

                default:
                    $finfo['type'] = MUtil_Model::TYPE_STRING;
                    break;
            }

            if ($field['LENGTH']) {
                $finfo['maxlength'] = $field['LENGTH'];
            }
            if ($field['PRECISION']) {
                $finfo['decimals'] = $field['PRECISION'];
            }
            $finfo['default'] = $field['DEFAULT'];
            $finfo['required'] = ! ($field['NULLABLE'] || $field['DEFAULT']);

            if ($field['PRIMARY']) {
                $finfo['key'] = true;
            }

            $this->set($name, $finfo);
        }
        $this->resetOrder();            //We don't want the newly added fields to mess up our order
    }

    /**
     * General utility function for saving a row in a table.
     *
     * This functions checks for prior existence of the row and switches
     * between insert and update as needed. Key updates can be handled through
     * passing the $oldKeys or by using copyKeys().
     *
     * @see copyKeys()
     *
     * @param Zend_Db_Table_Abstract $table The table to save
     * @param array $newValues The values to save, including those for other tables
     * @param array $oldKeys The original keys as they where before the changes
     * @return array The values for this table as they were updated
     */
    protected function _saveTableData(Zend_Db_Table_Abstract $table, array $newValues, array $oldKeys = null)
    {
        if ($newValues) {
            $table_name   = $this->_getTableName($table);
            $primaryKeys  = $this->_getKeysFor($table_name);
            $primaryCount = count($primaryKeys);
            $filter       = array();
            $update       = true;

            // MUtil_Echo::r($newValues, $table_name);

            foreach ($primaryKeys as $key) {
                if (array_key_exists($key, $newValues) && (0 == strlen($newValues[$key]))) {
                    // Never include null key values
                    unset($newValues[$key]);
                    if (MUtil_Model::$verbose) {
                        MUtil_Echo::r('Null key value: ' . $key, 'INSERT!!');
                    }

                    // Now we know we are not updating
                    $update = false;

                } elseif (isset($oldKeys[$key])) {
                    if (MUtil_Model::$verbose) {
                        MUtil_Echo::r($key . ' => ' . $oldKeys[$key], 'Old key');
                    }
                    $filter[$key . ' = ?'] = $oldKeys[$key];
                    // Key values left in $returnValues in case of partial key insert

                } else {
                    // Check for old key values being stored using copyKeys()
                    $copyKey = $this->getKeyCopyName($key);

                    if (isset($newValues[$copyKey])) {
                        $filter[$key . ' = ?'] = $newValues[$copyKey];
                        if (MUtil_Model::$verbose) {
                            MUtil_Echo::r($key . ' => ' . $newValues[$copyKey], 'Copy key');
                        }

                    } else {
                        $filter[$key . ' = ?'] = $newValues[$key];
                        if (MUtil_Model::$verbose) {
                            MUtil_Echo::r($key . ' => ' . $newValues[$key], 'Key');
                        }
                    }
                }
            }

            if ($update) {
                // MUtil_Echo::r($filter, 'Filter');

                // Retrieve the record from the database
                $oldValueSet = call_user_func_array(array($table, 'find'),  $filter);

                if ($oldValueSet->count()) {
                    $oldValues = $oldValueSet->current()->toArray();
                } else {
                    // MUtil_Echo::r('INSERT!!', 'Old not found');
                    // Apparently the record does not exist in the database
                    $update = false;
                }
            }

            // Check for actual values for this table to save.
            if ($returnValues = $this->_filterDataFor($table_name, $newValues, ! $update)) {
                if (MUtil_Model::$verbose) {
                    MUtil_Echo::r($returnValues, 'Return');
                }

                if ($update) {
                    // MUtil_Echo::r($filter);

                    // Check for actual changes
                    foreach ($oldValues as $name => $value) {

                        // The name is in the set being stored
                        if (array_key_exists($name, $returnValues)) {

                            // Detect change that is not auto update
                            if (! (($returnValues[$name] == $value) || $this->isAutoSave($name))) {
                                // MUtil_Echo::rs($name, $returnValues[$name], $value);
                                // MUtil_Echo::r($returnValues);

                                // Update the row
                                if ($changed = $table->update($returnValues, $filter)) {
                                    $this->addChanged($changed);
                                    return $this->_updateCopyKeys($primaryKeys, $returnValues);
                                }
                            }
                        }
                    }
                    // No changes were made, return empty array.
                    // The non-abstract child class should take care
                    // of returning the original values.

                } else {
                    // Perform insert
                    // MUtil_Echo::r($returnValues);
                    $newKeyValues = $table->insert($returnValues);
                    $this->addChanged();
                    // MUtil_Echo::rs($newKeyValues, $primaryKeys);

                    // Composite key returned.
                    if (is_array($newKeyValues)) {
                        foreach ($newKeyValues as $key => $value) {
                            $returnValues[$key] = $value;
                        }
                        return $this->_updateCopyKeys($primaryKeys, $returnValues);
                    } else {
                        // Single key returned
                        foreach ($primaryKeys as $key) {
                            // Fill the first empty value
                            if (! isset($returnValues[$key])) {
                                $returnValues[$key] = $newKeyValues;
                                return $this->_updateCopyKeys($primaryKeys, $returnValues);
                            }
                        }
                        // But if all the key values were already filled, make sure the new values are returned.
                        return $this->_updateCopyKeys($primaryKeys, $returnValues);
                    }
                }
            }
        }
        return array();
    }

    protected function _updateCopyKeys(array $primaryKeys, array $returnValues)
    {
        foreach ($primaryKeys as $name) {
            $copyKey = $this->getKeyCopyName($name);
            if ($this->has($copyKey)) {
                $returnValues[$copyKey] = $returnValues[$name];
            } else {
                // Either all keys have a copy key or none.
                break;
            }
        }

        return $returnValues;
    }

    /**
     * Adds a column to the model
     *
     * @param string|Zend_Db_Expr $column
     * @param string $columnName
     * @param string $orignalColumn
     * @return MUtil_Model_DatabaseModelAbstract Provides a fluent interface
     */
    public function addColumn($column, $columnName = null, $orignalColumn = null)
    {
        if (null === $columnName) {
            $columnName = strtr((string) $column, ' .,;:?!\'"()<=>-*+\\/&%^', '______________________');
        }
        if (is_string($column) && (strpos($column, ' ') !== false)) {
            $column = new Zend_Db_Expr($column);
        }
        if ($orignalColumn) {
            $settings = $this->setAlias($columnName, $orignalColumn);
        }

        $this->set($columnName, 'column_expression', $column);

        return $this;
    }

    /**
     * Adding DeleteValues means delete() updates the selected rows with these values, instead of physically deleting the rows.
     *
     * @param string|array $arrayOrField1 MUtil_Ra::pairs() arguments
     * @param mxied $value1
     * @param string $field2
     * @param mixed $key2
     * @return MUtil_Model_TableModel
     */
    public function addDeleteValues($arrayOrField1 = null, $value1 = null, $field2 = null, $key2 = null)
    {
        $args = MUtil_Ra::pairs(func_get_args());
        $this->_deleteValues = $args + $this->_deleteValues;
        return $this;
    }

    /**
     * Makes a copy for each key item in the model using $this->getKeyCopyName()
     * to create the new name.
     *
     * Call this function whenever the user is able to edit a key and the key is not
     * stored elsewhere (e.g. in a parameter). The save function using this value to
     * perform an update instead of an insert on a changed key.
     *
     * @param boolean $reset True if the key list should be rebuilt.
     * return MUtil_Model_DatabaseModelAbstract $this
     */
    public function copyKeys($reset = false)
    {
        foreach ($this->getKeys($reset) as $name) {
            $this->addColumn($name, $this->getKeyCopyName($name));
        }
        return $this;
    }

    /**
     * Creates a validator that checks that this value is used in no other
     * row in the table of the $name field, except that row itself.
     *
     * If $excludes is specified it is used to create db_fieldname => $_POST mappings.
     * When db_fieldname is numeric it is assumed both should be the same.
     *
     * If no $excludes the model creates a filter using the primary key of the table.
     *
     * @param string|array $name The name of a database table field in the model or an array of them belonging to the same table.
     * @param optional array $excludeFilter An array containing [num|db_fieldname] => $_POST mappings.
     * @return MUtil_Validate_Db_UniqueValue A validator.
     */
    public function createUniqueValidator($name, array $excludeFilter = null)
    {
        $names = $name;
        if (is_array($names)) {
            $name = reset($names);
        }

        if ($table_name = $this->get($name, 'table')) {
            $adapter    = $this->getAdapter();

            if (null === $excludeFilter) {
                $excludes = array();
                // Find the keys for the current table
                foreach ($this->_getKeysFor($table_name) as $current) {
                    $copyName = $this->getKeyCopyName($current);
                    if ($this->has($copyName)) {
                        // Get the original value that is stored in a separate field create by $this->copyKeys()
                        //
                        // This is required when the user
                        $excludes[$current] = $copyName;
                    } else {
                        $excludes[$current] = $current; // MUtil_Model::REQUEST_ID;
                    }
                }
            } else {
                $excludes = $excludeFilter;
            }
            // MUtil_Echo::r($excludes);

            if ($excludes) {
                return new MUtil_Validate_Db_UniqueValue($table_name, $names, $excludes, $adapter);
            }

            throw new MUtil_Model_ModelException("Cannot create UniqueValue validator as no keys were defined for table $table_name.");
        }

        throw new MUtil_Model_ModelException("Cannot create UniqueValue validator as no table was defined for field $name.");
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see Gems_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return Zend_Date
     */
    public function formatSaveDate($value, $isNew = false, $name = null, array $context = array())
    {
        if ($name && (! ((null === $value) || ($value instanceof Zend_Db_Expr)))) {
            if ($saveFormat = $this->get($name, 'storageFormat')) {

                if ($value instanceof Zend_Date) {
                    return $value->toString($saveFormat);

                } else {
                    $displayFormat = $this->get($name, 'dateFormat');

                    return MUtil_Date::format($value, $saveFormat, $displayFormat);
                }
            }
        }

        return $value;
    }

    /**
     * The database adapter used by the model.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    abstract public function getAdapter();

    public function getCreate()
    {
        return $this->canCreate;
    }

    /**
     * Returns the key copy name for a field.
     *
     * @param string $name
     * @return string
     */
    public function getKeyCopyName($name)
    {
        return sprintf($this->keyCopier, $name);
    }

    /**
     * The select object where we get the query from.
     *
     * @return Zend_Db_Table_Select
     */
    abstract public function getSelect();

    public function getTextSearchFilter($searchText)
    {
        $filter = array();

        if ($searchText) {
            $adapter  = $this->getAdapter();

            $fields = array();
            foreach ($this->getItemsUsed() as $name) {
                if ($this->get($name, 'label')) {
                    if ($expression = $this->get($name, 'column_expression')) {
                        $fields[$name] = $expression;
                    } else {
                        $fields[$name] = $adapter->quoteIdentifier($name);
                    }
                }
            }

            if ($fields) {
                foreach ($this->getTextSearches($searchText) as $searchOn) {
                    $wheres = array();
                    $search = trim($adapter->quote($searchOn), '\'');
                    foreach ($fields as $name => $sqlField) {
                        if ($options = $this->get($name, 'multiOptions')) {
                            foreach ($options as $key => $value) {
                                if (stripos($value, $searchOn) !== false) {
                                    if (null === $key) {
                                        $wheres[] = $sqlField . ' IS NULL';
                                    } else {
                                        $wheres[] = $sqlField . ' = ' . $adapter->quote($key);
                                    }
                                }
                            }
                        } elseif (is_numeric($searchOn) || $this->isString($name)) {
                            // Only for strings or all fields when numeric
                            $wheres[] = $sqlField . ' LIKE \'%' . $search . '%\'';
                        }
                    }
                    $filter[] = implode(' ' . Zend_Db_Select::SQL_OR . ' ', $wheres);
                }
            }
        }

        return $filter;
    }

    public function hasNew()
    {
        return $this->canCreate;
    }

    public function hasTextSearchFilter()
    {
        return true;
    }

    /**
     * Returns an array containing the first requested item.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return array An array or false
     */
    public function loadFirst($filter = true, $sort = true)
    {
        $select = $this->_createSelect($filter, $sort);
        $select->limit(1, 0);

        $data = $select->query(Zend_Db::FETCH_ASSOC)->fetch();
        if (is_array($data)) {
            $data = $this->_filterDataAfterLoad($data, false);
        }

        return $data;
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
        $select  = $this->_createSelect($filter, $sort);
        $adapter = new MUtil_Model_SelectModelPaginator($select, $this);

        return new Zend_Paginator($adapter);
    }

    /**
     * Helper function for SelectModelPaginator to process
     * setOnLoads.
     *
     * @see MUtil_Model_SelectModelPaginator
     *
     * @param array $data Nested array
     * @return array Nested
     */
    public function processAfterLoad(array $data)
    {
        if ($this->getMeta(parent::LOAD_TRANSFORMER)) {
            foreach ($data as $key => $row) {
                $data[$key] = $this->_filterDataAfterLoad($row, false);
            }
        }

        return $data;
    }

    // abstract public function save(array $newValues);

    public function setCreate($value = true)
    {
        $this->canCreate = (bool) $value;
        return $this;
    }

    /**
     * Setting DeleteValues means delete() updates the selected rows with these values, instead of physically deleting the rows.
     *
     * @param string|array $arrayOrField1 MUtil_Ra::pairs() arguments
     * @param mxied $value1
     * @param string $field2
     * @param mixed $key2
     * @return MUtil_Model_TableModel
     */
    public function setDeleteValues($arrayOrField1 = null, $value1 = null, $field2 = null, $key2 = null)
    {
        $args = MUtil_Ra::pairs(func_get_args());
        $this->_deleteValues = $args;
        return $this;
    }

    public function setKeysToTable($keysOrTableName)
    {
        if (is_string($keysOrTableName)) {
            $keys = $this->_getKeysFor($keysOrTableName);
        } else {
            $keys = $keysOrTableName;
        }
        $this->setKeys($keys);
    }

    public function setKeyCopier($value = self::KEY_COPIER)
    {
        $this->keyCopier = $value;
        return $this;
    }
}
