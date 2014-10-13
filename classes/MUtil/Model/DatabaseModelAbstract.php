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
     *
     * If there exists a table containing two fields where one fields maps with this key
     * to the other, shoot the table designer!!!
     */
    const KEY_COPIER = '__c_1_3_copy__%s__key_k_0_p_1__';

    /**
     * Default save mode: execute all saves
     */
    const SAVE_MODE_ALL    = 7;

    /**
     * Allow deletes to be executed
     */
    const SAVE_MODE_DELETE = 4;

    /**
     * Allow inserts to be executed
     */
    const SAVE_MODE_INSERT = 2;

    /**
     * Allow updates to be executed
     */
    const SAVE_MODE_UPDATE = 1;

    /**
     * Do nothing
     */
    const SAVE_MODE_NONE   = 0;
    /**
     * Name for query filter transformers
     */
    const TEXTFILTER_TRANSFORMER = 'filter_transformer';

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
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return Zend_Db_Table_Select
     */
    protected function _createSelect(array $filter, array $sort)
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
        foreach ($filter as $name => $value) {
            if (is_int($name)) {
                $select->where($value);
            } else {
                if ($expression = $this->get($name, 'column_expression')) {
                    //The brackets tell Zend_Db_Select that this is an epression in a sort.
                    $name = '(' . $expression . ')';
                } elseif ('limit' === strtolower($name)) {
                    if (is_array($value)) {
                        $count  = array_shift($value);
                        $offset = reset($value);
                    } else {
                        $count  = $value;
                        $offset = null;
                    }
                    $select->limit($count, $offset);
                    continue;

                } else {
                    $name = $adapter->quoteIdentifier($name);
                }
                if (null === $value) {
                    $select->where($name . ' IS NULL');
                } elseif (is_array($value)) {
                    if ($value) {
                        $select->where($name . ' IN (' . $adapter->quote($value) . ')');
                    } else {
                        // Never a result when a value should be one of an empty set.
                        $select->where('1=0');
                    }
                } else {
                    $select->where($name . ' = ?', $value);
                }
            }
        }

        // Sort
        foreach ($sort as $key => $order) {
            if (is_numeric($key) || is_string($order)) {
                if ($this->has($order)) {
                    $sqlsort[] = $order;
                }
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
                        if ($this->has($key)) {
                            $sqlsort[] = $key . ' ASC';
                        }
                        break;
                    case SORT_DESC:
                        if ($this->has($key)) {
                            $sqlsort[] = $key . ' DESC';
                        }
                        break;
                    default:
                        if ($this->has($order)) {
                            $sqlsort[] = $order;
                        }
                        break;
                }
            }
        }

        if (isset($sqlsort)) {
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
        $tableCols = array();

        // First check the data
        foreach ($this->getCol('table') as $name => $table) {
            // Is current table?
            if ($table === $table_name) {

                if (array_key_exists($name, $data)) {
                    if ($data[$name] && (! is_array($data[$name])) && ($len = $this->get($name, 'maxlength'))) {
                        $data[$name] = substr($data[$name], 0, $len);
                    }

                } elseif ($this->isAutoSave($name)) {
                    // Add a value for on auto save values
                    $data[$name] = null;
                }

                $tableCols[$name] = $name;
            }
        }
        $data = $this->_filterDataForSave($data, $isNew);

        // MUtil_Echo::track(array_intersect_key($data, $tableCols));
        return array_intersect_key($data, $tableCols);
    }

    /**
     *
     * @param string $table_name  Does not test for existence
     * @return array Numeric array containing the key field names.
     */
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
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return array Nested array or false
     */
    protected function _load(array $filter, array $sort)
    {
        return $this->_createSelect($filter, $sort)->query(Zend_Db::FETCH_ASSOC)->fetchAll();
    }

    /**
     * Returns an array containing the first requested item.
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return array Nested array or false
     */
    protected function _loadFirst(array $filter, array $sort)
    {
        $select = $this->_createSelect($filter, $sort);
        $select->limit(1, 0);

        $data = $select->query(Zend_Db::FETCH_ASSOC)->fetch();

        return $data;
    }

    /**
     * Extract all info about the fields in the table and set them for this model
     *
     * @param Zend_Db_Table_Abstract $table
     */
    protected function _loadTableMetaData(Zend_Db_Table_Abstract $table)
    {
        $table_name = $this->_getTableName($table);

        // MUtil_Echo::track($table->info('metadata'));
        foreach ($table->info('metadata') as $field) {
            $name = $field['COLUMN_NAME'];
            $finfo = array('table' => $table_name);

            switch (strtolower($field['DATA_TYPE'])) {
                case 'date':
                    $finfo['type'] = MUtil_Model::TYPE_DATE;
                    $this->set($name, 'storageFormat', 'yyyy-MM-dd');
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    $this->setOnLoad($name, array($this, 'formatLoadDate'));
                    break;

                case 'datetime':
                case 'timestamp':
                    $finfo['type'] = MUtil_Model::TYPE_DATETIME;
                    $this->set($name, 'storageFormat', 'yyyy-MM-dd HH:mm:ss');
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    $this->setOnLoad($name, array($this, 'formatLoadDate'));
                    break;

                case 'time':
                    $finfo['type'] = MUtil_Model::TYPE_TIME;
                    $this->set($name, 'storageFormat', 'HH:mm:ss');
                    $this->setOnSave($name, array($this, 'formatSaveDate'));
                    $this->setOnLoad($name, array($this, 'formatLoadDate'));
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
            switch (strtoupper($field['DEFAULT'])) {
                case 'CURRENT_DATE':
                case 'CURRENT_TIME':
                case 'CURRENT_TIMESTAMP':
                    $finfo['default'] = new Zend_Db_Expr($field['DEFAULT']);
                    break;
                case 'NULL':
                    break;

                default:
                    $finfo['default'] = $field['DEFAULT'];
            }
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
     * @param array  $newValues The values to save, including those for other tables
     * @param array  $oldKeys The original keys as they where before the changes
     * @param int    $saveMode Should updates / inserts occur
     * @return array The values for this table as they were updated
     */
    protected function _saveTableData(Zend_Db_Table_Abstract $table, array $newValues,
            array $oldKeys = null, $saveMode = self::SAVE_MODE_ALL)
    {
        if (! $newValues) {
            return array();
        }

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

                } elseif (isset($newValues[$key])) {
                    $filter[$key . ' = ?'] = $newValues[$key];
                    if (MUtil_Model::$verbose) {
                        MUtil_Echo::r($key . ' => ' . $newValues[$key], 'Key');
                    }
                }
            }
        }
        if (! $filter) {
            $update = false;
        }

        if ($update) {
            // MUtil_Echo::r($filter, 'Filter');

            $adapter = $this->getAdapter();
            $wheres   = array();
            foreach ($filter as $text => $value) {
                $wheres[] = $adapter->quoteInto($text, $value);
            }
            // Retrieve the record from the database
            $oldValues = $table->fetchRow('(' . implode(' ) AND (', $wheres) . ')');
            if (! $oldValues) {
                // MUtil_Echo::r('INSERT!!', 'Old not found');
                // Apparently the record does not exist in the database
                $update = false;
            } else {
                $oldValues = $oldValues->toArray();
            }
        }

        // Check for actual values for this table to save.
        // MUtil_Echo::track($newValues);
        if ($returnValues = $this->_filterDataFor($table_name, $newValues, ! $update)) {
            if (MUtil_Model::$verbose) {
                MUtil_Echo::r($returnValues, 'Return');
            }
            // MUtil_Echo::track($returnValues);

            if ($update) {
                // MUtil_Echo::r($filter);

                // Check for actual changes
                foreach ($oldValues as $name => $value) {

                    // The name is in the set being stored
                    if (array_key_exists($name, $returnValues)) {

                        if ($this->isAutoSave($name)) {
                            continue;
                        }

                        if (is_object($returnValues[$name]) || is_object($value)) {
                            $noChange = $returnValues[$name] == $value;
                        } else {
                            // Make sure differences such as extra start zero's on text fields do
                            // not disappear, while preventing a difference between an integer
                            // and string input of triggering a false change
                            $noChange = ($returnValues[$name] == $value) &&
                                    (strlen($returnValues[$name]) == strlen($value));
                        }

                        // Detect change that is not auto update
                        if (! $noChange) {
                            // MUtil_Echo::track($name, $returnValues[$name], $value);
                            // MUtil_Echo::track($returnValues);

                            // Update the row, if the saveMode allows it
                            if (($saveMode & self::SAVE_MODE_UPDATE) &&
                                    $changed = $table->update($returnValues, $filter)) {
                                $this->addChanged($changed);
                                // Make sure the copy keys (if any) have the new values as well
                                $returnValues = $this->_updateCopyKeys($primaryKeys, $returnValues);

                                // Add the old values as we have them and they may be of use later on.
                                $returnValues = $returnValues + $oldValues;

                                return $returnValues;
                            }
                        }
                    }
                }
                // Add the old values as we have them and they may be of use later on.
                return $returnValues + $oldValues;

            } elseif ($saveMode & self::SAVE_MODE_INSERT) {
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
        if (is_string($column) && ((strpos($column, ' ') !== false) || (strpos($column, '(') !== false))) {
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

            throw new MUtil_Model_ModelException(
                    "Cannot create UniqueValue validator as no keys were defined for table $table_name."
                    );
        }

        throw new MUtil_Model_ModelException(
                "Cannot create UniqueValue validator as no table was defined for field $name."
                );
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a Zend_Date format
     *
     * If empty or Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return MUtil_Date|Zend_Db_Expr|string
     */
    public function formatLoadDate($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        // If not empty or zend_db_expression and not already a zend date, we
        // transform to a Zend_Date using the ISO_8601 format
        if (empty($value) || $value instanceof Zend_Date || $value instanceof Zend_Db_Expr) {
            return $value;
        }

        $formats = array('storageFormat');
        if ($isPost) {
            // Formbridge uses a different format... quick & dirty fix is to duplicate that here:
            $formats['time'] = 'time';
            $formats['datetime'] = 'datetime';
            $formats['date'] ='date';

            // When posting try fixed formats first
            $formats = array_reverse($formats);
        }

        foreach ($formats as $key => $formatName)
        {
            if (is_numeric($key)) {
                // Get from the model
                $format = $this->_getKeyValue($name, $formatName);
            } else {
                // We use the fixed format
                $format = MUtil_Model_Bridge_FormBridge::getFixedOption($key, 'dateFormat');
            }

            if ($format) {
                try {
                    $date = new MUtil_Date($value, $format);
                    // When string representation of the created date is the same as the input we accept it
                    // This helps fix reversed day/month problems where month 13 will be 1st month of the next year
                    if ($date->get($format) === $value) {
                        return $date;
                    }
                } catch (Exception $exc) {
                    // Silently fail and try next one
                }
            }
        }

        try {
            // Last try
            $tmpDate = new MUtil_Date($value, Zend_Date::ISO_8601);

        } catch (Exception $exc) {
            // On failure, we use the input value
            $tmpDate = $value;
        }

        return $tmpDate;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return Zend_Date
     */
    public function formatSaveDate($value, $isNew = false, $name = null, array $context = array())
    {
        if ($name &&
                (! ((null === $value) ||
                        ($value instanceof Zend_Db_Expr) ||
                        MUtil_String::startsWith($value, 'current_', true))
                )) {
            $saveFormat = $this->getWithDefault($name, 'storageFormat', Zend_Date::ISO_8601);

            if ($value instanceof Zend_Date) {
                return $value->toString($saveFormat);

            } else {
                $displayFormat = $this->get($name, 'dateFormat');

                try {
                    return MUtil_Date::format($value, $saveFormat, $displayFormat);
                } catch (Zend_Exception $e) {
                    if (Zend_Date::isDate($value, $saveFormat)) {
                        return $value;
                    }
                    throw $e;
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
     * Creates an SQL filter for this value on this name.
     *
     * @param mixed $filter The value to filter for
     * @param string $name The name of the current field
     * @param string $sqlField The SQL name of the current field
     * @return mixed Nothing, a single filter statement or an array of OR filters
     */
    public function getOnTextFilter($filter, $name, $sqlField)
    {
        if ($call = $this->get($name, self::TEXTFILTER_TRANSFORMER)) {

            if (is_callable($call)) {
                return call_user_func($call, $filter, $name, $sqlField, $this);
            } else {
                return $call;
            }
        }

        if ($options = $this->get($name, 'multiOptions')) {
            $adapter = $this->getAdapter();
            $wheres = array();
            foreach ($options as $key => $value) {
                // MUtil_Echo::track($key, $value, $filter, stripos($value, $filter));
                if (stripos($value, $filter) !== false) {
                    if (null === $key) {
                        $wheres[1] = $sqlField . ' IS NULL';
                    } else {
                        $wheres[0][] = $adapter->quote($key);
                    }
                }
            }
            if (isset($wheres[0])) {
                if (count($wheres[0]) == 1) {
                    $wheres[0] = $sqlField . ' = ' . $wheres[0][0];
                } else {
                    $wheres[0] = $sqlField . ' IN (' . implode(', ', $wheres[0]) . ')';
                }
            }
            return $wheres;
        }

        if (is_numeric($filter) || $this->isString($name)) {
            // Only for strings or all fields when numeric
            return $sqlField . ' LIKE \'%' . trim($this->getAdapter()->quote($filter), '\'') . '%\'';
        }
    }

    /**
     * The select object where we get the query from.
     *
     * @return Zend_Db_Select
     */
    abstract public function getSelect();

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
            $adapter  = $this->getAdapter();

            $fields = array();
            foreach ($this->getItemsUsed() as $name) {
                if ($this->get($name, 'label') && (! $this->get($name, 'no_text_search'))) {
                    if ($expression = $this->get($name, 'column_expression')) {
                        if ($fieldList = $this->get($name, 'fieldlist')) {
                            foreach ((array) $fieldList as $field) {
                                $fields[$field] = $adapter->quoteIdentifier($field);
                            }
                        } else {
                            $fields[$name] = $expression;
                        }
                    } else {
                        $fields[$name] = $adapter->quoteIdentifier($name);
                    }
                }
            }

            if ($fields) {
                foreach ($this->getTextSearches($searchText) as $searchOn) {
                    $wheres = array();
                    foreach ($fields as $name => $sqlField) {
                        if ($where = $this->getOnTextFilter($searchOn, $name, $sqlField)) {
                            if (is_array($where)) {
                                $wheres = array_merge($wheres, $where);
                            } else {
                                $wheres[] = $where;
                            }
                        }
                    }

                    if ($wheres) {
                        $filter[] = implode(' ' . Zend_Db_Select::SQL_OR . ' ', $wheres);
                    } else {
                        // When all fields are multiOption fields that do not result in a
                        // filter, then there is no existing filter and the result set
                        // should always be empty.
                        $filter[] = '1=0';
                    }
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
     * Returns a Traversable spewing out arrays containing the items requested.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @return Traversable
     */
    public function loadIterator($filter = true, $sort = true)
    {
        $iter = new MUtil_Db_Iterator_SelectIterator($this->_createSelect(
                $this->_checkFilterUsed($filter),
                $this->_checkSortUsed($sort)
                ));

        if ($iter) {
            $data = $this->processAfterLoad($iter);
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
        $select  = $this->_createSelect(
                $this->_checkFilterUsed($filter),
                $this->_checkSortUsed($sort)
                );
        $adapter = new MUtil_Model_SelectModelPaginator($select, $this);

        return new Zend_Paginator($adapter);
    }

    // abstract public function save(array $newValues);

    /**
     * Function to turn database insertion on or off for this model.
     *
     * @param boolean $value
     * @return MUtil_Model_DatabaseModelAbstract (continuation pattern)
     */
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
     * @return MUtil_Model_DatabaseModelAbstract (continuation pattern)
     */
    public function setDeleteValues($arrayOrField1 = null, $value1 = null, $field2 = null, $key2 = null)
    {
        $args = MUtil_Ra::pairs(func_get_args());
        $this->_deleteValues = $args;
        return $this;
    }

    /**
     * Changes the key copy string that is used to create a new identifier
     * for keys.
     *
     * @param string $value A sting of at least 3 characters containing %s.
     * @return MUtil_Model_DatabaseModelAbstract (continuation pattern)
     */
    public function setKeyCopier($value = self::KEY_COPIER)
    {
        $this->keyCopier = $value;
        return $this;
    }

    /**
     * When passed an array this method set the keys of this database object
     * to those keys.
     * When passed a string it is assumed to be a table name and the keys of
     * this object are set to those of that table.
     *
     * @param mixed $keysOrTableName array or string
     * @return MUtil_Model_DatabaseModelAbstract (continuation pattern)
     */
    public function setKeysToTable($keysOrTableName)
    {
        if (is_string($keysOrTableName)) {
            $keys = $this->_getKeysFor($keysOrTableName);
        } else {
            $keys = $keysOrTableName;
        }
        $this->setKeys($keys);

        return $this;
    }

    /**
     * Sets a name to a callable function for query filtering.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type: callable($filter, $name, $sqlField, MUtil_Model_DatabaseModelAbstract $model)
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public function setOnTextFilter($name, $callableOrConstant)
    {
        $this->set($name, self::TEXTFILTER_TRANSFORMER, $callableOrConstant);
        return $this;
    }
}
