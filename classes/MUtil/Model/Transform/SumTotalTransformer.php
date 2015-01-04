<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SumTotalTransformer.php $
 */

namespace MUtil\Model\Transform;

/**
 * Add one or more totals lines to the output, either for the whole or on changes in values of a field.
 *
 * Functions that can be used to sum are:
 *
 * - count: the number of rows counted
 * - sum: just add the total
 * - last: use the last value that occured
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.4 2-jan-2015 17:17:34
 */
class SumTotalTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     *
     * @var array of suummarizeField => ['array' => [fieldName => values], 'string' => [fieldName => value]]
     */
    private $_summarizeOn = array();

    /**
     * Helper function for total rows field calculation
     *
     * @param striong $keyField
     * @param mixed $keyValue
     * @param array $currentValues
     */
    protected function _calculateFixedValues($keyField, $keyValue, array &$currentValues)
    {
        if (isset($this->_summarizeOn[$keyField]['arrays'])) {
            foreach ($this->_summarizeOn[$keyField]['arrays'] as $targetField => $lookup) {
                if (isset($lookup[$keyValue])) {
                    $currentValues[$targetField] = $lookup[$keyValue];
                }
            }
        }
        if (isset($this->_summarizeOn[$keyField]['calls'])) {
            foreach ($this->_summarizeOn[$keyField]['calls'] as $targetField => $function) {
                $value = isset($currentValues[$targetField]) ? $currentValues[$targetField] : null;
                $currentValues[$targetField] = call_user_func($function, $value, $targetField);
            }
        }
    }

    /**
     * Add a field to add a totals row on.
     *
     * The other parameters contains fixed field values for that row, e.g. a fixed value:
     *
     * <code>
     * $transformer->addTotal('groupField', 'rowClass', 'total');
     * </code>
     *
     * or a lookup array:
     *
     * <code>
     * $transformer->addTotal('groupField', 'labelField', array('x' => 'Total for X', 'y' => 'Total for Y'));
     * </code>
     *
     * or a callable:
     *
     * <code>
     * $transformer->addTotal('groupField', 'labelField', function ($value, $keyField) {sprintf('Total %d', $value);});
     * </code>
     *
     * for as many fields as required.
     *
     * @param type $field
     * @param type $fixedFieldsArrayOrName1
     * @param type $fixedFieldsValue1
     * @return \MUtil\Model\Transform\SumTotalTransformer
     */
    public function addTotal($field, $fixedFieldsArrayOrName1 = null, $fixedFieldsValue1 = null)
    {
        $args  = \MUtil_Ra::pairs(func_get_args(), 1);
        $fixed = array();

        foreach ($args as $fixedName => $value) {
            if (is_callable($value)) {
                $fixed['calls'][$fixedName] = $value;
            } elseif (is_array($value)) {
                $fixed['arrays'][$fixedName] = $value;
            } else {
                $fixed['string'][$fixedName] = $value;
            }
            // Make sure the fields are know to the model
            $this->_fields[$fixedName] = array();
        }

        $this->_summarizeOn[$field] = $fixed;

        return $this;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        if (! $data) {
            return $data;
        }

        $output        = array();
        $keyValues     = array();
        $summarizeCols = $model->getCol('summaryFunction');
        $sumReset      = array_fill_keys(array_keys($summarizeCols), 0) +
                array_fill_keys(array_keys(reset($data)), null);
        $sumValues     = array_fill_keys(array_keys($this->_summarizeOn), $sumReset);

        foreach ($this->_summarizeOn as $keyField => $settings) {
            if (isset($settings['string'])) {
                $sumValues[$keyField] = $settings['string'] + $sumValues[$keyField];
            }
        }

        foreach ($data as $row) {
            // Add summarize rows to output
            foreach ($sumValues as $keyField => $currentValues) {
                if (isset($sumValues[$keyField], $row[$keyField]) &&
                        array_key_exists($keyField, $keyValues) &&
                        ($row[$keyField] !== $keyValues[$keyField])) {

                    $this->_calculateFixedValues($keyField, $keyValues[$keyField], $currentValues);

                    $output[] = $currentValues;
                }
            }
            $output[] = $row;

            foreach ($sumValues as $keyField => $currentValues) {
                if (array_key_exists($keyField, $row)) {
                    // Create summarize rows
                    if ((!array_key_exists($keyField, $keyValues)) || ($row[$keyField] != $keyValues[$keyField])) {
                        $keyValues[$keyField] = $row[$keyField];
                        $currentValues        = $sumReset;

                        if (isset($this->_summarizeOn[$keyField]['string'])) {
                            $currentValues = $this->_summarizeOn[$keyField]['string'] + $currentValues;
                        }
                    }
                }
                // Calculate summarize values
                foreach ($summarizeCols as $fieldName => $function) {
                    if (array_key_exists($fieldName, $row) && array_key_exists($fieldName, $currentValues)) {
                        switch ($function) {
                            case 'sum':
                                $currentValues[$fieldName] = $currentValues[$fieldName] + $row[$fieldName];
                                break;

                            case 'count':
                                $currentValues[$fieldName]++;
                                break;

                            case 'last':
                                $currentValues[$fieldName] = $row[$fieldName];
                                break;

                            default:
                                break;
                        }
                    }
                }
                $sumValues[$keyField] = $currentValues;
            }
        }

        foreach ($sumValues as $keyField => $currentValues) {
            $keyValue = isset($keyValues[$keyField]) ? $keyValues[$keyField] : null;

            $this->_calculateFixedValues($keyField, $keyValue, $currentValues);

            $output[] = $currentValues;
        }

        return $output;
    }
}
