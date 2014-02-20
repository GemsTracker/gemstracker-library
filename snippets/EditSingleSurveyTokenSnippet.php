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
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Add the elements for the track fields
     *
     * @param MUtil_Model_FormBridge $bridge
     */
    protected function _addFieldsElements(MUtil_Model_FormBridge $bridge)
    {
        if ($this->trackEngine) {
            $elements = $this->trackEngine->getFieldNames();

            foreach ($elements as $id => $name) {
                $bridge->add($id);
            }
            // MUtil_Echo::track(array_intersect_key($this->formData, $elements));
        }
    }

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

        //$this->_addFieldsElements($bridge);
        //*
        if (isset($this->formData['gr2t_id_user'], $this->formData['gr2t_id_organization'])) {
            // Sort descending so current tracks are on top of the list
            $tracks = $this->loader->getTracker()->getRespondentTracks($this->formData['gr2t_id_user'], $this->formData['gr2t_id_organization'], array('gr2t_start_date DESC'));

            if (count($tracks)) {
                if (! isset($this->formData['add_to_track'])) {
                    $this->formData['add_to_track'] = $this->createData ? 1 : 0;
                }

                $onclick = new MUtil_Html_OnClickArrayAttribute();
                $onclick->addSubmit();
                $bridge->addRadio('add_to_track', 'label', $this->_('Add'),
                        'multiOptions', array(
                            '1' => $this->_('To existing track'),
                            '0' => $this->_('As standalone track'),
                        ),
                        'onclick', $onclick->get(),
                        'required', true,
                        'separator', ' ');

                if ($this->formData['add_to_track']) {
                    $translated = $this->util->getTranslated();
                    $results    = $translated->getEmptyDropdownArray();

                    foreach ($tracks as $track) {
                        if ($track instanceof Gems_Tracker_RespondentTrack) {
                            if (($track->getTrackEngine()->getTrackType() !== 'S') &&
                                    $track->getReceptionCode()->isSuccess()) {

                                $date = $translated->formatDateUnknown($track->getStartDate());
                                $info = $track->getFieldsInfo();
                                if ($info) {
                                    $results[$track->getRespondentTrackId()] = sprintf(
                                            $this->_('%s [start date: %s, %s]'),
                                            $track->getTrackEngine()->getTrackName(),
                                            $date,
                                            $info
                                            );
                                } else {
                                    $results[$track->getRespondentTrackId()] = sprintf(
                                            $this->_('%s [start date: %s]'),
                                            $track->getTrackEngine()->getTrackName(),
                                            $date
                                            );
                                }
                           }
                       }
                    }
                    $bridge->addSelect('to_existing_track', 'label', $this->_('Existing track'),
                            'multiOptions', $results,
                            'required', true);

                    // Keep the values, so add hidden
                    foreach ($this->trackEngine->getFieldNames() as $key => $code) {
                        $bridge->addHidden($key);
                    }
                } else {
                    $this->_addFieldsElements($bridge);

                    // Keep the value
                    $bridge->addHidden('to_existing_track');
                }
            } else {
                $this->_addFieldsElements($bridge);
            }
        } else {
            $this->_addFieldsElements($bridge);
        }
        // */

        // Token
        if ($this->token && $this->token->isCompleted()) {
            $bridge->addExhibitor('gto_valid_from');
            $bridge->addExhibitor('gto_valid_until');
        } else {
            $bridge->addDate(     'gto_valid_from');
            $bridge->addDate(     'gto_valid_until')
                ->addValidator(new MUtil_Validate_Date_DateAfter('gto_valid_from'));
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
        $model = $this->getModel();

        if (isset($this->formData['add_to_track']) && $this->formData['add_to_track']) {

            if (! isset($this->formData['to_existing_track'])) {
                throw new Gems_Exception_Coding('Add to track called without existing track.');
            }

            $tracker    = $this->loader->getTracker();
            $respTrack  = $tracker->getRespondentTrack($this->formData['to_existing_track']);
            $userId     = $this->loader->getCurrentUser()->getUserId();
            $tokenData  = array();
            $copyFields = array('gto_id_round', 'gto_valid_from', 'gto_valid_until', 'gto_comment');

            foreach ($copyFields as $name) {
                if (array_key_exists($name, $this->formData)) {
                    if ($model->hasOnSave($name)) {
                        $tokenData[$name] = $model->getOnSave($this->formData[$name], $this->createData, $name, $this->formData);
                    } else {
                        $tokenData[$name] = $this->formData[$name];
                    }
                } else {
                    $tokenData[$name] = null;
                }
            }
            $tokenData['gto_round_order']       = $respTrack->getLastToken()->getRoundOrder() + 10;
            $tokenData['gto_round_description'] = $this->_('Extra survey');

            if ($this->createData) {
                $surveyId = $this->formData['gto_id_survey'];

                $this->token   = $respTrack->addSurveyToTrack($surveyId, $tokenData, $userId);
                $this->tokenId = $this->token->getTokenId();

            } else {
                if (! $this->token) {
                    $this->token = $tracker->getToken($this->tokenId);
                }
                $oldTrack = $this->token->getRespondentTrack();

                // Save into the new track
                $respTrack->addTokenToTrack($this->token, $tokenData, $userId);

                // Should return empty array as the token is no longer in the
                // track
                if (! $oldTrack->getTokens(true)) {
                    $comment = sprintf(
                            $this->_('Token moved to %s track %d.'),
                            $respTrack->getTrackEngine()->getTrackName(),
                            $respTrack->getRespondentTrackId()
                            ) . "\n\n" . $oldTrack->getComment();

                    $code = $this->util->getReceptionCodeLibrary()->getStopString();

                    $oldTrack->setReceptionCode($code, $comment, $userId);
                }
            }

            $changed = 1;

        } else {
            if ($this->trackEngine) {
                // concatenate user input (gtf_field fields)
                // before the data is saved (the fields them
                $this->formData['gr2t_track_info'] = $this->trackEngine->calculateFieldsInfo($this->respondentTrackId, $this->formData);
            }

            // Perform the save
            $this->formData = $model->save($this->formData);
            $changed        = $model->getChanged();

            if ($this->createData) {
                $this->respondentTrackId = $this->formData['gr2t_id_respondent_track'];
                $this->tokenId           = $this->formData['gto_id_token'];
            }
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
            $this->afterSaveRouteUrl = array(
                $this->request->getActionKey() => $this->routeAction,
                MUtil_Model::REQUEST_ID => $this->tokenId,
                );
        }
        if (isset($this->formData['add_to_track']) && $this->formData['add_to_track']) {
            $this->afterSaveRouteUrl[$this->request->getControllerKey()] = 'track';
        }

        return $this;
    }
}