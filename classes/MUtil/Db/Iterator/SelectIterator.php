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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $Id: DbModelIterator.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Db_Iterator_SelectIterator implements Countable, Iterator
{
    /**
     * The number of items
     *
     * @var int
     */
    protected $_count;

    /**
     * Current key
     *
     * @var int
     */
    protected $_i;

    /**
     *
     * @var array
     */
    protected $_row;

    /**
     *
     * @var type
     */
    protected $_select;

    /**
     *
     * @var Zend_Db_Statement_Interface
     */
    protected $_statement;

    /**
     *
     * @param Zend_Db_Select $select
     */
    public function __construct(Zend_Db_Select $select)
    {
        $this->_select = $select;
    }

    protected function _initStatement()
    {
        // MUtil_Echo::track($this->_select->__toString());

        $this->_i         = 0;
        $this->_statement = $this->_select->query();
        $this->_row       = $this->_statement->fetch();
    }

    /**
     * Count interface implementation
     * @return int
     */
    public function count()
    {
        if (null !== $this->_count) {
            return $this->_count;
        }

        // Why implement again what has already been done :)
        $pag = new Zend_Paginator_Adapter_DbSelect($this->_select);
        $this->_count = $pag->count();

        return $this->_count;
    }


    /**
     * Return the current element
     *
     * @return array
     */
    public function current()
    {
        if (! $this->_statement instanceof Zend_Db_Statement_Interface) {
            $this->_initStatement();
        }
        return $this->_row;
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        if (! $this->_statement instanceof Zend_Db_Statement_Interface) {
            $this->_initStatement();
        }
        return $this->_i;
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        if (! $this->_statement instanceof Zend_Db_Statement_Interface) {
            $this->_initStatement();
        }
        $this->_row = $this->_statement->fetch();
        $this->_i   = $this->_i + 1;
    }

    /**
     *  Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->_initStatement();
    }

    /**
     * True if not EOF
     *
     * @return boolean
     */
    public function valid()
    {
        if (! $this->_statement instanceof Zend_Db_Statement_Interface) {
            $this->_initStatement();
        }
        return (boolean) $this->_row;
    }

}
