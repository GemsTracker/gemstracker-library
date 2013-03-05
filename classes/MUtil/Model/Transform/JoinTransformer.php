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
 * @version    $id: JoinTransformer.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Transform that can be used to join models to another model in non-relational
 * ways.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Model_Transform_JoinTransformer implements MUtil_Model_ModelTransformerInterface
{
    /**
     *
     * @var array of join functions
     */
    protected $_joins = array();

    /**
     *
     * @var array of MUtil_Model_ModelAbstract
     */
    protected $_subModels = array();

    public function addModel(MUtil_Model_ModelAbstract $subModel, array $joinFields)
    {
        // MUtil_Model::$verbose = true;

        $name = $subModel->getName();
        $this->_subModels[$name] = $subModel;
        $this->_joins[$name]     = $joinFields;

        return $this;
    }

    /**
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @return array Of filedname => set() values
     */
    public function getFieldInfo(MUtil_Model_ModelAbstract $model)
    {
        $data = array();
        foreach ($this->_subModels as $sub) {
            foreach ($sub->getItemNames() as $name) {
                if (! $model->has($name)) {
                    $data[$name] = $sub->get($name);

                    // Remove unsuited data
                    unset($data[$name]['table'], $data[$name]['column_expression']);
                }
            }
        }
        return $data;
    }

    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are the result
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(MUtil_Model_ModelAbstract $model, array $filter)
    {
        // Make sure the join fields are in the result set/
        foreach ($this->_joins as $joins) {
            foreach ($joins as $source => $target) {
                if (!is_integer($source)) {
                    $model->get($source);
                }
            }
        }

        return $filter;
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

        foreach ($this->_subModels as $name => $sub) {
            $this->transformSubModel($model, $sub, $data, $name);
        }
        // MUtil_Echo::track($data);

        return $data;
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param MUtil_Model_ModelAbstract $sub
     * @param array $data
     * @param string $name
     */
    protected function transformSubModel
            (MUtil_Model_ModelAbstract $model, MUtil_Model_ModelAbstract $sub, array &$data, $name)
    {
        if (1 === count($this->_joins[$name])) {
            $mkey = key($this->_joins[$name]);
            $skey = reset($this->_joins[$name]);

            $mfor = MUtil_Ra::column($mkey, $data);

            // MUtil_Echo::track($mfor);

            $sdata = $sub->load(array($skey => $mfor));
            // MUtil_Echo::track($sdata);

            if ($sdata) {
                $skeys = array_flip(MUtil_Ra::column($skey, $sdata));
                $empty = array_fill_keys(array_keys(reset($sdata)), null);

                foreach ($data as &$mrow) {
                    $mfind = $mrow[$mkey];

                    if (isset($skeys[$mfind])) {
                        $mrow += $sdata[$skeys[$mfind]];
                    } else {
                        $mrow += $empty;
                    }
                }
            } else {
                $empty = array_fill_keys($sub->getItemNames(), null);

                foreach ($data as &$mrow) {
                    $mrow += $empty;
                }
            }
        } else {
            $empty = array_fill_keys($sub->getItemNames(), null);
            foreach ($data as &$mrow) {
                $filter = $sub->getFilter();
                foreach ($this->_joins[$name] as $from => $to) {
                    if (isset($mrow[$from])) {
                        $filter[$to] = $mrow[$from];
                    }
                }

                $sdata = $sub->loadFirst($filter);

                if ($sdata) {
                    $mrow += $sdata;
                } else {
                    $mrow += $empty;
                }

                // MUtil_Echo::track($sdata, $mrow);
            }
        }
    }
}
