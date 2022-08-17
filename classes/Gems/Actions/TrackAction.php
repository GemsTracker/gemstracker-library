<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 * Controller for editing respondent tracks, including their tokens
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class TrackAction extends \Gems\Actions\RespondentChildActionAbstract
{
    /**
     *
     * @var \Gems\AccessLog
     */
    public $accesslog;

    /**
     * The parameters used for the answer export action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $answerExportParameters = [
        'formTitle' => 'getTokenTitle',
        'hideGroup' => true,
    ];

    /**
     * This snippets for answer export
     *
     * @var mixed String or array of snippets name
     */
    protected $answerExportSnippets = ['Export\\RespondentExportSnippet'];

    /**
     * The parameters used for the answers action.
     *
     * Currently filled from $defaultTokenParameters
     */
    protected $answerParameters = [];

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
    protected $autofilterParameters = [
        'extraFilter'     => 'getRespondentFilter',
        'extraSort'       => ['gr2t_start_date' => SORT_DESC],
        'menuEditActions' => ['edit-track'],
        'menuShowActions' => ['show-track'],
        'respondent'      => 'getRespondent',
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = [
        'Tracker\\TrackTableSnippet',
        //'Tracker\\Buttons\\TrackIndexButtonRow',
        'Tracker\\AvailableTracksSnippet',
    ];

    /**
     * The parameters used for the check token action. The $defaultTokenParameters are added to these parameters
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $checkTokenParameters = [];

    /**
     * Snippets for the check token actions
     *
     * @var mixed String or array of snippets name
     */
    protected $checkTokenSnippets = [
        'Token\\CheckTokenEvents',
        'Survey\\SurveyQuestionsSnippet'
    ];

    /**
     * The parameters used for the correct action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $correctParameters = [
        'fixedReceptionCode' => 'redo',
        'formTitle'          => 'getCorrectTokenTitle',
    ];

    /**
     * The parameters used for the create actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createParameters = [
        'createData'  => true,
        'formTitle'   => 'getCreateTrackTitle',
        'multiTracks' => 'isMultiTracks',
        'trackEngine' => 'getTrackEngine',
    ];

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected $createSnippets = [
        'Tracker\\TrackUsageOverviewSnippet',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Tracker\\EditTrackSnippet',
        'Tracker\\TrackSurveyOverviewSnippet',
    ];

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

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
    protected $defaultTokenParameters = [
        'model'      => null,
        'respondent' => null,
        'token'      => 'getToken',
        'tokenId'    => 'getTokenId',
    ];

    /**
     * The parameters used for the delete action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialisation
     */
    protected $deleteParameters = [
        'formTitle'     => null,
        'topicCallable' => 'getTokenTopicCallable',
    ];

    /**
     * The parameters used for the edit track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deleteTrackParameters = [
        'formTitle'         => null,
        'multiTracks'       => 'isMultiTracks',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'topicCallable'     => 'getTopicCallable',
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    ];

    /**
     * Snippets for deleting tracks
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteTrackSnippets = [
        'Tracker\\DeleteTrackSnippet',
        'Tracker\\TrackTokenOverviewSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
    ];

    /**
     * Parameters for editing a track token
     *
     * @var mixed String or array of snippets name
     */
    protected $editParameters = [
        'formTitle'     => null,
        'topicCallable' => 'getTokenTopicCallable',
    ];


    /**
     * The parameters used for the edit track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $editTrackParameters = [
        'createData'        => false,
        'formTitle'         => 'getTrackTitle',
        'multiTracks'       => 'isMultiTracks',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    ];

    /**
     * Snippets for editing tracks
     *
     * @var mixed String or array of snippets name
     */
    protected $editTrackSnippets = [
        'Tracker\\EditTrackSnippet',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Tracker\\TrackTokenOverviewSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
    ];

    /**
     * The parameters used for the email action.
     *
     * Currently mostly filled from $defaultTokenParameters
     */
    protected $emailParameters = [
        'formTitle'    => 'getEmailTokenTitle',
        'identifier'   => '_getIdParam',
        'mailTarget'   => 'token',
        // 'model'        => 'getModel',
        'routeAction'  => 'show',
        'templateOnly' => 'isTemplateOnly',
        'view'         => 'getView',
    ];

    /**
     * Snippets used for emailing
     *
     * @var mixed String or array of snippets name
     */
    protected $emailSnippets = ['Mail\\TokenMailFormSnippet'];

    /**
     * The parameters used for the export track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $exportTrackParameters = [
        'formTitle'         => 'getTrackTitle',
        'respondentTrack'   => 'getRespondentTrack',
    ];

    /**
     * This snippets for track export
     *
     * @var mixed String or array of snippets name
     */
    protected $exportTrackSnippets = ['Export\\RespondentExportSnippet'];

    /**
     * The parameters used for the insert action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $insertParameters = [
        'createData' => true,
        'formTitle'  => 'getInsertInTrackTitle',
        'model'      => null,
    ];

    /**
     * Snippets used for inserting a survey
     *
     * @var mixed String or array of snippets name
     */
    protected $insertSnippets = ['Tracker\\InsertSurveySnippet'];

    /**
     * The parameters used for the questions action.
     *
     * Currently mostly filled from $defaultTokenParameters
     */
    protected $questionsParameters = [
        'surveyId' => 'getSurveyId',
    ];

    /**
     * Snippets used for showing survey questions
     *
     * @var mixed String or array of snippets name
     */
    protected $questionsSnippets = [
        'Survey\\SurveyQuestionsSnippet',
        'Tracker\\Buttons\\TokenActionButtonRow',
    ];

    /**
     * The parameters used for the edit track action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showTrackParameters = [
        'contentTitle'      => 'getTrackTitle',
        'extraFilter'       => 'getNoRespondentFilter',
        'multiTracks'       => 'isMultiTracks',
        'respondentTrack'   => 'getRespondentTrack',
        'respondentTrackId' => 'getRespondentTrackId',
        'displayMenu'       => false,
        'trackEngine'       => 'getTrackEngine',
        'trackId'           => 'getTrackId',
    ];

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected $showTrackSnippets = [
        'Generic\\ContentTitleSnippet',
        'Tracker\\SingleSurveyAvailableTracksSnippet',
        'ModelItemTableSnippetGeneric',
        'Tracker\\Buttons\\TrackActionButtonRow',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Tracker\\TrackTokenOverviewSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
    ];

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
    public $summarizedActions = ['index', 'autofilter', 'create', 'view', 'view-survey'];

    /**
     * The actions that should result in the survey return being set.
     *
     * @var array
     */
    protected $tokenReturnActions = [
        'index',
        'show',
        'show-track',
    ];

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
    protected $viewParameters = [
        'contentTitle' => 'getViewTrackTitle',
        'multiTracks'  => 'isMultiTracks',
        'trackEngine'  => 'getTrackEngine',
        'trackId'      => 'getTrackId',
    ];

    /**
     * This action uses a different snippet order during create
     *
     * @var mixed String or array of snippets name
     */
    protected $viewSnippets = [
        'Tracker\\TrackUsageTextDetailsSnippet',
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackUsageOverviewSnippet',
        'Tracker\\Buttons\\TrackActionButtonRow',
        'Tracker\\TrackSurveyOverviewSnippet',
    ];

    /**
     * The parameters used for the viewSurveys action.
     */
    protected $viewSurveyParameters = [
        'surveyId' => 'getSurveyId',
    ];

    /**
     * Snippets used for showing survey questions
     *
     * @var mixed String or array of snippets name
     */
    protected $viewSurveySnippets = [
        'Survey\\SurveyQuestionsSnippet'
    ];

    /**
     * Pops the answers to a survey in a separate window
     */
    public function answerAction()
    {
        // Set menu OFF
        $this->menu->setVisible(false);

        $token = $this->getToken();
        if (! $token->isViewable()) {
            throw new \Gems\Exception(
                sprintf($this->_('Inaccessible or unknown token %s'), strtoupper($token->getTokenId())),
                403, null,
                sprintf($this->_('Access to this token is not allowed for current role: %s.'), $this->currentUser->getRole()));
        }

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
     * Check the tokens for a single respondent
     */
    public function checkAllAnswersAction()
    {
        $respondent = $this->getRespondent();
        $where      = $this->db->quoteInto('gto_id_respondent = ?', $respondent->getId());

        $batch = $this->loader->getTracker()->recalculateTokens(
            'answersCheckAllResp_' . $respondent->getId(),
            $this->currentUser->getUserId(),
            $where
        );

        $title = sprintf(
            $this->_('Checking all surveys of respondent %s, %s for answers.'),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Survey\\CheckAnswersInformation',
            'itemDescription', $this->_('This task (re)checks all tokens of this respondent for answers.')
        );
    }

    /**
     * Action for checking all assigned rounds for this respondent using a batch
     */
    public function checkAllTracksAction()
    {
        $respondent = $this->getRespondent();
        $where      = $this->db->quoteInto('gr2t_id_user = ?', $respondent->getId());

        $batch = $this->loader->getTracker()->checkTrackRounds(
            'trackCheckRoundsResp_' . $respondent->getId(),
            $this->currentUser->getUserId(),
            $where
        );

        $title = sprintf(
            $this->_('Checking round assignments for all tracks of respondent %s, %s.'),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Track\\CheckRoundsInformation');
    }

    /**
     * Check the survey level actions for this token
     */
    public function checkTokenAction()
    {
        if ($this->checkTokenSnippets) {
            $params = $this->_processParameters($this->checkTokenParameters + $this->defaultTokenParameters);

            $this->addSnippets($this->checkTokenSnippets, $params);
        }
    }

    /**
     * Check the tokens for a single token
     */
    public function checkTokenAnswersAction()
    {
        $token       = $this->getToken();
        $where       = $this->db->quoteInto('gto_id_token = ?', $token->getTokenId());
        $batch = $this->loader->getTracker()->recalculateTokens(
            'answersCheckToken__' . $token->getTokenId(),
            $this->currentUser->getUserId(),
            $where
        );
        $batch->autoStart = true;

        $title = sprintf(
            $this->_("Checking the token %s for answers."),
            $token->getTokenId()
        );
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Survey\\CheckAnswersInformation',
            'itemDescription', $this->_('This task checks one token for answers.')
        );
    }

    /**
     * Action for checking all assigned rounds for a single respondent track using a batch
     */
    public function checkTrackAction()
    {
        $respondent  = $this->getRespondent();
        $respTrackId = $this->getRespondentTrackId();
        $trackEngine = $this->getTrackEngine();
        $where       = $this->db->quoteInto('gr2t_id_respondent_track = ?', $respTrackId);
        $batch = $this->loader->getTracker()->checkTrackRounds(
            'trackCheckRoundsFor_' . $respTrackId,
            $this->currentUser->getUserId(),
            $where
        );

        $title = sprintf(
            $this->_("Checking round assignments for track '%s' of respondent %s, %s."),
            $trackEngine->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Track\\CheckRoundsInformation');
    }

    /**
     * Check the tokens for a single track
     */
    public function checkTrackAnswersAction()
    {
        $respondent  = $this->getRespondent();
        $respTrackId = $this->getRespondentTrackId();
        $trackEngine = $this->getTrackEngine();
        $where       = $this->db->quoteInto('gto_id_respondent_track = ?', $respTrackId);
        $batch = $this->loader->getTracker()->recalculateTokens(
            'answersCheckAllFor__' . $respTrackId,
            $this->currentUser->getUserId(),
            $where
        );

        $title = sprintf(
            $this->_("Checking the surveys in track '%s' of respondent %s, %s for answers."),
            $trackEngine->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Survey\\CheckAnswersInformation',
            'itemDescription', $this->_('This task checks all tokens for this track for this respondent for answers.')
        );
    }

    /**
     * Action for correcting answers
     */
    public function correctAction()
    {
        $this->deleteParameters = $this->correctParameters + $this->deleteParameters;

        $this->deleteAction();
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
     * @return \MUtil\Model\ModelAbstract
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
     * Get the title for correcting a token
     *
     * @return string
     */
    protected function getCorrectTokenTitle()
    {
        $token = $this->getToken();

        return sprintf(
            $this->_('Correct answers for survey %s, round %s'),
            $token->getSurveyName(),
            $token->getRoundDescription()
        );
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
            $token->getEmail(),          // When using relations, this is the right email address
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
     * Get a blocking filter when no respondent is passed on
     *
     * @return array
     */
    public function getNoRespondentFilter()
    {
        if (! $this->getRespondentId()) {
            return array('1 = 0');
        }
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
     * @return \Gems\Tracker\RespondentTrack
     */
    public function getRespondentTrack()
    {
        static $respTrack;

        if ($respTrack instanceof \Gems\Tracker\RespondentTrack) {
            return $respTrack;
        }

        $respTrackId = $this->request->getAttribute(\Gems\Model::RESPONDENT_TRACK);
        $tracker     = $this->loader->getTracker();

        if ($respTrackId) {
            $respTrack = $tracker->getRespondentTrack($respTrackId);
        } else {
            if ($this->isMultiTracks()) {
                throw new \Gems\Exception($this->_('No track specified for respondent!'));
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
                    if ($respTrack instanceof \Gems\Tracker\RespondentTrack) {
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
        if (! $respTrack instanceof \Gems\Tracker\RespondentTrack) {
            if ($this->isMultiTracks()) {
                throw new \Gems\Exception($this->_('No track found for respondent!'));
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
        $sid = $this->request->getAttribute(\Gems\Model::SURVEY_ID);
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
     * @return \Gems\Tracker\Token
     */
    public function getToken()
    {
        static $token;

        if ($token instanceof \Gems\Tracker\Token) {
            return $token;
        }

        $token   = null;
        $tokenId = $this->getTokenId();

        if ($tokenId) {
            $token = $this->loader->getTracker()->getToken($tokenId);
        }
        if ($token && $token->exists) {
            if (! array_key_exists($token->getOrganizationId(), $this->currentUser->getAllowedOrganizations())) {
                throw new \Gems\Exception(
                    $this->_('Inaccessible or unknown organization'),
                    403, null,
                    sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->currentUser->getRole()));
            }

            // Set variables for the menu
            $token->applyToMenuSource($this->menu->getParameterSource());

            return $token;
        }

        throw new \Gems\Exception($this->_('No existing token specified!'));
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
     * @return \Gems\Tracker\Engine\TrackEngineInterface
     */
    public function getTrackEngine()
    {
        static $engine;

        if ($engine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
            return $engine;
        }

        try {
            $respTrack = $this->getRespondentTrack();
            if ($respTrack instanceof \Gems\Tracker\RespondentTrack) {
                $engine = $respTrack->getTrackEngine();
            }

        } catch (\Exception $ex) {
        }

        if (! $engine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
            $trackId = $this->request->getAttribute(\Gems\model::TRACK_ID);

            if (! $trackId) {
                if ($this->isMultiTracks()) {
                    throw new \Gems\Exception($this->_('No track engine specified!'));
                }

                $trackId = $this->escort->getTrackId();

                if (! $trackId) {
                    return null;
                }
            }

            $engine = $this->loader->getTracker()->getTrackEngine($trackId);
        }

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

            if ($this->currentUser->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                // Set params
                return sprintf(
                    $this->_('%s track for respondent nr %s'),
                    $trackEngine->getTrackName(),
                    $respondent->getPatientNumber()
                );
            }

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
                $this->request->getAttribute(\MUtil\Model::REQUEST_ID1),
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
    public function init(): void
    {
        parent::init();

        if (in_array($this->requestHelper->getActionName(), $this->tokenReturnActions)) {
            // Tell the system where to return to after a survey has been taken
            $route = [
                    'route' => $this->requestHelper->getRouteResult()->getMatchedRouteName(),
                ] + $this->requestHelper->getRouteResult()->getMatchedParams();
            $this->currentUser->setSurveyReturn($route);
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
     * Action for checking all assigned rounds using a batch
     */
    public function recalcAllFieldsAction()
    {
        $respondent = $this->getRespondent();
        $where      = $this->db->quoteInto('gr2t_id_user = ?', $respondent->getId());

        $batch = $this->loader->getTracker()->recalcTrackFields(
            'trackRecalcFieldsResp_' . $respondent->getId(),
            $where
        );

        $title = sprintf(
            $this->_('Recalculating fields for all tracks of respondent %s, %s.'),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Track\\RecalcFieldsInformation');
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function recalcFieldsAction()
    {
        $respondent  = $this->getRespondent();
        $respTrackId = $this->getRespondentTrackId();
        $trackEngine = $this->getTrackEngine();
        $where       = $this->db->quoteInto('gr2t_id_respondent_track = ?', $respTrackId);
        $batch = $this->loader->getTracker()->recalcTrackFields(
            'trackRecalcFieldsFor_' . $respTrackId,
            $where
        );

        $title = sprintf(
            $this->_("Recalculating fields for track '%s' of respondent %s, %s."),
            $trackEngine->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getFullName()
        );
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addSnippet('Track\\RecalcFieldsInformation');
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