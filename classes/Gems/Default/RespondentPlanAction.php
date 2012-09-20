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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_RespondentPlanAction extends Gems_Default_TokenPlanAction
{
    public $sortKey = array(
        'respondent_name'         => SORT_ASC,
        'gr2o_patient_nr'         => SORT_ASC,
        'calc_track_name'         => SORT_ASC,
        'calc_track_info'         => SORT_ASC,
        'calc_round_description'  => SORT_ASC,
        'gto_round_order'         => SORT_ASC,
        );

    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->gtr_track_type; // Data needed for edit button
        $bridge->gr2t_id_respondent_track; // Data needed for edit button
        $bridge->gr2o_id_organization; // Data needed for edit button

        $HTML = MUtil_Html::create();

        // Get the buttons
        if ($menuItem = $this->menu->find(array('controller' => 'respondent', 'action' => 'show', 'allowed' => true))) {
            $respondentButton = $menuItem->toActionLink($this->getRequest(), $bridge, $this->_('Show respondent'));
            $respondentButton->appendAttrib('class', 'rightFloat');
        } else {
            $respondentButton = null;
        }
        if ($menuItem = $this->menu->find(array('controller' => 'track', 'action' => 'show-track', 'allowed' => true))) {
            $trackButton = $menuItem->toActionLink($this->getRequest(), $bridge, $this->_('Show track'));
            $trackButton->appendAttrib('class', 'rightFloat');
        } else {
            $trackButton = null;
        }

        // Row with dates and patient data
        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
        $bridge->addSortable('gr2o_patient_nr');
        $bridge->addSortable('respondent_name')->colspan = 2;

        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
            $bridge->addSortable('grs_birthday');
            $bridge->addMultiSort('progress', array($respondentButton));
        } else {
            $bridge->addSortable('grs_birthday');
            $bridge->addMultiSort('grs_city', array($respondentButton));

            $model->set('calc_track_info', 'tableDisplay', 'smallData');

            // Row with track info
            $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
            $td = $bridge->addMultiSort('calc_track_name', 'calc_track_info');
            $td->class   = 'indentLeft';
            $td->colspan = 4;
            $td->renderWithoutContent = false; // Do not display this cell and thus this row if there is not content
            $td = $bridge->addMultiSort('progress', array($trackButton));
            $td->renderWithoutContent = false; // Do not display this cell and thus this row if there is not content
        }

        $bridge->tr(array('class' => 'odd'));
        $bridge->addColumn($this->getTokenLinks($bridge))->class = 'rightAlign';
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');
        $model->set('calc_round_description', 'tableDisplay', 'smallData');
        $bridge->addMultiSort('gsu_survey_name', 'calc_round_description')->colspan = 2;

        $bridge->tr(array('class' => 'odd'));
        $bridge->addColumn();
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_id_token');
        $bridge->addMultiSort('ggp_name', array($this->getActionLinks($bridge)));
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = parent::createModel($detailed, $action);

        $model->set('grs_birthday', 'label', $this->_('Birthday'));
        $model->set('grs_city', 'label', $this->_('City'));

        $model->addColumn("CASE WHEN gtr_track_type = 'T' THEN CONCAT(gr2t_completed, '" . $this->_(' of ') . "', gr2t_count) ELSE NULL END", 'progress');
        $model->set('progress', 'label', $this->_('Progress'));

        return $model;
    }

     public function getTopicTitle()
    {
        return $this->_('Respondent planning');
    }
}