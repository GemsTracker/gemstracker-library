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
 *    * Neither the name of the Erasmus MC nor the
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
 * @subpackage Model_Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: VerticalTableBridge.php 1916 2014-05-01 12:49:14 matijsdejong $
 */

/**
 *
 * @package    MUtil
 * @subpackage Model_Bridge
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Model_Bridge_VerticalTableBridge extends \MUtil_Model_Bridge_TableBridgeAbstract
{
    protected $columnCount   = 1;
    protected $columnCounts  = array();
    protected $currentColumn = 0;

    /**
     *
     * @var boolean True if th's should be used for label class.
     */
    protected $labelTh       = true;

    private function _checkAttributesFor($name, array $attr)
    {
        if (is_string($name) && $this->model->has($name)) {
            $attr = $attr + $this->model->get($name, 'colspan', 'rowspan', 'tdClass', 'thClass');
        }

        $hattr = $attr;
        if (isset($attr['colspan'])) {
            // Colspan is applied only to value
            $attr['colspan'] = ($attr['colspan'] * 2) - 1;
            unset($hattr['colspan']);
        }
        if (isset($attr['thClass'])) {
            $hattr['class'] = $attr['thClass'];
            unset($attr['thClass'], $hattr['thClass']);
        }
        if (isset($attr['tdClass'])) {
            $attr['class'] = $attr['tdClass'];
            unset($attr['tdClass'], $hattr['tdClass']);
        }

        return array($attr, $hattr);
    }

    private function _checkColumnAdded(array $attr)
    {
        // Without columnCount the programmer must set the rows by hand.
        if ($this->columnCount) {

            // Get the COLSPAN and add it to the current number of columns
            $colCount = isset($attr['colspan']) ? $attr['colspan'] : 1;
            $this->currentColumn += $colCount;

            // Add the ROWSPAN by substracting COLSPAN from a future number of rows
            //
            // Yep, complicated array work
            if (isset($attr['rowspan']) && $attr['rowspan'] > 1) {

                // Leave out the current row
                $rowspan = $attr['rowspan'];

                // echo '[' . ($rowspan + 1) . '] ';

                // Decrease all already defined column counts with one
                foreach ($this->columnCounts as &$count) {
                    if ($rowspan == 0) {
                        break;
                    }

                    $count -= $colCount;
                    $rowspan--;
                }

                // Define lower column counts for not yet defined rows
                if ($rowspan) {
                    $this->columnCounts = array_pad($this->columnCounts, $rowspan, $this->columnCount - $colCount);
                }

                // \MUtil_Echo::r($this->columnCounts);
            }
        }
    }

    private function _checkColumnNewRow()
    {
        // Without columnCount the programmer must set the rows by hand.
        if ($this->columnCount) {
            // Check for end of rows
            //
            // First get the number of columns that should be in the current row
            if ($this->columnCounts) {
                // \MUtil_Echo::r($this->columnCounts);
                $maxColumns = reset($this->columnCounts);
            } else {
                $maxColumns = $this->columnCount;
            }

            // Now add new row if over column margin.
            //
            // Do this before the ROWSPAN is applied as that applies to future rows
            // \MUtil_Echo::r((is_string($name) ? $name : 'array') . '-' . $this->currentColumn . '-' . $maxColumns);
            if ($this->currentColumn >= $maxColumns) {
                $this->table->tr();
                $this->currentColumn = 0;

                if ($this->columnCounts) {
                    array_shift($this->columnCounts);
                }
            }
        }
    }

    public function addItem($name, $label = null, array $attr = array())
    {
        list($attr, $hattr) = $this->_checkAttributesFor($name, $attr);

        $this->_checkColumnNewRow();

        if (is_string($name) && $this->model->has($name, 'description') && !isset($hattr['title'])) {
            $hattr['title'] = $this->model->get($name, 'description');
        }
        if ($this->labelTh) {
            $this->table->tdh($this->_checkLabel($label, $name), $hattr);
        } else {
            $this->table->td($this->_checkLabel($label, $name), $hattr);
        }

        $this->table->td($this->_getLazyName($name), $attr);

        $this->_checkColumnAdded($attr);

        return $this;
    }

    public function addItemWhen($condition, $name = null, $label = null, array $attr = array())
    {
        $attr['renderWithoutContent'] = false;

        if (null === $name) {
            $name = $condition;
        }
        if (is_string($condition)) {
            $condition = $this->$condition;
        }

        list($attr, $hattr) = $this->_checkAttributesFor($name, $attr);

        $this->_checkColumnNewRow();

        if ($this->labelTh) {
            $this->table->tdh(\MUtil_Lazy::iif($condition, $this->_checkLabel($label, $name)), $hattr);
        } else {
            $this->table->td(\MUtil_Lazy::iif($condition, $this->_checkLabel($label, $name)), $hattr);
        }

        $this->table->td(\MUtil_Lazy::iif($condition, $this->_getLazyName($name)), $attr);

        $this->_checkColumnAdded($attr);

        return $this;
    }

    public function getColumnCount()
    {
        return $this->columnCount;
    }

    public function getTable()
    {
        if ($this->columnCount) {
            // Check for end of rows
            //
            // First get the number of columns that should be in the current row
            if ($this->columnCounts) {
                // \MUtil_Echo::r($this->columnCounts);
                $maxColumns = $this->columnCounts;
            } else {
                $maxColumns = array($this->columnCount);
            }

            // Pad the table for as long as it takes
            foreach ($maxColumns as $maxColumn) {
                while ($this->currentColumn < $maxColumn) {
                    $this->table->tdh();
                    $this->table->td();
                    $this->currentColumn++;
                }
            }
        }

        return $this->table;
    }

    /**
     * Add an item based of a lazy if
     *
     * @param mixed $if
     * @param mixed $item
     * @param mixed $else
     * @return array
     */
    public function itemIf($if, $item, $else = null)
    {
        if (is_string($if)) {
            $if = $this->$if;
        }

        return \MUtil_Lazy::iff($if, $item, $else);
    }

    public function setColumnCount($count)
    {
        $this->columnCount = $count;

        return $this;
    }

    public function setColumnCountOff()
    {
        return $this->setColumnCount(false);
    }

    public function setLabelTh($value)
    {
        $this->labelTh = $value;
    }
}