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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Controller for editing respondent tracks, including their tokens
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_TrackAction extends \Gems_Default_RespondentChildActionAbstract
{
    /**
     * The parameters used for the answer export action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $answerExportParameters = array(
        'formTitle' => 'getTokenTitle',
        'hideGroup' => true,
    );

    /**
     * This snippets for answer export
     *
     * @var mixed String or array of snippets name
     */
    protected $answerExportSnippets = array('Export\\RespondentExportSnippet');

    /**
     * The parameters used for the answers action.
     *
     * Currently filled from $defaultTokenParameters
     */
    protected $answerParameters = array();

    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'extraFilter'     => 'getRespondentFilter',
        'menuEditActions' => array('edit-track'),
        'menuShowActions' => array('show-track'),
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = array(
        'ModelTableSnippetGeneric',
        'Tracker\\Buttons\\TrackIndexButtonRow',
        'Tracker\\AvailableTracksSnippet',
        );

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createParameters = array(
        'createData'  => true,
        'formTitle'   => 'getCreateTrackTitle',
        'multiTracks' => 'isMultiTracks',
        'trackEngine' => 'getTrackEngine',
        );

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected $createSnippets = array(
        'Tracker\\TrackUsageOverviewSnippet',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Tracker\\EditTrackSnippet',
        'Tracker\\TrackSurveyOverviewSnippet',
        );

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The default parameters used for any token action like answers or sho0w
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $defaultTokenParameters = array(
        'model'      => null,
        'respondent' => null,
        'token'      => 'getToken',
        'tokenId'    => 'getTokenId',
    );

    /**
     * The parameters used for the delete action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deleteParameters = array(
        'formTitle'     => null,
        'topicCallable' => 'getTokenTopicCallable',
    );

    /**
     * The parameters used for the edit track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deleteTrackParameters = array(
        'formTitle'         => null,
        'multiTracks'       => 'isMultiTracks',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'topicCallable'     => 'getTopicCallable',
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    );

    /**
     * Snippets for deleting tracks
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteTrackSnippets = array(
        'Tracker\\DeleteTrackSnippet',
        'Tracker\\TrackTokenOverviewSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
        );

    /**
     * The parameters used for the edit track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $editTrackParameters = array(
        'createData'        => false,
        'formTitle'         => 'getTrackTitle',
        'multiTracks'       => 'isMultiTracks',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    );

    /**
     * Snippets for editing tracks
     *
     * @var mixed String or array of snippets name
     */
    protected $editTrackSnippets = array(
        'Tracker\\EditTrackSnippet',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Tracker\\TrackTokenOverviewSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
        );

    /**
     * The parameters used for the email action.
     *
     * Currently mostly filled from $defaultTokenParameters
     */
    protected $emailParameters = array(
        'formTitle'    => 'getEmailTokenTitle',
        'identifier'   => '_getIdParam',
        'mailTarget'   => 'token',
        // 'model'        => 'getModel',
        'routeAction'  => 'show',
        'templateOnly' => 'isTemplateOnly',
        'view'         => 'getView',
    );

    /**
     * Snippets used for emailing
     *
     * @var mixed String or array of snippets name
     */
    protected $emailSnippets = array('Mail_TokenMailFormSnippet');

    /**
     * The parameters used for the export track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $exportTrackParameters = array(
        'formTitle'         => 'getTrackTitle',
        'respondentTrack'   => 'getRespondentTrack',
    );

    /**
     * This snippets for track export
     *
     * @var mixed String or array of snippets name
     */
    protected $exportTrackSnippets = array('Export\\RespondentExportSnippet');

    /**
     * The parameters used for the insert action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $insertParameters = array(
        'createData' => true,
        'formTitle'  => 'getInsertInTrackTitle',
        'model'      => null,
        'surveyId'   => 'getSurveyId',
        );

    /**
     * Snippets used for inserting a survey
     *
     * @var mixed String or array of snippets name
     */
    protected $insertSnippets = array('Tracker\\InsertSurveySnippet');

    /**
     * The parameters used for the questions action.
     *
     * Currently mostly filled from $defaultTokenParameters
     */
    protected $questionsParameters = array(
        'surveyId' => 'getSurveyId',
    );

    /**
     * Snippets used for showing survey questions
     *
     * @var mixed String or array of snippets name
     */
    protected $questionsSnippets = array(
        'Survey\\SurveyQuestionsSnippet',
        'Tracker\\Buttons\\TokenActionButtonRow',
        );

    /**
     * The parameters used for the edit track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showTrackParameters = array(
        'contentTitle'      => 'getTrackTitle',
        'multiTracks'       => 'isMultiTracks',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'displayMenu'       => false,
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    );

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected $showTrackSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Tracker\\SingleSurveyAvailableTracksSnippet',
        'ModelItemTableSnippetGeneric',
        'Tracker\\Buttons\\TrackActionButtonRow',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Tracker\\TrackTokenOverviewSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
        );

    /**
     * Array of the actions that use a summarized version of the model.
     *
     * This determines the value of $detailed in createAction(). As it is usually
     * less of a problem to use a $detailed model with an action that should use
     * a summarized model and I guess there will usually be more detailed actions
     * than summarized ones it seems less work to specify these.
     *
     * @var array $summarizedActions Array of the actions that use a
     * summarized version of the model.
     */
    public $summarizedActions = array('index', 'autofilter', 'create', 'view', 'view-survey');

    /**
     * The actions that should result in the survey return being set.
     *
     * @var array
     */
    protected $tokenReturnActions = array(
        'index',
        'show',
        'show-track',
    );

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $viewParameters = array(
        'contentTitle' => 'getViewTrackTitle',
        'multiTracks'  => 'isMultiTracks',
        'trackEngine'  => 'getTrackEngine',
        'trackId'      => 'getTrackId',
        );

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected $viewSnippets = array(
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
        'Tracker\\Buttons\\TrackActionButtonRow',
        'Tracker\\TrackSurveyOverviewSnippet',
        );
    
    /**
     * The parameters used for the viewSurveys action.
     */
    protected $viewSurveyParameters = array(
        'surveyId' => 'getSurveyId',
    );
    
    /**
     * Snippets used for showing survey questions
     *
     * @var mixed String or array of snippets name
     */
    protected $viewSurveySnippets = array(
        'Survey\\SurveyQuestionsSnippet'
        );

    /**
     * Pops the answers to a survey in a separate window
     */
    public function answerAction()
    {
        // Set menu OFF
        $this->menu->setVisible(false);

        $token    = $this->getToken();
        $snippets = $token->getAnswerSnippetNames();

        if ($snippets) {
            $this->setTitle(sprintf($this->_('Token answers: %s'), strtoupper($token->getTokenId())));

            $params = $this->_processParameters($this->answerParameters + $this->defaultTokenParameters);

            $this->addSnippets($snippets, $params);
        }
    }

    /**
     * Export a single token
     */
    public function answerExportAction()
    {
        if ($this->answerExportSnippets) {
            $params = $this->_processParameters($this->answerExportParameters + $this->defaultTokenParameters);

            $this->addSnippets($this->answerExportSnippets, $params);
        }
    }

    /**
     * Action for showing a create new item page
     *
     * Uses separate createSnippets instead of createEditSnipppets
     */
    public function createAction()
    {
        if (! $this->isMultiTracks()) {
            // Fix for double pressing of create button
            $request = $this->getRequest();
            $model   = $this->getModel();

            $model->setFilter(array()) // First clear existing filter
                    ->applyRequest($request);
            $data = $model->loadFirst();

            if ($data) {
                $this->_reroute(array($request->getActionKey() => 'edit-track'));
                return;
            }
        }

        if ($this->createSnippets) {
            $params = $this->_processParameters($this->createParameters + $this->createEditParameters);

            $this->addSnippets($this->createSnippets, $params);
        }
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $apply = true;
        $model = $this->loader->getTracker()->getRespondentTrackModel();
        if ($detailed) {
            $engine = $this->getTrackEngine();

            if ($engine) {
                switch ($action) {
                    case 'export-track':
                    case 'show-track':
                        $model->applyDetailSettings($engine);
                        break;

                    default:
                        $model->applyEditSettings($engine);
                        break;
                }

                $apply = false;
            }
        }
        if ($apply) {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Delete a single token
     */
    public function deleteAction()
    {
        $this->deleteParameters = $this->deleteParameters + $this->defaultTokenParameters;
        $this->deleteSnippets   = $this->getToken()->getDeleteSnippetNames();

        parent::deleteAction();
    }

    /**
     * Delete a track
     */
    public function deleteTrackAction()
    {
        if ($this->deleteTrackSnippets) {
            $params = $this->_processParameters($this->deleteTrackParameters + $this->deleteParameters);

            $this->addSnippets($this->deleteTrackSnippets, $params);
        }
    }

    /**
     * Edit single token
     */
    public function editAction()
    {
        $this->editParameters      = $this->editParameters + $this->defaultTokenParameters;
        $this->createEditSnippets  = $this->getToken()->getEditSnippetNames();

        parent::editAction();
    }

    /**
     * Edit the respondent track data
     */
    public function editTrackAction()
    {
        if ($this->editTrackSnippets) {
            $params = $this->_processParameters($this->editTrackParameters + $this->createEditParameters);

            $this->addSnippets($this->editTrackSnippets, $params);
        }
    }

    /**
     * Email the user
     */
    public function emailAction()
    {
        if ($this->emailSnippets) {
            $params = $this->_processParameters($this->emailParameters + $this->defaultTokenParameters);

            $this->addSnippets($this->emailSnippets, $params);
        }
    }

    /**
     * Export a single track
     */
    public function exportTrackAction()
    {
        if ($this->exportTrackSnippets) {
            $params = $this->_processParameters($this->exportTrackParameters);

            $this->addSnippets($this->exportTrackSnippets, $params);
        }
    }

    /**
     * Get the title for creating a track
     *
     * @return string
     */
    protected function getCreateTrackTitle()
    {
        $respondent = $this->getRespondent();

        return sprintf(
                $this->_('Adding the %s track to respondent %s: %s'),
                $this->getTrackEngine()->getTrackName(),
                $respondent->getPatientNumber(),
                $respondent->getFullName()
                );
    }

    /**
     * Get the title describing the track
     *
     * @return string
     */
    protected function getTrackTitle()
    {
        $respondent  = $this->getRespondent();
        $respTrack   = $this->getRespondentTrack();
        if ($respTrack) {
            $trackEngine = $respTrack->getTrackEngine();

            // Set params
            return sprintf(
                    $this->_('%s track for respondent nr %s: %s'),
                    $trackEngine->getTrackName(),
                    $respondent->getPatientNumber(),
                    $respondent->getName()
                    );
        }
    }

    /**
     * Get the title describing the token
     *
     * @return string
     */
    protected function getTokenTitle()
    {
        $token      = $this->getToken();
        $respondent = $token->getRespondent();

        // Set params
        return sprintf(
                $this->_('Token %s in round "%s" in track "%s" for respondent nr %s: %s'),
                $token->getTokenId(),
                $token->getRoundDescription(),
                $token->getTrackName(),
                $respondent->getPatientNumber(),
                $respondent->getName()
                );
    }

    /**
     * Get the title for editing a track
     *
     * @return string
     */
    protected function getEmailTokenTitle()
    {
        $token      = $this->getToken();
        $respondent = $token->getRespondent();

        // Set params
        return sprintf(
                $this->_('Send mail to %s respondent nr %s for token %s'),
                $respondent->getEmailAddress(),
                $respondent->getPatientNumber(),
                $token->getTokenId()
                );
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        $respondent = $this->getRespondent();

        return sprintf(
                $this->_('Tracks assigned to %s: %s'),
                $respondent->getPatientNumber(),
                $respondent->getFullName()
                );
    }

    /**
     * Get the title for creating a track
     *
     * @return string
     */
    protected function getInsertInTrackTitle()
    {
        $respondent = $this->getRespondent();

        return sprintf(
                $this->_('Inserting a survey in a track for respondent %s: %s'),
                $respondent->getPatientNumber(),
                $respondent->getFullName()
                );
    }

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getRespondentFilter()
    {
        $respondent = $this->getRespondent();
        return array(
            'gr2t_id_user'         => $respondent->getId(),
            'gr2t_id_organization' => $respondent->getOrganizationId(),
            );
    }

    /**
     * Retrieve the respondent track
     * (So we don't need to repeat that for every snippet.)
     *
     * @return \Gems_Tracker_RespondentTrack
     */
    public function getRespondentTrack()
    {
        static $respTrack;

        if ($respTrack instanceof \Gems_Tracker_RespondentTrack) {
            return $respTrack;
        }

        $respTrackId = $this->_getParam(\Gems_Model::RESPONDENT_TRACK);
        $tracker     = $this->loader->getTracker();

        if ($respTrackId) {
            $respTrack = $tracker->getRespondentTrack($respTrackId);
        } else {
            if ($this->isMultiTracks()) {
                throw new \Gems_Exception($this->_('No track specified for respondent!'));
            }

            $respondent = $this->getRespondent();
            $respTracks = $tracker->getRespondentTracks(
                    $respondent->getId(),
                    $respondent->getOrganizationId(),
                    array('grc_success DESC', 'gr2t_start_date')
                    );
            $trackId = $this->escort->getTrackId();
            if ($trackId) {
                foreach ($respTracks as $respTrack) {
                    if ($respTrack instanceof \Gems_Tracker_RespondentTrack) {
                        if ($trackId == $respTrack->getTrackId()) {
                            // Return the right track if it exists
                            break;
                        }
                    }
                }
            } else {
                $respTrack = reset($respTracks);
            }
        }
        if (! $respTrack instanceof \Gems_Tracker_RespondentTrack) {
            if ($this->isMultiTracks()) {
                throw new \Gems_Exception($this->_('No track found for respondent!'));
            } else {
                $this->menu->getParameterSource()->offsetSet('track_can_be_created', 1);
                return null;
            }
        }

        $respTrack->applyToMenuSource($this->menu->getParameterSource());

        // Otherwise return the last created track (yeah some implementations are not correct!)
        return $respTrack;
    }

    /**
     * Retrieve the respondent track ID
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentTrackId()
    {
        $respTrack = $this->getRespondentTrack();
        if ($respTrack) {
            return $respTrack->getRespondentTrackId();
        }
    }

    /**
     * Retrieve the survey ID
     *
     * @return int
     */
    public function getSurveyId()
    {
        $sid = $this->_getParam(\Gems_Model::SURVEY_ID);
        if ($sid) {
            return $sid;
        }
        if ($this->getTokenId()) {
            return $this->getToken()->getSurveyId();
        }
    }

    /**
     * Retrieve the token
     *
     * @return \Gems_Tracker_Token
     */
    public function getToken()
    {
        static $token;

        if ($token instanceof \Gems_Tracker_Token) {
            return $token;
        }

        $token   = null;
        $tokenId = $this->getTokenId();

        if ($tokenId) {
            $token = $this->loader->getTracker()->getToken($tokenId);
        }
        if ($token && $token->exists) {
            // Set variables for the menu
            $token->applyToMenuSource($this->menu->getParameterSource());

            return $token;
        }

        throw new \Gems_Exception($this->_('No existing token specified!'));
    }

    /**
     * Retrieve the token ID
     *
     * @return string
     */
    public function getTokenId()
    {
        return $this->_getIdParam();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTokenTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);;
    }

    /**
     *
     * @return callable Get the getTokenTopic function as a callable
     */
    public function getTokenTopicCallable()
    {
        return array($this, 'getTokenTopic');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);;
    }

    /**
     * Retrieve the track engine
     *
     * @return \Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngine()
    {
        static $engine;

        if ($engine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
            return $engine;
        }

        try {
            $respTrack = $this->getRespondentTrack();
            if ($respTrack instanceof \Gems_Tracker_RespondentTrack) {
                $engine = $respTrack->getTrackEngine();
            }

        } catch (\Exception $ex) {
        }

        if (! $engine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
            $trackId = $this->_getParam(\Gems_model::TRACK_ID);

            if (! $trackId) {
                if ($this->isMultiTracks()) {
                    throw new \Gems_Exception($this->_('No track engine specified!'));
                }

                $trackId = $this->escort->getTrackId();

                if (! $trackId) {
                    return null;
                }
            }

            $engine = $this->loader->getTracker()->getTrackEngine($trackId);
        }
        $engine->applyToMenuSource($this->menu->getParameterSource());

        return $engine;
    }

    /**
     * Retrieve the track ID
     *
     * @return int
     */
    public function getTrackId()
    {
        $trackEngine = $this->getTrackEngine();
        if ($trackEngine) {
            return $trackEngine->getTrackId();
        }
    }

    /**
     * Get the view
     *
     * @return \Zend_View_Interface
     */
    protected function getView()
    {
        return $this->view;
    }

    /**
     * Get the title for viewing track usage
     *
     * @return string
     */
    protected function getViewTrackTitle()
    {
        $trackEngine = $this->getTrackEngine();

        if ($this->isMultiTracks()) {
            $respondent = $this->getRespondent();

            // Set params
            return sprintf(
                    $this->_('%s track assignments for respondent nr %s: %s'),
                    $trackEngine->getTrackName(),
                    $this->_getParam(\MUtil_Model::REQUEST_ID1),
                    $this->getRespondent()->getFullName()
                    );
        } else {
            return sprintf(
                    $this->_('%s track overview'),
                    $trackEngine->getTrackName()
                    );
        }
    }

    /**
     * Initialize translate and html objects
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $request = $this->getRequest();

        if (in_array($request->getActionName(), $this->tokenReturnActions)) {
            // Tell the system where to return to after a survey has been taken
            $this->currentUser->setSurveyReturn($request);
        }
    }

    /**
     * Insert a single survey into a track
     */
    public function insertAction()
    {
        if ($this->insertSnippets) {
            $params = $this->_processParameters($this->insertParameters);

            $this->addSnippets($this->insertSnippets, $params);
        }
    }

    /**
     *
     * @return boolean
     */
    protected function isMultiTracks()
    {
        return ! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;
    }

    /**
     *
     * @return boolean
     */
    protected function isTemplateOnly()
    {
        return ! $this->currentUser->hasPrivilege('pr.token.mail.freetext');
    }

    /**
     * Pops a PDF - if it exists
     */
    public function pdfAction()
    {
        // Make sure nothing else is output
        $this->initRawOutput();

        // Output the PDF
        $this->loader->getPdf()->echoPdfByTokenId($this->_getIdParam());
    }

    /**
     * Shows the questions in a survey
     */
    public function questionsAction()
    {
        if (!$this->getTokenId()) {
            $params = $this->_processParameters($this->questionsParameters);
        } else {
            $params = $this->_processParameters($this->questionsParameters + $this->defaultTokenParameters);
        }
        if ($this->questionsSnippets) {
            $params = $this->_processParameters($this->questionsParameters + $this->defaultTokenParameters);

            $this->addSnippets($this->questionsSnippets, $params);
        }
    }

    /**
     * Show a single token, mind you: it can be a SingleSurveyTrack
     */
    public function showAction()
    {
        $this->showParameters = $this->showParameters + $this->defaultTokenParameters;
        $this->showSnippets   = $this->getToken()->getShowSnippetNames();

        parent::showAction();
    }

    /**
     * Show information on a single track assigned to a respondent
     */
    public function showTrackAction()
    {
        if ($this->showTrackSnippets) {
            $params = $this->_processParameters($this->showTrackParameters);

            $this->addSnippets($this->showTrackSnippets, $params);
        }
    }

     /**
     * Delete a single token
     */
    public function undeleteAction()
    {
        $this->deleteParameters = $this->deleteParameters + $this->defaultTokenParameters;
        $this->deleteSnippets   = $this->getToken()->getDeleteSnippetNames();

        parent::deleteAction();
    }

    /**
     * Undelete a track
     */
    public function undeleteTrackAction()
    {
        if ($this->deleteTrackSnippets) {
            $params = $this->_processParameters($this->deleteTrackParameters + $this->deleteParameters);

            $this->addSnippets($this->deleteTrackSnippets, $params);
        }
    }

   /**
     * Show information on a single track type assigned to a respondent
     */
    public function viewAction()
    {
        if ($this->viewSnippets) {
            $params = $this->_processParameters($this->viewParameters);

            $this->addSnippets($this->viewSnippets, $params);
        }
    }
    
    /**
     * Used in AddTracksSnippet to show a preview for an insertable survey
     */
    public function viewSurveyAction()
    {
        $params = $this->_processParameters($this->viewSurveyParameters);
        $this->addSnippets($this->viewSurveySnippets, $params);
    }
}