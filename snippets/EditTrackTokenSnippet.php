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
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditTrackTokenSnippet extends Gems_Tracker_Snippets_EditTokenSnippetAbstract
{
    const RECALCULATE_FIELD = '_recalc';

    /**
     *
     * @var ArrayObject
     */
    protected $session;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->addHidden('gr2o_id_organization');
        $bridge->addHidden('gr2t_id_respondent_track');
        $bridge->addHidden('gr2t_id_user');
        $bridge->addHidden('gr2t_id_organization');
        $bridge->addHidden('gr2t_id_track');
        $bridge->addHidden('gr2t_active');
        $bridge->addHidden('gr2t_count');
        $bridge->addHidden('gr2t_reception_code');
        $bridge->addHidden('gto_id_respondent_track');
        $bridge->addHidden('gto_id_round');
        $bridge->addHidden('gto_id_respondent');
        $bridge->addHidden('gto_id_organization');
        $bridge->addHidden('gto_id_track');
        $bridge->addHidden('gto_id_survey');
        $bridge->addHidden('gtr_id_track');
        $bridge->addHidden('gtr_track_type');

        if (! $this->createData) {
            $bridge->addExhibitor('gto_id_token');
        }

        // Patient
        $bridge->addExhibitor('gr2o_patient_nr');
        $bridge->addExhibitor('respondent_name');

        // Track
        $bridge->addExhibitor('gtr_track_name');
        if ($this->formData['gr2t_track_info']) {
            $bridge->addExhibitor('gr2t_track_info');
        } else {
            $bridge->addHidden('gr2t_track_info');
        }

        // Round
        $bridge->addExhibitor('gsu_survey_name');
        if ($this->formData['gto_round_description']) {
            $bridge->addExhibitor('gto_round_description');
        } else {
            $bridge->addHidden('gto_round_description');
        }
        $bridge->addExhibitor('ggp_name');

        // Token
        if ($this->token->isCompleted()) {
            $bridge->addExhibitor('gto_valid_from');
            $bridge->addExhibitor('gto_valid_until');
        } else {
            $bridge->addDate(     'gto_valid_from');
            $bridge->addDate(     'gto_valid_until')
                ->addValidator(new MUtil_Validate_Date_DateAfter('gto_valid_from'));
            /* $bridge->addDate(     'gto_next_mail_date')
                ->addValidator(new MUtil_Validate_Date_DateAfter('gto_valid_from'))
                ->addValidator(new MUtil_Validate_Date_DateBefore('gto_valid_until')); // */
        }
        $bridge->addTextarea('gto_comment', 'rows', 3, 'cols', 50);

        $bridge->addExhibitor('gto_mail_sent_date');
        $bridge->addExhibitor('gto_completion_time');

        if (! $this->token->hasSuccesCode()) {
            $bridge->addExhibitor('grc_description');
        }
        $bridge->addCheckbox(self::RECALCULATE_FIELD, 'label', $this->_('Recalculate track'));
    }

    /**
     *
     * @return Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('track', 'show', $this->_('Show token'))
                ->addCurrentParent($this->_('Show track'))
                // ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    public function saveData()
    {
        $model = $this->getModel();
        if ($this->formData['gto_valid_until']) {
            // Make sure date based units are valid until the end of the day.
            $date = new MUtil_Date($this->formData['gto_valid_until'], $model->get('gto_valid_until', 'dateFormat'));
            $date->addDay(1);
            $date->subSecond(1);
            $this->formData['gto_valid_until'] = $date;
        }

        parent::saveData();

        if ($this->formData[self::RECALCULATE_FIELD]) {
            // Refresh token with current form data
            $updateData['gto_valid_from']  = $model->getOnSave($this->formData['gto_valid_from'], true, 'gto_valid_from');
            $updateData['gto_valid_until'] = $model->getOnSave($this->formData['gto_valid_until'], true, 'gto_valid_until');
            $updateData['gto_comment']     = $this->formData['gto_comment'];

            $this->token->refresh($updateData);

            $respTrack = $this->token->getRespondentTrack();
            if ($nextToken = $this->token->getNextToken()) {
                $changed = $respTrack->checkTrackTokens($this->session->user_id, $nextToken);
            }

            $this->addMessage(sprintf($this->plural('%d token changed by recalculation.', '%d tokens changed by recalculation.', $changed), $changed));
        }
    }
}
