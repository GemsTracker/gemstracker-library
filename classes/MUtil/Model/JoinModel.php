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
class MUtil_Model_JoinModel extends MUtil_Model_DatabaseModelAbstract
{
    protected $_joinFields;
    protected $_saveTables;

    private $_select;
    private $_tables;

    /**
     * Create a model that joins two or more tables
     *
     * @param string $name          the name of the model
     * @param string $startTable    The base table for the model
     * @param bool   $saveable      Will changes to this table be saved
     */
    public function __construct($name, $startTable, $saveable = false)
    {
        parent::__construct($name);

        $table = $this->_loadTable($startTable, $saveable);

        // Fix primary keys to those of the current table.
        $this->getKeys();

        $this->_select = new Zend_Db_Select($table->getAdapter());
        $this->_select->from($this->_getTableName($table), array());
    }

    protected function _joinTable($join, $table, array $joinFields, $saveable = false)
    {
        $table      = $this->_loadTable($table, $saveable);
        $table_name = $this->_getTableName($table);
        $adapter    = $table->getAdapter();

        foreach ($joinFields as $source => $target) {
            $this->_joinFields[$source] = $target;
            $joinSql[] = $adapter->quoteIdentifier($source) . ' = ' . $adapter->quoteIdentifier($target);
        }

        $this->_select->$join($table_name, implode(' ' . Zend_Db_Select::SQL_AND . ' ', $joinSql), array());
    }

    protected function _loadTable($table, $saveable = false)
    {
        if ($table instanceof Zend_Db_Table_Abstract) {
            $table_name = $this->_getTableName($table);
        } else {
            $table_name = (string) $table;
            $table = new Zend_DB_Table($table_name);
        }
        $this->_tables[$table_name] = $table;

        if ($saveable) {
            $this->_saveTables[] = $table_name;
        }

        $this->_loadTableMetaData($table);

        return $table;
    }

    /**
     * Add a table to the model with a left join
     *
     * @param string $table         The name of the table to join
     * @param array  $joinFields    Array of source->dest primary keys for this join
     * @param bool   $saveable      Will changes to this table be saved
     *
     * @return MUtil_Model_JoinModel
     */
    public function addLeftTable($table, array $joinFields, $saveable = false)
    {
        $this->_joinTable('joinLeft', $table, $joinFields, $saveable);
        return $this;
    }

    /**
     * Add a table to the model with a right join
     *
     * @param string $table         The name of the table to join
     * @param array  $joinFields    Array of source->dest primary keys for this join
     * @param bool   $saveable      Will changes to this table be saved
     *
     * @return MUtil_Model_JoinModel
     */
    public function addRightTable($table, array $joinFields, $saveable = false)
    {
        $this->_joinTable('joinRight', $table, $joinFields, $saveable);
        return $this;
    }

    /**
     * Add a table to the model with an inner join
     *
     * @param string $table         The name of the table to join
     * @param array  $joinFields    Array of source->dest primary keys for this join
     * @param bool   $saveable      Will changes to this table be saved
     *
     * @return Gems_Model_JoinModel
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
        if (null === $saveTables) {
            $saveTables = $this->_saveTables;
        }
        $filter = $this->_checkFilterUsed($filter);

        if ($this->_deleteValues) {
            $changed = $this->save($this->_deleteValues + $filter, $filter, $saveTables);
        } else {
            $changed = 0;
            foreach ($saveTables as $table_name) {
                $table_filter = array();
                $delete       = true;

                // Find per table key filters
                foreach ($this->_getKeysFor($table_name) as $key) {
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

                // MUtil_Echo::r($table_filter, $table_name);
                if ($delete && $table_filter) {
                    $changed = max($changed, $this->_deleteTableData($this->_tables[$table_name], $table_filter));
                }
            }
        }

        $this->setChanged($changed);

        return $changed;
    }

    public function getAdapter()
    {
        return $this->_select->getAdapter();
    }

    public function getSelect()
    {
        $select = clone $this->_select;

        if (! $this->hasItemsUsed()) {
            foreach ($this->_tables as $name => $table) {
                $select->columns(Zend_Db_Select::SQL_WILDCARD, $name);
            }
        }

        return $select;
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
    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        if (null === $saveTables) {
            $saveTables = $this->_saveTables;
        }

        $oldChanged = $this->getChanged();

        // MUtil_Echo::r($newValues,  __CLASS__ . '->' . __FUNCTION__);
        // MUtil_Echo::r($saveTables, __CLASS__ . '->' . __FUNCTION__);

        $oldValues = $newValues;
        foreach ($saveTables as $table_name) {
            // Gotta repeat this every time, as keys may be set later
            foreach ($this->_joinFields as $source => $target) {
                // Use is_string as $target and $target can be e.g. a Zend_Db_Expr() object
                if (! (is_string($target) && isset($newValues[$target]) && $newValues[$target])) {
                    if (! (is_string($source) && isset($newValues[$source]) && $newValues[$source])) {
                        continue;
                    }
                    $newValues[$target] = $newValues[$source];

                } elseif (! (is_string($source) && isset($newValues[$source]) && $newValues[$source])) {
                    $newValues[$source] = $newValues[$target];
                }
            }

            //$this->_saveTableData returns the new row values, including any automatic changes.
            $newValues = $this->_saveTableData($this->_tables[$table_name], $newValues, $filter) + $oldValues;
            $oldValues = $newValues;
            // MUtil_Echo::r($newValues, 'JoinModel, after: ' . $table_name);
        }

        // If anything has changed, it counts as only one item for the user.
        if ($this->getChanged() > $oldChanged) {
            $this->setChanged(++$oldChanged);
        }

        return $newValues;
    }

    /**
     *
     * @param string $table_name    Does not test for existence
     * @param bool   $saveable      Will changes to this table be saved
     * @return MUtil_Model_JoinModel
     */
    public function setTableSaveable($table_name, $saveable = true)
    {
        // MUtil_Echo::r(func_get_args(), __CLASS__ . '->' . __FUNCTION__);
        if ($saveable) {
            if (! in_array($table_name, $this->_saveTables)) {
                $this->_saveTables[] = $table_name;
            }
        } else {
            $key = array_search($table_name, $this->_saveTables);
            unset($this->_saveTables[$key]);
        }

        return $this;
    }
}
