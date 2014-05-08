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
 * Snippet for showing the all tokens for a single survey type for a single patient
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see MUtil_Registry_TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class BrowseSingleSurveyTokenSnippet extends Gems_Snippets_TokenModelSnippetAbstract
{
    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = true;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var array Token filter
     */
    protected $filter = array();

    /**
     *
     * @var array
     */
    public $_fixedSort = array(
            'calc_used_date'  => SORT_ASC,
            'gtr_track_name'  => SORT_ASC,
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC);

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_Bridge_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_Bridge_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        // Some of the calulated fields have their own sort logic
        $this->sortCalcDateCheck($model);

        $HTML = MUtil_Html::create();

        // Signal the bridge that we need these values
        $bridge->gtr_track_type;
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;

        $bridge->setOnEmpty($this->_('This track is currently not assigned to this respondent.'));

        /*
        $roundDescription[] = $HTML->if($bridge->calc_round_description, $HTML->small(' [', $bridge->calc_round_description, ']'));
        $roundDescription[] = $HTML->small(' [', $bridge->createSortLink('calc_round_description'), ']');

        if ($menuItem = $this->findMenuItem('track', 'show-track')) {
            $href = $menuItem->toHRefAttribute($this->request, $bridge);
            $track1 = $HTML->if($bridge->calc_track_name, $HTML->a($href, $bridge->calc_track_name));
        } else {
            $track1 = $bridge->calc_track_name;
        }
        $track[] = array($track1, $HTML->if($bridge->calc_track_info, $HTML->small(' [', $bridge->calc_track_info, ']')));
        $track[] = array($bridge->createSortLink('calc_track_name'), $HTML->small(' [', $bridge->createSortLink('calc_track_info'), ']'));

        // Set column widths to prevent strange column breaking in dates
        //
        // Use colgroup to test the second way of doing this.
        // $bridge->colgroup(array('span' => 3));
        // $bridge->colgroup(array('span' => 3, 'width' => '9em'));

        $bridge->addMultiSort($track);
        $bridge->addMultiSort('gsu_survey_name', $roundDescription);
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('calc_used_date', null, $HTML->if($bridge->is_completed, 'disabled date', 'enabled date'));
        // */

        $bridge->addMultiSort('gtr_track_name');
        $bridge->addMultiSort('calc_round_description');
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('calc_used_date', null, $HTML->if($bridge->is_completed, 'disabled date', 'enabled date'));

        //If we are allowed to see the result of the survey, show it
        if (GemsEscort::getInstance()->hasPrivilege('pr.respondent.result')) {
            $bridge->addSortable('gto_result', $this->_('Score'));
        }
        // $bridge->addSortable('gto_completion_time', null, 'date');

        $bridge->useRowHref = false;

        $title = $HTML->strong($this->_('+'));

        $showLinks[]   = $this->createMenuLink($bridge, 'track',  'show', $title);
        $showLinks[]   = $this->createMenuLink($bridge, 'survey', 'show', $title);
        $actionLinks[] = $this->createMenuLink($bridge, 'track',  'answer');
        $actionLinks[] = $this->createMenuLink($bridge, 'survey', 'answer');
        $actionLinks[] = array(
            $bridge->ggp_staff_members->if($this->createMenuLink($bridge, 'ask', 'take'), $bridge->calc_id_token->strtoupper()),
            'class' => $bridge->ggp_staff_members->if(null, $bridge->calc_id_token->if('token')));
        // calc_id_token is leeg als vraqgenlijst ingevuld

        // MUtil_Lazy::comp($bridge->val1, '==', $bridge->val2)->if($bridge->val3, 'broehaha');

        // Remove nulls
        $showLinks   = array_filter($showLinks);
        $actionLinks = array_filter($actionLinks);

        if ($showLinks || $actionLinks) {
            foreach ($showLinks as $showLink) {
                if ($showLink) {
                    $showLink->title = array($this->_('Token'), $bridge->gto_id_token->strtoupper());
                }
            }
            $bridge->addItemLink($actionLinks);
            $bridge->addItemLink($showLinks);
        }
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        if (true || ! $this->filter) {
            $patientId = $this->request->getParam(MUtil_Model::REQUEST_ID1);
            $orgId     = $this->request->getParam(MUtil_Model::REQUEST_ID2);
            $trackId   = $this->request->getParam(Gems_Model::TRACK_ID);

            $this->filter['gr2o_patient_nr']      = $patientId;
            $this->filter['gr2o_id_organization'] = $orgId;
            $this->filter['gro_id_track']         = $trackId;

            // MUtil_Echo::track($this->filter);
        }
        $model->addFilter($this->filter);

        $this->processSortOnly($model);
    }
}
