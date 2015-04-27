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
     * True when the form should edit a new model item.
     *
     * @var boolean
     */
    protected $createData = true;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var int
     */
    protected $defaultRound = 10;

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
    public function addMessageInvalid($message_args)
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

        if ($model instanceof \Gems_Tracker_Model_StandardTokenModel) {
            $model->addEditTracking();

            if ($this->createData) {
                $model->applyInsertionFormatting();
            }
        }

        $trackData = $this->util->getTrackData();

        $this->surveyList = $trackData->getInsertableSurveys();

        $model->set('gto_id_survey',   'label', $this->_('Suvey to insert'),
                // 'elementClass' set in loadSurvey
                'multiOptions', $this->surveyList,
                'onchange', 'this.form.submit();'
                );
        $model->set('gto_id_track',    'label', $this->_('Existing track'),
                'elementClass', 'Select',
                //'multiOptions' set in loadTrackSettings
                'onchange', 'this.form.submit();'
                );
        $model->set('gto_round_order', 'label', $this->_('In round'),
                'elementClass', 'Select',
                //'multiOptions' set in loadRoundSettings
                'required', true
                );
        $model->set('gto_valid_from',
                'required', true
                );

        return $model;
    }

    /**
     * Get a select with the fields:
     *  - round_order: The gto_round_order to use for this round
     *  - has_group: True when has surveys for same group as current survey
     *  - group_answered: True when has answers for same group as current survey
     *  - any_answered: True when has answers for any survey
     *  - round_description: The gto_round_description for the round
     *
     * @return \Zend_Db_Select or you can return a nested array containing said output/
     */
    protected function getRoundSelect()
    {
        $select = $this->db->select();

        $select->from('gems__tokens', array('gto_round_description AS round_description'))
                ->joinInner('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array())
                ->joinInner('gems__surveys', 'gto_id_survey = gsu_id_survey', array())
                ->where('grc_success = 1')
                ->group('gto_round_description');

        if ($this->survey instanceof \Gems_Tracker_Survey) {
            $groupId = $this->survey->getGroupId();
            $select->columns(array(
                // Round order is maximum for the survey's group unless this round had no surveys of the same group
                'round_order'    => new \Zend_Db_Expr(
                        "COALESCE(
                            MAX(CASE WHEN gsu_id_primary_group = $groupId THEN gto_round_order ELSE NULL END),
                            MAX(gto_round_order)
                            ) + 1"
                        ),
                'has_group'      => new \Zend_Db_Expr(
                        "SUM(CASE WHEN gsu_id_primary_group = $groupId THEN 1 ELSE 0 END)"
                        ),
                'group_answered' => new \Zend_Db_Expr(
                        "SUM(CASE WHEN gto_completion_time IS NOT NULL AND gsu_id_primary_group = $groupId
                            THEN 1
                            ELSE 0
                            END)"
                        ),
                'any_answered'   => new \Zend_Db_Expr(
                        "SUM(CASE WHEN gto_completion_time IS NOT NULL THEN 1 ELSE 0 END)"
                        ),
            ));
        } else {
            $select->columns(array(
                'round_order'    => new \Zend_Db_Expr("MAX(gto_round_order)+ 1"),
                'has_group'      => new \Zend_Db_Expr("0"),
                'group_answered' => new \Zend_Db_Expr("0"),
                'any_answered'   => new \Zend_Db_Expr(
                        "SUM(CASE WHEN gto_completion_time IS NOT NULL THEN 1 ELSE 0 END)"
                        ),
            ));
        }

        if (isset($this->formData['gto_id_track'])) {
            $select->where('gto_id_respondent_track = ?', $this->formData['gto_id_track']);
        } else {
            $select->where('1=0');
        }

        $select->order('round_order');

        return $select;
    }

    /**
     * Get the list of rounds and set the default
     *
     * @return array [roundInsertNr => RoundDescription
     */
    protected function getRoundsListAndSetDefault()
    {
        $output = array();
        $select = $this->getRoundSelect();

        if ($select instanceof \Zend_Db_Select) {
            $rows = $this->db->fetchAll($select);
        } else {
            $rows = $select;
        }

        if ($rows) {

            // Initial values
            $maxAnswered      = 0;
            $maxGroup         = 0;
            $maxGroupAnswered = 0;

            foreach ($rows as $row) {
                $output[$row['round_order']] = $row['round_description'];

                if ($row['has_group']) {
                    $maxGroup = $row['round_order'];
                    if ($row['group_answered']) {
                        $maxGroupAnswered = $row['round_order'];
                    }
                }
                if ($row['any_answered']) {
                    $maxAnswered = $row['round_order'];
                }
            }
            if ($maxGroupAnswered) {
                $this->defaultRound = $maxGroupAnswered;
            } elseif ($maxAnswered) {
                $this->defaultRound = $maxAnswered;
            } elseif ($maxGroup) {
                $this->defaultRound = $maxAnswered;
            } else {
                $row = reset($rows);
                $this->defaultRound = $row['round_order'];
            }

        } else {
            $output[10] = $this->_('Added survey');
            $this->defaultRound = 10;
        }

        return $output;
    }

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            $this->_items = array_merge(
                    array(
                        'gto_id_respondent',
                        'gr2o_patient_nr',
                        'respondent_name',
                        'gto_id_organization',
                        'gto_id_survey',
                        'ggp_name',
                        'gto_id_track',
                        'gto_round_order',
                        'gto_valid_from_manual',
                        'gto_valid_from',
                        'gto_valid_until_manual',
                        'gto_valid_until',
                        'gto_comment',
                        ),
                    $this->getModel()->getMeta(\MUtil_Model_Type_ChangeTracker::HIDDEN_FIELDS, array())
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
                'gto_valid_from_manual'  => 0,
                'gto_valid_from'         => $now,
                'gto_valid_until_manual' => 0,
                'gto_valid_until'        => null, // Set in loadSurvey
                );

            $this->getModel()->processAfterLoad(array($this->formData), $this->createData, false);
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
        $rounds = $this->getRoundsListAndSetDefault();
        $model  = $this->getModel();
        $model->set('gto_round_order', 'multiOptions', $rounds, 'size', count($rounds));

        if (! isset($this->formData['gto_round_order'], $rounds[$this->formData['gto_round_order']])) {
            $this->formData['gto_round_order'] = $this->defaultRound;
        }
    }

    /**
     * Load the survey object and use it
     */
    protected function loadSurvey()
    {
        if (! $this->surveyList) {
            $this->addMessageInvalid($this->_('Survey insertion impossible: no insertable surveys exist!'));
        }
        if (count($this->surveyList) === 1) {
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
                $this->formData['ggp_name'] = $groups[$groupId];
            }

            $this->formData['gto_valid_until'] = $this->survey->getInsertDateUntil($this->formData['gto_valid_from']);
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
            $this->addMessageInvalid($this->_('Survey insertion impossible: respondent has no tracks!'));
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
