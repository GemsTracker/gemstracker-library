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
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 * Displays a table for TokenModel
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
  */
class Gems_Snippets_TokenPlanTableSnippet extends Gems_Snippets_ModelTableSnippetGeneric
{
    public $filter = array();

    /**
     * @var GemsEscort
     */
    public $escort;

    public function addBrowseTableColumns(\MUtil_Model_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $model->set('gr2o_patient_nr',       'label', $this->_('Respondent'));
        $model->set('gto_round_description', 'label', $this->_('Round / Details'));
        $model->set('gto_valid_from',        'label', $this->_('Valid from'));
        $model->set('gto_valid_until',       'label', $this->_('Valid until'));
        $model->set('gto_mail_sent_date',    'label', $this->_('Contact date'));
        $model->set('respondent_name',       'label', $this->_('Name'));

        $HTML  = MUtil_Html::create();

        // Row with dates and patient data
        $bridge->gtr_track_type; // Data needed for buttons

        $bridge->setDefaultRowClass(MUtil_Html_TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $bridge->addColumn($this->getTokenLinks($bridge), ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addMultiSort('gr2o_patient_nr', $HTML->raw('; '), 'respondent_name');
        $bridge->addMultiSort('ggp_name', array($this->getActionLinks($bridge)));

        $bridge->tr();
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');

        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
            $bridge->addMultiSort('calc_round_description', $HTML->raw('; '), 'gsu_survey_name');
        } else {
            $model->set('calc_track_info', 'tableDisplay', 'smallData');
            $model->set('calc_round_description', 'tableDisplay', 'smallData');
            $bridge->addMultiSort(
                'calc_track_name', 'calc_track_info',
                $bridge->calc_track_name->if($HTML->raw(' &raquo; ')),
                'gsu_survey_name', 'calc_round_description');
        }

        $bridge->addSortable('assigned_by');
    }
    
    public function getActionLinks(MUtil_Model_TableBridge $bridge)
    {
        // Get the other token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => array('track', 'survey'), 'action' => array('email', 'answer'), 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->request, $bridge);
            $buttons->appendAttrib('class', 'rightFloat');
        } else {
            $buttons = null;
        }
        // Add the ask button
        if ($menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
            $askLink = $menuItem->toActionLink($this->request, $bridge);
            $askLink->appendAttrib('class', 'rightFloat');

            if ($buttons) {
                // Show previous link if show, otherwise show ask link
                $buttons = array($buttons, $askLink);
            } else {
                $buttons = $askLink;
            }
        }

        return $buttons;
    }

    public function getTokenLinks(MUtil_Model_TableBridge $bridge)
    {
        // Get the token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => array('track', 'survey'), 'action' => 'show', 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->request, $bridge, $this->_('+'));
            $buttons->title = $bridge->gto_id_token->strtoupper();

            return $buttons;
        }
    }

    public function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        if (!empty($this->filter)) {
            $model->setFilter($this->filter);
        }

        parent::processFilterAndSort($model);

        if (!empty($this->filter)) {
            $filter = $model->getFilter();
            unset($filter['gto_id_token']);
            $model->setFilter($filter);
        }
    }
}