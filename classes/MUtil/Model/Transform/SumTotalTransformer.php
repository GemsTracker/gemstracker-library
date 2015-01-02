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
 * - sum: just add the total
 * - last: use the last value that occured
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.4 2-jan-2015 17:17:34
 */
class SumTotalTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    private $_rowClassField;

    private $_summarizeOn = array();

    public function __construct($rowClassField = null)
    {
        $this->_rowClassField = $rowClassField;
    }

    public function addTotal($field, $rowClass = null, $labels = false)
    {
        $this->_summarizeOn[$field] = array(
            'rowClass' => $rowClass,
            'labels'   => $labels,
            );

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

        if ($this->_rowClassField) {
            foreach ($this->_summarizeOn as $keyField => $settings) {
                $sumValues[$keyField][$this->_rowClassField] = $settings['rowClass'];
            }
        }

        foreach ($data as $row) {
            foreach ($sumValues as $keyField => $currentValues) {
                if (isset($sumValues[$keyField], $keyValues[$keyField], $row[$keyField]) &&
                        ($row[$keyField] !== $keyValues[$keyField])) {
                    $output[] = $sumValues[$keyField];
                }
            }
            $output[] = $row;

            foreach ($sumValues as $keyField => $currentValues) {
                if (isset($row[$keyField])) {
                    if ((! isset($keyValues[$keyField])) || ($row[$keyField] !== $keyValues[$keyField])) {
                        $keyValues[$keyField] = $row[$keyField];
                        $currentValues        = array_combine(array_keys($sumReset), $sumReset);

                        if ($this->_rowClassField && $this->_summarizeOn[$keyField]['rowClass']) {
                            $currentValues[$this->_rowClassField] = $this->_summarizeOn[$keyField]['rowClass'];
                        }
                    }
                }
                foreach ($summarizeCols as $fieldName => $function) {
                    if (isset($row[$fieldName], $currentValues[$fieldName])) {
                        switch ($function) {
                            case 'sum':
                                $currentValues[$fieldName] = $currentValues[$fieldName] + $row[$fieldName];
                                break;

                            case 'last':
                                $currentValues[$fieldName] = $row[$fieldName];
                                break;

                            default:
                                if (is_callable($function)) {
                                    $currentValues[$fieldName] = $function($row[$fieldName], $currentValues[$fieldName], $keyField);
                                }
                        }
                    }
                }
                $sumValues[$keyField] = $currentValues;
            }
        }

        foreach ($sumValues as $currentValues) {
            $output[] = $currentValues;
        }

        return $output;
    }
}
