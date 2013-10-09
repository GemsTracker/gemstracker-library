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
 * Transform that can be used to join models to another model in possubly non-relational
 * ways.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Model_Transform_JoinTransformer extends MUtil_Model_SubmodelTransformerAbstract
{
    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param MUtil_Model_ModelAbstract $sub
     * @param array $data
     * @param array $join
     * @param string $name
     */
    protected function transformLoadSubModel
            (MUtil_Model_ModelAbstract $model, MUtil_Model_ModelAbstract $sub, array &$data, array $join, $name)
    {
        if (1 === count($join)) {
            // Suimple implementation
            $mkey = key($join);
            $skey = reset($join);

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
            // Multi column implementation
            $empty = array_fill_keys($sub->getItemNames(), null);
            foreach ($data as &$mrow) {
                $filter = $sub->getFilter();
                foreach ($join as $from => $to) {
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

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param MUtil_Model_ModelAbstract $sub
     * @param array $data
     * @param array $join
     * @param string $name
     */
    protected function transformSaveSubModel
            (MUtil_Model_ModelAbstract $model, MUtil_Model_ModelAbstract $sub, array &$row, array $join, $name)
    {
        $keys = array();

        // Get the parent key values.
        foreach ($join as $parent => $child) {
            if (isset($row[$parent])) {
                $keys[$child] = $row[$parent];
            }
        }

        $row   = $keys + $row;
        $saved = $sub->save($row);

        $row = $saved + $row;
    }
}
