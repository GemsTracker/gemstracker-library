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
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extra code for displaying token models.
 *
 * Adds columns to the model and adds extra logic for calc_used_date sorting.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Snippets_TokenModelSnippetAbstract extends Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->loader->getTracker()->getTokenModel();
        $model->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN gto_valid_from ELSE gto_completion_time END',
            'calc_used_date',
            'gto_valid_from');
        $model->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN gto_valid_from ELSE NULL END',
            'calc_valid_from',
            'gto_valid_from');
        $model->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN gto_id_token ELSE NULL END',
            'calc_id_token',
            'gto_id_token');

        return $model;
    }

    /**
     * calc_used_date has special sort, see bugs 108 and 127
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function sortCalcDateCheck(MUtil_Model_ModelAbstract $model)
    {
        $sort = $model->getSort();

        if (isset($sort['calc_used_date'])) {
            $add        = true;
            $resultSort = array();

            foreach ($sort as $key => $asc) {
                if ('calc_used_date' === $key) {
                    if ($add) {
                        $resultSort['is_completed']        = $asc;
                        $resultSort['gto_completion_time'] = $asc == SORT_ASC ? SORT_DESC : SORT_ASC;
                        $resultSort['calc_valid_from']     = $asc;
                        $add = false; // We can add this only once
                    }
                } else {
                    $resultSort[$key] = $asc;
                }
            }

            if (! $add) {
                $model->setSort($resultSort);
            }
        }
    }
}
