<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: InsertSurveySnippet.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Snippets\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 23-apr-2015 12:34:48
 */
class InsertSurveySnippet extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var Gems_Tracker_RespondentTrack Respondent Track
     */
    protected $respondentTrack;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show';

    /**
     *
     * @var \Gems_Tracker_Survey
     */
    protected $survey;

    /**
     *
     * @var array id => label
     */
    protected $surveyList;

    /**
     * The newly create token
     *
     * @var Gems_Tracker_Token
     */
    protected $token;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed $message_args Can be an array or multiple argemuents. Each sub element is a single message string
     * @return self (continuation pattern)
     */
    public function addMessage($message_args)
    {
        $this->saveButtonId = null;
        $this->saveLabel    = null;

        parent::addMessage(func_get_args());
    }
    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->saveLabel = $this->_('Insert survey');
        $this->tracker   = $this->loader->getTracker();
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->loader->getTracker()->getTokenModel();

        $dbLookup  = $this->util->getDbLookup();
        $trackData = $this->util->getTrackData();

        $this->surveyList = $trackData->getInsertableSurveys();

        $model->set('gr2o_patient_nr', 'elementClass', 'Exhibitor');
        $model->set('respondent_name', 'elementClass', 'Exhibitor');

        $model->set('gto_id_organization', 'label', $this->_('Organization'),
                'elementClass', 'Exhibitor',
                'multiOptions', $dbLookup->getOrganizationsWithRespondents()
                );
        $model->set('gto_id_survey', 'label', $this->_('Suvey to insert'),
                'multiOptions', $this->surveyList,
                'onchange', 'this.form.submit();'
                );
        $model->set('group_name', 'label', $this->_('Assigned to'),
                'elementClass', 'Exhibitor'
                );
        $model->set('gto_id_track', 'label', $this->_('Existing track'),
                'elementClass', 'Select',
                'onchange', 'this.form.submit();'
                );
        $model->set('gto_round_order', 'label', $this->_('In round'),
                'elementClass', 'Select',
                'required', true
                );
        $model->set('gto_valid_from',
                'elementClass', 'Date',
                'required', true
                );
        $model->set('gto_valid_until',
                'elementClass', 'Date'
                );
        $model->set('gto_comment',
                'elementClass', 'Textarea'
                );

        $model->addEditTracking();

        return $model;
    }

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            $this->_items = array(
                'gto_id_respondent',
                'gr2o_patient_nr',
                'respondent_name',
                'gto_id_organization',
                'gto_id_survey',
                'group_name',
                'gto_id_track',
                'gto_round_order',
                'gto_valid_from',
                'gto_valid_from_manual',
                'gto_valid_until',
                'gto_valid_until_manual',
                'gto_comment'
                );
            if (! $this->createData) {
                array_unshift($this->_items, 'gto_id_token');
            }
        }
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if ($this->createData && (! $this->request->isPost())) {
            $now            = new \MUtil_Date();
            $organizationId = $this->request->getParam(\MUtil_Model::REQUEST_ID2);
            $patientId      = $this->request->getParam(\MUtil_Model::REQUEST_ID1);
            $respondentData = $this->util->getDbLookup()->getRespondentIdAndName($patientId, $organizationId);

            $this->formData = array(
                'gr2o_patient_nr'        => $patientId,
                'gto_id_organization'    => $organizationId,
                'gto_id_respondent'      => $respondentData['id'],
                'respondent_name'        => $respondentData['name'],
                'gto_id_survey'          => $this->request->getParam(\Gems_Model::SURVEY_ID),
                'gto_id_track'           => $this->request->getParam(\Gems_Model::TRACK_ID),
                'gto_valid_from'         => $now,
                'gto_valid_from_manual'  => 0,
                'gto_valid_until_manual' => 0,
                );
        } else {
            parent::loadFormData();
        }

        $this->loadSurvey();
        $this->loadTrackSettings();
        $this->loadRoundSettings();

        // \MUtil_Echo::track($this->formData);
    }

    /**
     * Load the settings for the round
     */
    protected function loadRoundSettings()
    {
        $rounds = array();

        if ($this->respondentTrack instanceof \Gems_Tracker_RespondentTrack) {
            $hasAnswers = false;
            $lastOrder  = 10;
            $lastRound  = $this->respondentTrack->getFirstToken()->getRoundDescription();
            $lastToken  = null;
            foreach ($this->respondentTrack->getTokens() as $token)
            {
                if ($token instanceof \Gems_Tracker_Token) {
                    $descr = $token->getRoundDescription();
                    if ($descr != $lastRound) {
                        if ($lastToken) {
                            $order = $lastToken->getRoundOrder() + 1;
                        } else {
                            $order = $token->getRoundOrder() - 1;
                        }
                        $rounds[$order] = $lastRound;
                        if ($hasAnswers) {
                            $lastOrder = $order;
                        }
                        $hasAnswers = false;
                        $lastRound  = $descr;
                    }
                    $hasAnswers = $hasAnswers || $token->isCompleted();
                    $lastToken  = $token;
                }
            }
            $order = $lastToken->getRoundOrder() + 1;
            $rounds[$order] = $lastRound;
            if ($hasAnswers) {
                $lastOrder = $order;
            }
        }
        if (! $rounds) {
            $rounds    = array(10 => $this->_('Added survey'));
            $lastOrder = key($rounds);
        }
        $model = $this->getModel();
        $model->set('gto_round_order', 'multiOptions', $rounds, 'size', count($rounds));

        if (! isset($this->formData['gto_round_order'], $rounds[$this->formData['gto_round_order']])) {
            $this->formData['gto_round_order'] = $lastOrder;
        }

    }

    /**
     * Load the survey object and use it
     */
    protected function loadSurvey()
    {
        if (! $this->surveyList) {
            $this->addMessage($this->_('Survey insertion impossible: no insertable surveys exist!'));
        }
        if (count($this->surveyList ) === 1) {
            $model = $this->getModel();
            $model->set('gto_id_survey', 'elementClass', 'Exhibitor');

            reset($this->surveyList);
            $this->formData['gto_id_survey'] = key($this->surveyList);
        }

        if (isset($this->formData['gto_id_survey'])) {
            $this->survey = $this->tracker->getSurvey($this->formData['gto_id_survey']);

            $groupId = $this->survey->getGroupId();
            $groups  = $this->util->getDbLookup()->getGroups();
            if (isset($groups[$groupId])) {
                $this->formData['group_name'] = $groups[$groupId];
            }

            $then = new \MUtil_Date();
            $then->addMonth(6);
            $this->formData['gto_valid_until'] = $then;
        }
    }

    /**
     * Load the settings for the survey
     */
    protected function loadTrackSettings()
    {
        $respTracks = $this->tracker->getRespondentTracks(
                $this->formData['gto_id_respondent'],
                $this->formData['gto_id_organization']
                );
        $tracks = array();
        foreach ($respTracks as $respTrack) {
            if ($respTrack instanceof \Gems_Tracker_RespondentTrack) {
                if ($respTrack->hasSuccesCode()) {
                    $tracks[$respTrack->getRespondentTrackId()] = substr(sprintf(
                            $this->_('%s - %s'),
                            $respTrack->getTrackEngine()->getTrackName(),
                            $respTrack->getFieldsInfo()
                            ), 0, 100);
                }
            }
        }
        if ($tracks) {
            if (! isset($this->formData['gto_id_track'])) {
                reset($tracks);
                $this->formData['gto_id_track'] = key($tracks);
            }
        } else {
            $this->addMessage($this->_('Survey insertion impossible: no tracks exist for respondent.'));
            $tracks = $this->util->getTranslated()->getEmptyDropdownArray();
        }
        asort($tracks);
        $model = $this->getModel();
        $model->set('gto_id_track', 'multiOptions', $tracks);
        if (count($tracks) === 1) {
            $model->set('gto_id_track', 'elementClass', 'Exhibitor');
        }

        if (isset($this->formData['gto_id_track'])) {
            $this->respondentTrack = $respTracks[$this->formData['gto_id_track']];
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

        $userId     = $this->loader->getCurrentUser()->getUserId();
        $tokenData  = array();
        $copyFields = array(
            'gto_id_round',
            'gto_valid_from',
            'gto_valid_from_manual',
            'gto_valid_until',
            'gto_valid_until_manual',
            'gto_comment',
            );

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
        $rounds = $model->get('gto_round_order', 'multiOptions');
        $tokenData['gto_id_round']          = '0';
        $tokenData['gto_round_order']       = $this->formData['gto_round_order'];
        $tokenData['gto_round_description'] = $rounds[$this->formData['gto_round_order']];

        $surveyId = $this->formData['gto_id_survey'];

        $this->token = $this->respondentTrack->addSurveyToTrack($surveyId, $tokenData, $userId);

        $changed = 1;

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
                $this->request->getControllerKey() => 'track',
                $this->request->getActionKey()     => $this->routeAction,
                \MUtil_Model::REQUEST_ID           => $this->token->getTokenId(),
                );
        }

        return $this;
    }
}
