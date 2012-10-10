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
class EditTrackSnippet extends Gems_Tracker_Snippets_EditTrackSnippetAbstract
{
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
        $bridge->addHidden(   'gr2t_id_respondent_track');
        $bridge->addHidden(   'gr2t_id_user');
        $bridge->addHidden(   'gr2t_id_track');
        $bridge->addHidden(   'gr2t_id_organization');
        $bridge->addHidden(   'gr2t_active');
        $bridge->addHidden(   'gr2t_count');
        $bridge->addHidden(   'gr2t_completed');
        $bridge->addHidden(   'gr2t_reception_code');
        $bridge->addHidden(   'gr2o_id_organization');
        $bridge->addHidden(   'gtr_id_track');
        $bridge->addHidden(   'grc_success');

        // Patient
        $bridge->addExhibitor('gr2o_patient_nr', 'label', $this->_('Respondent number'));
        $bridge->addExhibitor('respondent_name', 'label', $this->_('Respondent name'));

        // Track
        $bridge->addExhibitor('gtr_track_name');
        if ($this->trackEngine) {
            $elements = $this->trackEngine->getFieldsElements();

            foreach ($elements as $element) {
                $element->setBelongsTo(self::TRACKFIELDS_ID);
                $bridge->addElement($element);
            }
        }

        if (isset($this->formData['gr2t_completed']) && $this->formData['gr2t_completed']) {
            // Cannot change start date after first answered token
            $bridge->addExhibitor('gr2t_start_date');
        } else {
            $bridge->addDate('gr2t_start_date', 'size', 30);
        }
        $bridge->addDate('gr2t_end_date', 'size', 30);

        if (isset($this->formData['grc_succes']) && $this->formData['grc_succes']) {
            $bridge->addExhibitor('grc_description', 'label', $this->_('Rejection code'));
        }
    }

    /**
     *
     * @return Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'index', $this->_('Show tracks'))
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
    protected function saveData()
    {
        if ($this->trackEngine && isset($this->formData[self::TRACKFIELDS_ID])) {
            // concatenate user input (gtf_field fields)
            // before the data is saved (the fields them
            $this->formData['gr2t_track_info'] = $this->trackEngine->calculateFieldsInfo($this->respondentTrackId, $this->formData[self::TRACKFIELDS_ID]);
        }

        // Perform the save
        $model          = $this->getModel();
        $this->formData = $model->save($this->formData);
        $changed        = $model->getChanged();
        $refresh        = false;

        // Retrieve the key if just created
        if ($this->createData) {
            $this->respondentTrackId = $this->formData['gr2t_id_respondent_track'];
            $this->respondentTrack   = $this->loader->getTracker()->getRespondentTrack($this->formData);

            // Create the actual tokens!!!!
            $this->trackEngine->checkRoundsFor($this->respondentTrack, $this->userId);

        } elseif (! (isset($this->formData['gr2t_completed']) && $this->formData['gr2t_completed'])) {
            // Check if startdate has changed
            if (! $this->respondentTrack->getStartDate()->equals(new MUtil_Date($this->formData['gr2t_start_date']))) {
                $refresh = true;
            }
        }

        if ($this->trackEngine && isset($this->formData[self::TRACKFIELDS_ID])) {
            $changed = $this->trackEngine->setFieldsData($this->respondentTrackId, $this->formData[self::TRACKFIELDS_ID]) ? 1 : $changed;
            $refresh = $refresh || $changed;
        }

        if ($refresh) {
            // Perform a refresh from the database, to avoid date trouble
            $this->respondentTrack->refresh();
            $this->respondentTrack->checkTrackTokens($this->userId);
        }


        // Communicate with the user
        $this->afterSave($changed);
    }
}
