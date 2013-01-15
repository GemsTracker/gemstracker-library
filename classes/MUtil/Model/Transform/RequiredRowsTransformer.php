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
 * Transforms the output of a model->load() function to include required rows.
 *
 * A good usage example is a time report, when there has to be an output row for e.g.
 * every week, even when there is no data for that week.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Model_Transform_RequiredRowsTransformer extends MUtil_Model_ModelTransformerAbstract
{
    /**
     * Contains default values for all missing row values
     *
     * @var mixed Something that can be made into an array using MUtil_Ra::to()
     */
    protected $_defaultRow;

    /**
     * The number of key values to compare, if empty the number of fields in the first required row
     *
     * @var int
     */
    protected $_keyItemCount;

    /**
     *
     * @var mixed Something that can be made into an array using MUtil_Ra::to()
     */
    protected $_requiredRows;

    /**
     *
     * @param array $required
     * @param array $row
     * @param int $count
     * @return boolean True if the rows refer to the same row
     */
    protected function _compareRows($required, $row, $count)
    {
        if ($row) {
            $val1 = reset($required);
            $val2 = reset($row);
            $i = 0;

            while ($i < $count) {
                if ($val1 != $val2) {
                    return false;
                }
                $val1 = next($required);
                $val2 = next($row);
                $i++;
            }
            return true;

        } else {
            return false;
        }
    }

    /**
     * Returns the required rows set or calculates the rows using the $model and the required rows info
     *
     * @param MUtil_Model_ModelAbstract $model Optional model for calculation
     * @return array
     * @throws MUtil_Model_ModelException
     */
    public function getDefaultRow(MUtil_Model_ModelAbstract $model = null)
    {
        if (! $this->_defaultRow) {
            $requireds = $this->getRequiredRows();
            $required  = MUtil_Ra::to(reset($requireds));

            if (! $this->_keyItemCount) {
                $this->setKeyItemCount(count($required));
            }

            if ($required && ($model instanceof MUtil_Model_ModelAbstract)) {
                $defaults = array();
                foreach ($model->getItemNames() as $name) {
                    if (! array_key_exists($name, $required)) {
                        $defaults[$name] = null;
                    }
                }
                $this->_defaultRow = $defaults;
            } else {
                throw new MUtil_Model_ModelException('Cannot create default row without model and required rows.');
            }
        } elseif (! is_array($this->_defaultRow)) {
            $this->_defaultRow = MUtil_Ra::to($this->_defaultRow);
        }

        return $this->_defaultRow;
    }

    /**
     * The number of key values to compare
     *
     * @return int
     */
    public function getKeyItemCount()
    {
        if (! $this->_keyItemCount) {
            $required = MUtil_Ra::to(reset($this->getRequiredRows()));
            $this->setKeyItemCount(count($required));
        }

        return $this->_keyItemCount;
    }

    /**
     * Array of required rows
     *
     * @return array
     */
    public function getRequiredRows()
    {
        if (! is_array($this->_requiredRows)) {
            $this->_requiredRows = MUtil_Ra::to($this->_requiredRows);
        }

        return $this->_requiredRows;
    }

    /**
     * Contains default values for all missing row values
     *
     * @param mixed $defaultRow Something that can be made into an array using MUtil_Ra::to()
     * @return \MUtil_Model_Transform_RequiredRowsTransformer
     * @throws MUtil_Model_ModelException
     */
    public function setDefaultRow($defaultRow)
    {
        if (MUtil_Ra::is($defaultRow)) {
            $this->_defaultRow = $defaultRow;
            return $this;
        }

        throw new MUtil_Model_ModelException('Invalid parameter type for ' . __FUNCTION__ . ': $rows cannot be converted to an array.');
    }

    /**
     * The number of key values to compare
     *
     * @param int $count
     * @return \MUtil_Model_Transform_RequiredRowsTransformer
     */
    public function setKeyItemCount($count)
    {
        $this->_keyItemCount = $count;
        return $this;
    }

    /**
     * The keys for the required rows
     *
     * @param mixed $rows Something that can be made into an array using MUtil_Ra::to()
     * @return \MUtil_Model_Transform_RequiredRowsTransformer
     * @throws MUtil_Model_ModelException
     */
    public function setRequiredRows($rows)
    {
        if (MUtil_Ra::is($rows)) {
            $this->_requiredRows = $rows;
            return $this;
        }

        throw new MUtil_Model_ModelException('Invalid parameter type for ' . __FUNCTION__ . ': $rows cannot be converted to an array.');
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MUtil_Model_ModelAbstract $model, array $data)
    {
        $defaults  = $this->getDefaultRow($model);
        $keyCount  = $this->getKeyItemCount();
        $requireds = $this->getRequiredRows();
        $data      = MUtil_Ra::to($data, MUtil_Ra::RELAXED);
        $results   = array();

        if (! $data) {
            foreach ($requireds as $key => $required) {
                $results[$key] = $required + $defaults;
            }
        } else {
            $row = reset($data);
            foreach ($requireds as $key => $required) {
                if ($this->_compareRows($required, $row, $keyCount)) {
                    $results[$key] = $row + $required;
                    $row = next($data);
                } else {
                    $results[$key] = $required + $defaults;
                }
            }
        }

        // MUtil_Echo::r($results);

        return $results;
    }
}

