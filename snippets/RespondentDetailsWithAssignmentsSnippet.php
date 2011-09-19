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
 * Displays a respondent's details with assigned surveys and tracks in extra columns.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentDetailsWithAssignmentsSnippet extends Gems_Snippets_RespondentDetailSnippetAbstract
{
    /**
     * Require
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var Gems_Util
     */
    public $util;

    /**
     * Place to set the data to display
     *
     * @param MUtil_Model_VerticalTableBridge $bridge
     * @return void
     */
    protected function addTableCells(MUtil_Model_VerticalTableBridge $bridge)
    {
        $bridge->setColumnCount(1);

        $HTML = MUtil_Html::create();

        $bridge->tdh($this->_('Respondent information'), array('colspan' => 2));

        // Caption for surveys
        $trackLabel = $this->_('Assigned surveys');
        if ($menuItem = $this->findMenuItem('survey', 'index')) {
            $href = $menuItem->toHRefAttribute($this->request, $bridge);
            $bridge->tdh(array('class' => 'linked'))->a($href, $trackLabel);
        } else {
            $bridge->tdh($trackLabel, array('class' => 'linked'));
        }

        // Caption for tracks
        $trackLabel = $this->_('Assigned tracks');
        if ($menuItem = $this->findMenuItem('track', 'index')) {
            $href = $menuItem->toHRefAttribute($this->request, $bridge);
            $bridge->tdh(array('class' => 'linked'))->a($href, $trackLabel);
        } else {
            $bridge->tdh($trackLabel, array('class' => 'linked'));
        }

        $bridge->tr();

        // ROW 1
        $bridge->addItem($bridge->gr2o_patient_nr, $this->_('Respondent nr: '));

        // Column for surveys
        $tracksModel = $this->model->getRespondentTracksModel();
        // Add token as action needs token ID + only one per single survey
        $tracksModel->addTable('gems__tokens', array('gr2t_id_respondent_track' => 'gto_id_respondent_track'));
        $tracksData  = MUtil_Lazy::repeat(
            $tracksModel->load(
                array('gr2o_patient_nr' => $this->repeater->gr2o_patient_nr, 'gr2o_id_organization' => $this->repeater->gr2o_id_organization, 'gtr_track_type' => 'S'),
                array('gr2t_created' => SORT_DESC)));
        $tracksList  = $HTML->div($tracksData, array('class' => 'tracksList'));
        $tracksList->setOnEmpty($this->_('No surveys'));
        if ($menuItem = $this->findMenuItem('survey', 'show')) {
            $href = $menuItem->toHRefAttribute($tracksData, array('gr2o_patient_nr' => $this->repeater->gr2o_patient_nr));
            $tracksTarget = $tracksList->p()->a($href);
        } else {
            $tracksTarget = $tracksList->p();
        }
        $tracksTarget->strong($tracksData->gtr_track_name);
        $tracksTarget[] = ' ';
        $tracksTarget->em($tracksData->gr2t_track_info, array('renderWithoutContent' => false));
        $tracksTarget[] = ' ';
        $tracksTarget[] = MUtil_Lazy::call($this->util->getTranslated()->formatDate, $tracksData->gr2t_created);
        $bridge->td($tracksList, array('rowspan' => 10, 'class' => 'linked tracksList'));

        // Column for tracks
        $tracksModel = $this->model->getRespondentTracksModel();
        $tracksData  = MUtil_Lazy::repeat(
            $tracksModel->load(
                array('gr2o_patient_nr' => $this->repeater->gr2o_patient_nr, 'gr2o_id_organization' => $this->repeater->gr2o_id_organization, 'gtr_track_type' => 'T'),
                array('gr2t_created' => SORT_DESC)));
        $tracksList  = $HTML->div($tracksData, array('class' => 'tracksList'));
        $tracksList->setOnEmpty($this->_('No tracks'));
        if ($menuItem = $this->findMenuItem('track', 'show-track')) {
            $href = $menuItem->toHRefAttribute($tracksData, array('gr2o_patient_nr' => $this->repeater->gr2o_patient_nr));
            $tracksTarget = $tracksList->p()->a($href);
        } else {
            $tracksTarget = $tracksList->p();
        }
        $tracksTarget->strong($tracksData->gtr_track_name);
        $tracksTarget[] = ' ';
        $tracksTarget->em($tracksData->gr2t_track_info, array('renderWithoutContent' => false));
        $tracksTarget[] = ' ';
        $tracksTarget[] = MUtil_Lazy::call($this->util->getTranslated()->formatDate, $tracksData->gr2t_created);
        $bridge->td($tracksList, array('rowspan' => 10, 'class' => 'linked tracksList'));

        // OTHER ROWS
        $bridge->addItem(
            $HTML->spaced($bridge->itemIf('grs_last_name', array($bridge->grs_last_name, ',')), $bridge->grs_first_name, $bridge->grs_surname_prefix),
            $this->_('Respondent'));
        $bridge->addItem('grs_gender');
        $bridge->addItem('grs_birthday');
        $bridge->addItem('grs_email');
        $bridge->addItem('gr2o_created');
        $bridge->addItem('gr2o_created_by');

        if ($this->onclick) {
            // TODO: can we not use $repeater?
            $href = array('location.href=\'', $this->onclick, '\';');

            foreach ($bridge->tbody() as $tr) {
                foreach ($tr as $td) {
                    if (strpos($td->class, 'linked') === false) {
                        $td->onclick = $href;
                    } else {
                        $td->onclick = 'event.cancelBubble=true;';
                    }
                }
            }
            $bridge->tbody()->onclick = '// Dummy for CSS';
        }
   }
}
