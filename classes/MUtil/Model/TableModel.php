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
 * A simple mode for a single table
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class MUtil_Model_TableModel extends MUtil_Model_DatabaseModelAbstract
{
    /**
     *
     * @var Zend_Db_Table_Abstract
     */
    private $_table;

    public function __construct($table, $altName = null)
    {
        if ($table instanceof Zend_Db_Table_Abstract) {
            $this->_table = $table;
            $table_name = $this->_getTableName($table);
        } else {
            $this->_table = new Zend_DB_Table($table);
            $table_name = $table;
        }

        parent::__construct(null === $altName ? $table_name : $altName);

        $this->_loadTableMetaData($this->_table);
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {
        return $this->_deleteTableData(
                $this->_table,
                $this->_checkFilterUsed($filter),
                $this->_deleteValues);
    }

    /**
     * The database adapter used by the model.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_table->getAdapter();
    }

    /**
     * Returns a Zend_Db_Table_Select object to work with
     *
     * @return Zend_Db_Table_Select
     */
    public function getSelect()
    {
        if ($this->hasItemsUsed()) {
            $select = $this->_table->select(Zend_Db_Table_Abstract::SELECT_WITHOUT_FROM_PART);
            $select->from($this->_getTableName($this->_table), array());
            return $select;
        } else {
            return $this->_table->select(Zend_Db_Table_Abstract::SELECT_WITH_FROM_PART);
        }
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null)
    {
        // $this->_saveTableData returns the new row values, including any automatic changes.
        // add $newValues to throw nothing away.
        $updatedValues = $this->_saveTableData($this->_table, $newValues, $filter, parent::SAVE_MODE_ALL) + $newValues;

        // Handle possible onLoad
        $updatedValues = $this->processAfterLoad(array($updatedValues));
        return  $updatedValues;
    }
}
