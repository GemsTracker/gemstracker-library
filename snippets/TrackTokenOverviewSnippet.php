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
 * Snippet for showing the all tokens for a single track for a single patient
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see MUtil_Registry_TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TrackTokenOverviewSnippet extends Gems_Snippets_TokenModelSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter = array(
        'gro_active' => 1,
        'gsu_active' => 1,
    );

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC
        );

    /**
     * The respondent2track ID
     *
     * @var int
     */
    protected $respondentTrackId;

    /**
     * Optional: the display data of the track shown
     *
     * @var array
     */
    protected $trackData;

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
        // Signal the bridge that we need these values
        $bridge->gtr_track_type;
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;

        $bridge->tr()->appendAttrib('class', $bridge->row_class);

        $title = MUtil_Html::create()->strong($this->_('+'));

        $showLinks[] = $this->createMenuLink($bridge, 'track',  'show', $title);
        $showLinks[] = $this->createMenuLink($bridge, 'survey', 'show', $title);
        // Remove nulls
        $showLinks   = array_filter($showLinks);

        // Columns
        $bridge->addSortable('gsu_survey_name')
                ->append(MUtil_Lazy::iif($bridge->gro_icon_file, MUtil_Html::create('img', array('src' => $bridge->gro_icon_file, 'class' => 'icon'))));
        $bridge->addSortable('gto_round_description');
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('gto_valid_from',      null, 'date');
        $bridge->addSortable('gto_completion_time', null, 'date');
        $bridge->addSortable('gto_valid_until',     null, 'date');

        if (GemsEscort::getInstance()->hasPrivilege('pr.respondent.result')) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
        }
        $actionLinks[] = $this->createMenuLink($bridge, 'track',  'answer');
        $actionLinks[] = array($bridge->ggp_staff_members->if(
                    $this->createMenuLink($bridge, 'ask', 'take'),
                    $bridge->can_be_taken->if($bridge->calc_id_token->strtoupper())),
            'class' => $bridge->ggp_staff_members->if(null, $bridge->calc_id_token->if('token')));

        // Remove nulls
        $actionLinks   = array_filter($actionLinks);
        if ($actionLinks) {
            $bridge->addItemLink($actionLinks);
        }

        if ($showLinks) {
            foreach ($showLinks as $showLink) {
                if ($showLink) {
                    $showLink->title = array($this->_('Token'), $bridge->gto_id_token->strtoupper());
                }
            }
            $bridge->addItemLink($showLinks);
        }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! $this->respondentTrackId) {
            if (isset($this->trackData['gr2t_id_respondent_track'])) {
                $this->respondentTrackId = $this->trackData['gr2t_id_respondent_track'];

            } elseif (isset($this->trackData['gto_id_respondent_track'])) {
                $this->respondentTrackId = $this->trackData['gto_id_respondent_track'];

            } elseif ($this->request && ($respondentTrackId = $this->request->getParam(Gems_Model::RESPONDENT_TRACK))) {
                $this->respondentTrackId = $respondentTrackId;
            }
        }
        if ($this->respondentTrackId) {
            return parent::hasHtmlOutput();
        } else {
            return false;
        }
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        $model->setFilter(array('gto_id_respondent_track' => $this->respondentTrackId));

        $this->processSortOnly($model);
    }
}
