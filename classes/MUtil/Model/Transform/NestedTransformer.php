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
 * @version    $Id: NestedTransformer.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Transform that can be used to put nested submodels in a model
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.6.2
 */
class MUtil_Model_Transform_NestedTransformer extends \MUtil_Model_SubmodelTransformerAbstract
{
    /**
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @return array Of filedname => set() values
     */
    public function getFieldInfo(\MUtil_Model_ModelAbstract $model)
    {
        $data = array();
        foreach ($this->_subModels as $sub) {
            foreach ($sub->getItemNames() as $name) {
                if (! $model->has($name)) {
                    $data[$name] = $sub->get($name);
                    $data[$name]['no_text_search'] = true;

                    // Remove unsuited data
                    unset($data[$name]['table'], $data[$name]['column_expression']);
                    unset($data[$name]['label']);
                    $data[$name]['elementClass'] = 'None';

                    // Remove the submodel's own transformers to prevent changed/created to show up in the data array instead of only in the nested info
                    unset($data[$name][\MUtil_Model_ModelAbstract::LOAD_TRANSFORMER]);
                    unset($data[$name][\MUtil_Model_ModelAbstract::SAVE_TRANSFORMER]);
                }
            }
        }
        return $data;
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param \MUtil_Model_ModelAbstract $model Parent model
     * @param \MUtil_Model_ModelAbstract $sub Sub model
     * @param array $data The nested data rows
     * @param array $join The join array
     * @param string $name Name of sub model
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     */
    protected function transformLoadSubModel(
            \MUtil_Model_ModelAbstract $model, \MUtil_Model_ModelAbstract $sub, array &$data, array $join,
            $name, $new, $isPostData)
    {
        foreach ($data as $key => $row) {
            // E.g. if loaded from a post
            if (isset($row[$name])) {
                $rows = $sub->processAfterLoad($row[$name], $new, $isPostData);
            } elseif ($new) {
                $rows = $sub->loadAllNew();
            } else {
                $filter = $sub->getFilter();

                foreach ($join as $parent => $child) {
                    if (isset($row[$parent])) {
                        $filter[$child] = $row[$parent];
                    }
                }
                // If $filter is empty, treat as new
                if (empty($filter)) {
                    $rows = $sub->loadAllNew();
                } else {
                    $rows = $sub->load($filter);
                }
            }

            $data[$key][$name] = $rows;
        }
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param \MUtil_Model_ModelAbstract $sub
     * @param array $data
     * @param array $join
     * @param string $name
     */
    protected function transformSaveSubModel
            (\MUtil_Model_ModelAbstract $model, \MUtil_Model_ModelAbstract $sub, array &$row, array $join, $name)
    {
        if (! isset($row[$name])) {
            return;
        }

        $data = $row[$name];
        $keys = array();

        // Get the parent key values.
        foreach ($join as $parent => $child) {
            if (isset($row[$parent])) {
                $keys[$child] = $row[$parent];
            } else {
                // if there is no parent identifier set, don't save
                return;
            }
        }
        foreach($data as $key => $subrow) {
            // Make sure the (possibly changed) parent key
            // is stored in the sub data.
            $data[$key] = $keys + $subrow;
        }

        $saved = $sub->saveAll($data);

        $row[$name] = $saved;
    }

    /**
     * This transform function checks the sort to
     * a) remove sorts from the main model that are not possible
     * b) add sorts that are required needed
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $sort
     * @return array The (optionally changed) sort
     */
    public function transformSort(\MUtil_Model_ModelAbstract $model, array $sort)
    {
        foreach ($this->_subModels as $sub) {
            foreach ($sort as $key => $value) {
                if ($sub->has($key)) {
                    // Make sure the filter is applied during load
                    $sub->addSort(array($key => $value));

                    // Remove all sorts on columns from the submodel
                    unset($sort[$key]);
                }
            }
        }

        return $sort;
    }
}
