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
 *    * Neither the name of the <organization> nor the
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
 * JoinModel is a model that allows requesting, editing, inserting and
 * deleting over multiple tables.
 *
 * You can specify per table wether the contents should be updated,
 * but you can override this when calling save() and delete().
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Model_JoinModel extends \MUtil_Model_DatabaseModelAbstract
{
    protected $_joinFields = array();
    protected $_saveTables;

    private $_select;
    private $_tables;

    /**
     * Create a model that joins two or more tables
     *
     * @param string $name       A name for the model
     * @param string $startTable The base table for the model
     * @param mixed  $saveable   Will changes to this table be saved, true or a combination of SAVE_MODE constants
     */
    public function __construct($name, $startTable, $saveable = false)
    {
        parent::__construct($name);

        $alias = $this->_loadTable($startTable, $saveable);
        $table = $this->_tables[$alias];

        // Fix primary keys to those of the current table.
        $this->getKeys();

        $this->_select = new \Zend_Db_Select($table->getAdapter());
        $this->_select->from(array($alias => $this->_getTableName($table)), array());
    }

    /**
     * Check the passed saveTable information and return 'new style' SAVE_MODE
     * constant array
     *
     * @param array $saveTables Optional array containing the table names to save,
     * otherwise the tables set to save at model level will be saved.
     * @return array Containing savetable data
     */
    protected function _checkSaveTables($saveTables)
    {
        if (null === $saveTables) {
            return $this->_saveTables;
        }

        $results = array();
        foreach ((array) $saveTables as $tableName => $setting) {
            if (is_numeric($tableName) || (true === $setting)) {
                $results[$setting] = self::SAVE_MODE_ALL;
            } elseif ($setting) {
                $results[$tableName] = $setting;
            }
        }

        return $results;
    }

    /**
     *
     * @param string $alias
     * @return \Zend_Db_Table_Abstract
     */
    protected function _getTable($alias)
    {
        if (isset($this->_tables[$alias])) {
            return $this->_tables[$alias];
        }
    }

    /**
     * Join a table to the select statement and load the table information
     *
     * @param string $join      Join function name specifying the type of join
     * @param mixed $table      The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array $joinFields Array of field pairs that form the join statement
     * @param mixed $saveable   Will changes to this table be saved, true or a combination of SAVE_MODE constants
     */
    protected function _joinTable($join, $table, array $joinFields, $saveable = false)
    {
        $alias     = $this->_loadTable($table, $saveable);
        $table     = $this->_tables[$alias];
        $tableName = $this->_getTableName($table);
        $adapter   = $table->getAdapter();

        $joinSql = array();
        foreach ($joinFields as $source => $target) {
            if (is_numeric($source)) {
                // A join expression other than equality is used
                $joinSql[] = $target;
            } else {
                $this->_joinFields[$source] = $target;
                $joinSql[] = $adapter->quoteIdentifier($source) . ' = ' . $adapter->quoteIdentifier($target);
            }
        }
        if ($tableName != $alias) {
            $tableName = array($alias => $tableName);
        }

        $this->_select->$join($tableName, implode(' ' . \Zend_Db_Select::SQL_AND . ' ', $joinSql), array());
    }

    /**
     * Load table meta data and set the models table properties
     *
     * @param mixed $table    The name of the table to join or a table object
     * @param mixed $saveable Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @return string Table alias
     */
    protected function _loadTable($table, $saveable = false)
    {
        $alias = null;
        if ($table instanceof \Zend_Db_Table_Abstract) {
            $tableName = $this->_getTableName($table);
        } else {
            if (is_array($table)) {
                $tableName = reset($table);

                // Alias compatible with select
                $alias     = key($table);
                if (is_int($alias)) {
                    $alias = end($table);
                }
            } else {
                $tableName = (string) $table;
            }
            $table = new \Zend_DB_Table($tableName);
        }
        if (! $alias) {
            $alias = $tableName;
        }
        if (isset($this->_tables[$alias])) {
            for ($i = 2; isset($this->_tables[$alias . '_' . $i]); $i++);

            $alias = $alias . '_' . $i;
        }
        $this->_tables[$alias] = $table;

        $this->_setTableSaveable($alias, $saveable);
        $this->_loadTableMetaData($table, $alias);

        return $alias;
    }


    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @param array $saveTables Optional array containing the table names to save,
     * otherwise the tables set to save at model level will be saved.
     * @return array The values as they are after saving (they may change).
     */
    protected function _save(array $newValues, array $filter = null, array $saveTables = null)
    {
        $saveTables = $this->_checkSaveTables($saveTables);
        $oldChanged = $this->getChanged();

        // \MUtil_Echo::track($newValues, $filter, $saveTables, $this->_joinFields);

        $oldValues = $newValues;
        foreach ($saveTables as $tableName => $saveMode) {
            // Gotta repeat this every time, as keys may be set later
            foreach ($this->_joinFields as $source => $target) {
                // Use is_string as $target and $target can be e.g. a \Zend_Db_Expr() object
                // as $source is an index keys it must be a string
                if (is_string($target)) {
                    if (! (isset($newValues[$target]) && $newValues[$target])) {
                        if (! (isset($newValues[$source]) && $newValues[$source])) {
                            if (\MUtil_Model::$verbose) {
                                \MUtil_Echo::r('Missing: ' . $source . ' -> ' . $target, 'ERROR!');
                            }
                            continue;
                        }
                        $newValues[$target] = $newValues[$source];

                    } elseif (! (isset($newValues[$source]) && $newValues[$source])) {
                        $newValues[$source] = $newValues[$target];

                    } elseif ((strlen($newValues[$target]) > 0) &&
                            (strlen($newValues[$source]) > 0) &&
                            $newValues[$target] != $newValues[$source]) {
                        // Join key values changed.
                        //
                        // Set the old values as the filter
                        $filter[$target] = $newValues[$target];
                        $filter[$source] = $newValues[$source];

                        // Switch the target value to the value in the source field.
                        //
                        // JOIN FIELD ORDER IS IMPORTANT!!!
                        // The changing field must be stated first in the join statement.
                        $newValues[$target] = $newValues[$source];
                    }
                } elseif ($target instanceof \Zend_Db_Expr &&
                        (! (isset($newValues[$source]) && $newValues[$source]))) {
                    $newValues[$source] = $target;
                }
            }

            //$this->_saveTableData returns the new row values, including any automatic changes.
            $newValues = $this->_saveTableData($this->_tables[$tableName], $newValues, $filter, $saveMode)
                    + $oldValues;
            // \MUtil_Echo::track($oldValues, $newValues, $filter);
            $oldValues = $newValues;
        }

        // If anything has changed, it counts as only one item for the user.
        if ($this->getChanged() > $oldChanged) {
            $this->setChanged(++$oldChanged);
        }

        return $newValues;
    }

    /**
     * Add the table to the default save tables.
     *
     * This private functions saves against overloading
     *
     * Only tables marked as save tables are saved during a save() or delete(),
     * unless this is overuled by the extra parameter for those functions in
     * this object.
     *
     * @param string $tableName     Does not test for existence
     * @param mixed  $saveable      Will changes to this table be saved, true or a combination of SAVE_MODE constants
     */
    private function _setTableSaveable($tableName, $saveable)
    {
        if (true === $saveable) {
            $this->_saveTables[$tableName] = self::SAVE_MODE_ALL;
        } elseif ($saveable) {
            $this->_saveTables[$tableName] = $saveable;
        } else {
            unset($this->_saveTables[$tableName]);
        }
    }

    /**
     * Add a table to the model with a left join
     *
     * @param mixed $table      The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array $joinFields Array of source->dest primary keys for this join
     * @param mixed $saveable   Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @return \MUtil_Model_JoinModel
     */
    public function addLeftTable($table, array $joinFields, $saveable = false)
    {
        $this->_joinTable('joinLeft', $table, $joinFields, $saveable);
        return $this;
    }

    /**
     * Add a table to the model with a right join
     *
     * @param mixed $table      The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array $joinFields Array of source->dest primary keys for this join
     * @param mixed $saveable   Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \MUtil_Model_JoinModel
     */
    public function addRightTable($table, array $joinFields, $saveable = false)
    {
        $this->_joinTable('joinRight', $table, $joinFields, $saveable);
        return $this;
    }

    /**
     * Add a table to the model with an inner join
     *
     * @param mixed $table      The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array $joinFields Array of source->dest primary keys for this join
     * @param mixed $saveable   Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems_Model_JoinModel
     */
    public function addTable($table, array $joinFields, $saveable = false)
    {
        $this->_joinTable('joinInner', $table, $joinFields, $saveable);
        return $this;
    }

    /**
     * Delete items from the model
     *
     * The filter is propagated using over $this->_joinFields.
     *
     * Table rows are only deleted when there exists a value in the filter for
     * ALL KEY FIELDS of that table. In other words: a partial key is not enough
     * to actually delete an item.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true, array $saveTables = null)
    {
        $saveTables = $this->_checkSaveTables($saveTables);
        $filter     = $this->_checkFilterUsed($filter);

        if ($this->_deleteValues) {
            // First get the old values so we can have all the key values
            $oldValues = $this->loadFirst($filter);

            // Add the oldValues to the save
            $changed = $this->save($this->_deleteValues + $oldValues, $filter, $saveTables);
        } else {
            $changed = 0;
            foreach ($saveTables as $tableName => $saveMode) {
                $table_filter = array();
                $delete       = $saveMode & self::SAVE_MODE_DELETE;

                // Find per table key filters
                foreach ($this->_getKeysFor($tableName) as $key) {
                    if (isset($filter[$key])) {
                        $table_filter[$key] = $filter[$key];
                    } else {
                        // If key values are missing, do not delete.
                        $delete = false;
                        foreach ($this->_joinFields as $source => $target) {
                            $found = null;

                            if ($source === $key) {
                                $found = $target;
                            } elseif ($target == $key) {
                                $found = $source;
                            }
                            if ($found && isset($filter[$found])) {
                                /// Found after all.
                                $delete = true;
                                $table_filter[$key] = $filter[$found];
                                break;
                            }
                        }
                    }
                }

                // \MUtil_Echo::r($table_filter, $tableName);
                if ($delete && $table_filter) {
                    $changed = max($changed, $this->_deleteTableData($this->_tables[$tableName], $table_filter));
                }
            }
        }

        $this->setChanged($changed);

        return $changed;
    }

    /**
     * The database adapter used by the model.
     *
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_select->getAdapter();
    }

    /**
     * The select object where we get the query from.
     *
     * @return \Zend_Db_Select
     */
    public function getSelect()
    {
        $select = clone $this->_select;

        if (! $this->hasItemsUsed()) {
            foreach ($this->_tables as $name => $table) {
                $select->columns(\Zend_Db_Select::SQL_WILDCARD, $name);
            }
            foreach ($this->getCol('column_expression') as $name => $expression) {
                $select->columns(array($name => $expression));
            }
        }

        return $select;
    }

    /**
     * Does this table alias exist in the mdoel.
     *
     * @param string $name The name of the alias
     * @return boolean
     */
    public function hasAlias($name)
    {
        return (boolean) isset($this->_tables[$name]);
    }

    /**
     *
     * @param string $tableName Does not test for existence
     * @return \MUtil_Model_JoinModel (continuation pattern)
     */
    public function setTableKeysToJoin($tableName)
    {
        $origKeys = $this->_getKeysFor($tableName);

        // First remove old keys
        foreach ($origKeys as $key) {
            $this->del($key, 'key');
        }

        foreach ($this->_joinFields as $left => $right) {
            if (is_string($left) && $this->is($left, 'table', $tableName)) {
                $this->set($left, 'key', true);
            }
            if (is_string($right) && $this->is($right, 'table', $tableName)) {
                $this->set($right, 'key', true);
            }
        }

        return $this;
    }

    /**
     * Add the table to the default save tables.
     *
     * Only tables marked as save tables are saved during a save() or delete(),
     * unless this is overuled by the extra parameter for those functions in
     * this object.
     *
     * @param string $tableName     Does not test for existence
     * @param mixed  $saveable      Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @return \MUtil_Model_JoinModel (continuation pattern)
     */
    public function setTableSaveable($tableName, $saveable = true)
    {
        $this->_setTableSaveable($tableName, $saveable);
        return $this;
    }
}
