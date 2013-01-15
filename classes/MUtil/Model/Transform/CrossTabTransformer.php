<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: CrossTabTransformer.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Model_Transform_CrossTabTransformer extends MUtil_Model_ModelTransformerAbstract
{
    /**
     * The field to crosstab over
     *
     * @var string
     */
    protected $idField;

    /**
     *
     * @var string
     */
    protected $valueField;

    /**
     *
     * @param string $idField The field values to perform the crosstab over
     * @param string $valueField The field values to crosstab
     * @return MUtil_Model_Transform_CrossTabTransformer (continuation pattern)
     */
    public function setCrosstabFields($idField, $valueField)
    {
        $this->idField = $idField;
        $this->valueField = $valueField;

        return $this;

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
        if (! $data) {
            return $data;
        }

        //*
        $row = reset($data);
        if (! ($this->idField &&
                $this->valueField &&
                isset($row[$this->idField]) &&
                array_key_exists($this->valueField, $row)
                )) {
            return $data;
        }

        $keys    = $model->getKeys();
        $keys    = array_combine($keys, $keys);
        $default = array_fill_keys(array_keys($this->_fields), null);
        $except  = array($this->idField => 1, $this->valueField => 1);
        $results = array();
        foreach ($data as $row) {
            $name = 'col_' . $row[$this->idField];

            if (isset($this->_fields[$name])) {
                $key = implode("\t", array_intersect_key($row, $keys));

                if (! isset($results[$key])) {
                    $results[$key] = array_diff_key($row, $except) + $default;
                }
                $results[$key][$name] = $row[$this->valueField];
            }
        }

        // MUtil_Echo::track($results, $data);
        return $results;
    }
}
