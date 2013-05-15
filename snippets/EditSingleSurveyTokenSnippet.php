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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Snippet for editing a single survey token
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditSingleSurveyTokenSnippet extends Gems_Tracker_Snippets_EditSingleSurveyTokenSnippetAbstract
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
        $bridge->addHidden('gr2o_id_organization');
        $bridge->addHidden('gr2t_id_respondent_track');
        $bridge->addHidden('gr2t_id_user');
        $bridge->addHidden('gr2t_id_organization');
        $bridge->addHidden('gr2t_id_track');
        $bridge->addHidden('gr2t_active');
        $bridge->addHidden('gr2t_count');
        $bridge->addHidden('gr2t_reception_code');
        $bridge->addHidden('gr2t_track_info');
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

        // Survey
        $bridge->addExhibitor('gsu_survey_name');
        $bridge->addExhibitor('ggp_name');

        /*
        $bridge->addSelect('to_existing_track', 'label', $this->_('Existing track'), 'multiOptions',
                $this->util->getR);
         * 
         */

        if ($this->trackEngine) {
            $elements = $this->trackEngine->getFieldsElements();

            foreach ($elements as $element) {
                $element->setBelongsTo(self::TRACKFIELDS_ID);
                $bridge->addElement($element);
            }
        }

        // Token
        if ($this->token && $this->token->isCompleted()) {
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

        if (! $this->createData) {
            $bridge->addExhibitor('gto_mail_sent_date');
            $bridge->addExhibitor('gto_completion_time');

            if ($this->token && (! $this->token->hasSuccesCode())) {
                $bridge->addExhibitor('grc_description');
            }
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

        $links->addByController('survey', 'show', $this->_('Show survey'))
                ->addCurrentParent($this->_('Show surveys'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if ($this->createData  && (! ($this->formData || $this->request->isPost()))) {

            $filter['gtr_id_track']         = $this->trackId;
            $filter['gr2o_patient_nr']      = $this->patientId;
            $filter['gr2o_id_organization'] = $this->organizationId;

            $this->formData = $this->getModel()->loadNew(null, $filter);

        } else {
            parent::loadFormData();
        }

        if (! array_key_exists(self::TRACKFIELDS_ID, $this->formData)) {
            if ($this->trackEngine) {
                $this->formData[self::TRACKFIELDS_ID] = $this->trackEngine->getFieldsData($this->respondentTrackId);
            } else {
                $this->formData[self::TRACKFIELDS_ID] = array();
            }
        }
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

        if ($this->createData) {
            $this->respondentTrackId = $this->formData['gr2t_id_respondent_track'];
            $this->tokenId           = $this->formData['gto_id_token'];
        }

        if ($this->trackEngine && isset($this->formData[self::TRACKFIELDS_ID])) {
            $this->trackEngine->setFieldsData($this->respondentTrackId, $this->formData[self::TRACKFIELDS_ID]);
        }

        // Communicate with the user
        $this->afterSave($changed);
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return EditSingleSurveyTokenSnippet (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array($this->request->getActionKey() => $this->routeAction, MUtil_Model::REQUEST_ID => $this->tokenId);
        }

        return $this;
    }
}
