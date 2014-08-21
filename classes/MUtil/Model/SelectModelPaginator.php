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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This class wraps around a select as a paginator, while allowing model->onload
 * functions to apply.
 *
 * It also implements some extra fancy functions to speed up the result retrieval on MySQL databases.
 *
 * @see MUtil_Model_DatabaseModelAbstract
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Model_SelectModelPaginator implements MUtil_Paginator_Adapter_PrefetchInterface
{
    /**
     * Store for count
     *
     * @var int
     */
    protected $_count;

    /**
     * Last item count
     *
     * @var int
     */
    protected $_lastItemCount = null;

    /**
     * Last offset
     *
     * @var int
     */
    protected $_lastItems = null;

    /**
     * Last offset
     *
     * @var int
     */
    protected $_lastOffset = null;

    /**
     *
     * @var MUtil_Model_DatabaseModelAbstract
     */
    protected $_model;

    /**
     *
     * @var Zend_Db_Select
     */
    protected $_select;

    /**
     *
     * @var Zend_Paginator_Adapter_DbSelect
     */
    protected $_selectAdapter;

    /**
     *
     * @param Zend_Db_Select $select
     * @param MUtil_Model_ModelAbstract $model
     */
    public function __construct(Zend_Db_Select $select, MUtil_Model_DatabaseModelAbstract $model)
    {
        $this->_select = $select;
        $this->_selectAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $this->_model = $model;
    }

    /**
     * Returns the total number of rows in the result set.
     *
     * @return integer
     */
    public function count()
    {
        if (null === $this->_count) {
            $this->_count = $this->_selectAdapter->count();
        }

        return $this->_count;
    }

    /**
     * Returns an array of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        // Cast to integers, as $itemCountPerPage can be string sometimes and that would fail later checks
        $offset = (int) $offset;
        $itemCountPerPage = (int) $itemCountPerPage;

        if (($this->_lastOffset === $offset) && ($this->_lastItemCount === $itemCountPerPage)) {
            return $this->_lastItems;
        }
        $this->_lastOffset    = $offset;
        $this->_lastItemCount = $itemCountPerPage;

        // Optimization: by using the MySQL feature SQL_CALC_FOUND_ROWS
        // we can get the count and the results in a single query.
        $db = $this->_select->getAdapter();
        if ($db instanceof Zend_Db_Adapter_Mysqli) {

            $this->_select->limit($itemCountPerPage, $offset);
            $sql = $this->_select->__toString();

            if (MUtil_String::startsWith($sql, 'select ', true)) {
                $sql = 'SELECT SQL_CALC_FOUND_ROWS ' . substr($sql, 7);
            }

            $this->_lastItems = $db->fetchAll($sql);

            $this->_count = $db->fetchOne('SELECT FOUND_ROWS()');

        } else {
            $this->_lastItems = $this->_selectAdapter->getItems($offset, $itemCountPerPage);
        }

        // MUtil_Echo::track($this->_lastItems);
        if (is_array($this->_lastItems)) {
            $this->_lastItems = $this->_model->processAfterLoad($this->_lastItems);
        }
        // MUtil_Echo::track($this->_lastItems);

        return $this->_lastItems;
    }
}