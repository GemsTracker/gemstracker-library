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
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ComplianceTableSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_Tracker_Summary_SummaryTableSnippet extends Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        // $bridge->getTable()->setAlternateRowClass('odd', 'odd', 'even', 'even');

        // MUtil_Model::$verbose = true;

        $bridge->add(
                'gro_round_description',
                $bridge->createSortLink('gro_id_order', $model->get('gro_round_description', 'label'))
                );
        $bridge->addSortable('gsu_survey_name');
        $bridge->th(array($bridge->createSortLink('answered'), 'colspan' => 2))->class = 'centerAlign';
        $bridge->td($bridge->answered)->class = 'centerAlign';
        $bridge->td($this->percentageLazy($bridge->answered, $bridge->total))->class = 'rightAlign';
        $bridge->th(array($bridge->createSortLink('missed'), 'colspan' => 2))->class = 'centerAlign';
        $bridge->td($bridge->missed)->class = 'centerAlign';
        $bridge->td($this->percentageLazy($bridge->missed, $bridge->total))->class = 'rightAlign';
        $bridge->th(array($bridge->createSortLink('open'), 'colspan' => 2))->class = 'centerAlign';
        $bridge->td($bridge->open)->class = 'centerAlign';
        $bridge->td($this->percentageLazy($bridge->open, $bridge->total))->class = 'rightAlign';
        // $bridge->addSortable('answered');
        // $bridge->addSortable('missed');
        // $bridge->addSortable('open');
        // $bridge->add('future');
        // $bridge->add('unknown');
        $bridge->addColumn(array('=', 'class' => 'centerAlign'));
        $bridge->addSortable('total');
        $bridge->addSortable('gsu_id_primary_group');
        // $bridge->tr();
        // $bridge->add('gsu_survey_name')->colspan = 4;
        // $bridge->add('gsu_id_primary_group')->colspan = 2;
        // $bridge->addColumn();
        /*
        $bridge->addColumn(
                array(
                    $bridge->gsu_survey_name,
                    MUtil_Html::create('em', ' - ', $bridge->gsu_id_primary_group)
                    ),
                array(
                    $model->get('gsu_survey_name', 'label'),
                    MUtil_Html::create('em', ' - ', $model->get('gsu_id_primary_group', 'label'))
                    )
                )->colspan = 7;
        $bridge->add('removed');
         // */
    }

    /**
     *
     * @param MUtil_Lazy_LazyInterface $part
     * @param MUtil_Lazy_LazyInterface $total
     * @return MUtil_Lazy_Call
     */
    public function percentageLazy($part, $total)
    {
        return MUtil_Lazy::method($this, 'showPercentage', $part, $total);
    }

    /**
     *
     * @param int $part
     * @param int $total
     * @return string
     */
    public function showPercentage($part, $total)
    {
        return sprintf($this->_('%d%%'), round($part / $total * 100, 0));
    }
}
