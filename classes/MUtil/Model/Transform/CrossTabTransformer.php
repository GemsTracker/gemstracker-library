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
 * @version    $Id: CrossTabTransformer.php 203 2012-01-01t 12:51:32Z matijs $
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
class MUtil_Model_Transform_CrossTabTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * The fields to crosstab over
     *
     * @var array Nested array: index => array('id' => idField, 'val' => valueField, 'pre' => prefix)
     */
    protected $crossTabs;

    /**
     * The fields to exclude from the crosstab result
     *
     * Calculated by setCrosstabFields
     *
     * @var array idField => idField
     */
    protected $excludes;

    /**
     * Set the idField / crossTab output fields for the transformer.
     *
     * You can define multiple crossTabs over the same id value.
     *
     * @param string $idField    The field values to perform the crosstab over
     * @param string $valueField The field values to crosstab
     * @param string $prefix     Optional prefix to add before the $idField value as the identifier
     *                           for the output field, otherwise
     * @return \MUtil_Model_Transform_CrossTabTransformer (continuation pattern)
     */
    public function addCrosstabField($idField, $valueField, $prefix = null)
    {
        if (null === $prefix) {
            $prefix = $valueField . '_';
        }

        $this->crossTabs[] = array(
            'id'  => $idField,
            'val' => $valueField,
            'pre' => $prefix,
            );

        $this->excludes[$idField]    = $idField;
        $this->excludes[$valueField] = $valueField;

        return $this;

    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
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

        //*
        $row = reset($data);
        if (! ($this->crossTabs)) {
            return $data;
        }

        $keys    = $model->getKeys();
        $keys    = array_combine($keys, $keys);
        $default = array_fill_keys(array_keys(array_diff_key($this->_fields, $this->excludes)), null);
        $results = array();
        // \MUtil_Echo::track($default);

        foreach ($data as $row) {
            foreach ($this->crossTabs as $crossTab) {
                $name = $crossTab['pre'] . $row[$crossTab['id']];

                $key = implode("\t", array_intersect_key($row, $keys));

                if (! isset($results[$key])) {
                    $results[$key] = array_diff_key($row, $this->excludes) + $default;
                }

                $results[$key][$name] = $row[$crossTab['val']];
            }
        }

        if (\MUtil_Model::$verbose) {
            \MUtil_Echo::r($results, 'Transform output');
        }
        return $results;
    }
}
