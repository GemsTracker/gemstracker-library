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
class Gems_Default_TrackAction extends \Gems_Controller_ModelSnippetActionAbstract
{
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
        'respondent'      => 'getRespondent',
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = array(
        'ModelTableSnippetGeneric',
        'Generic_CurrentButtonRowSnippet',
        'Tracker\\AvailableTracksSnippet',
        );
    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic_ContentTitleSnippet', 'AutosearchInRespondentSnippet');

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
    public $summarizedActions = array('index', 'autofilter', 'create', 'view');

    /**
     * Pops the answers to a survey in a separate window
     */
    public function answerAction()
    {
        // Set menu OFF
        $this->menu->setVisible(false);

        $tokenId = $this->_getIdParam();
        $token   = $this->loader->getTracker()->getToken($tokenId);

        // Set variables for the menu
        $token->applyToMenuSource($this->menu->getParameterSource());

        $this->setTitle(sprintf($this->_('Token answers: %s'), strtoupper($tokenId)));
        $this->addSnippets($token->getAnswerSnippetNames(), 'token', $token, 'tokenId', $tokenId);
    }

    /**
     * Create a new track (never a token as a token is created with the track)
     */
    public function createAction()
    {
        $this->createParameters = array(
            'formTitle'   => 'getCreateTrackTitle',
            'respondent'  => 'getRespondent',
            'trackEngine' => 'getTrackEngine',
            );

        $this->createEditSnippets = array(
            'Tracker\\TrackUsageOverviewSnippet',
            'Tracker\\TrackUsageTextDetailsSnippet',
            'Tracker\\EditTrackSnippet',
            'Tracker\\TrackSurveyOverviewSnippet',
            );

        parent::createAction();
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
        $model = $this->loader->getTracker()->getRespondentTrackModel();

        if ($detailed) {
            $engine = $this->getRespondentTrack()->getTrackEngine();

            switch ($action) {
                case 'export-track':
                case 'show-track':
                    $model->applyDetailSettings($engine);
                    break;

                default:
                    $model->applyEditSettings($engine);
                    break;
            }
        } else {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Edit the respondent track data
     *
     * @param array $params Optional parameters from child class
     * @param array $snippets Contains snippet names when other names are needed in a child class
     */
    public function editTrackAction(array $params = array(), array $snippets = null)
    {
        $respondent  = $this->getRespondent();
        $respTrack   = $this->getRespondentTrack();
        $trackEngine = $respTrack->getTrackEngine();

        // Set params
        $params['formTitle']      = sprintf(
                $this->_('%s track for respondent nr %s: %s'),
                $trackEngine->getTrackName(),
                $this->_getParam(\MUtil_Model::REQUEST_ID1),
                $this->getRespondent()->getFullName()
                );
        $params['multiTracks']       = ! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;
        $params['respondentTrack']   = $respTrack;
        $params['respondentTrackId'] = $respTrack->getRespondentTrackId();
        $params['trackEngine']       = $trackEngine;
        $params['trackId']           = $trackEngine->getTrackId();

        // Set snippets
        if (! $snippets) {
            $snippets = array(
                'Tracker\\EditTrackSnippet',
                'Tracker\\TrackUsageTextDetailsSnippet',
                'Tracker\\TrackTokenOverviewSnippet',
                'Tracker\\TrackUsageOverviewSnippet',
                );
        }
        $this->addSnippets($snippets, $params);
    }

    /**
     * Get the title for adding a track
     *
     * @return string
     */
    protected function getCreateTrackTitle()
    {
        $respondent = $this->getRespondent();

        return sprintf($this->_('Adding the %s track to respondent %s: %s'),
                $this->getTrackEngine()->getTrackName(),
                $respondent->getPatientNumber(),
                $respondent->getFullName()
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

        return sprintf($this->_('Tracks assigned to %s: %s'),
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
     * Get the respondent object
     *
     * @return \Gems_Tracker_Respondent
     */
    protected function getRespondent()
    {
        static $respondent;

        if (! $respondent) {
            $patientNumber  = $this->_getParam(\MUtil_Model::REQUEST_ID1);
            $organizationId = $this->_getParam(\MUtil_Model::REQUEST_ID2);

            $respondent = $this->loader->getRespondent($patientNumber, $organizationId);

            $this->menu->getParameterSource()->setPatient($patientNumber, $organizationId);
            // \Gems_Menu::$verbose = true;

            if (! $respondent->exists) {
                throw new \Gems_Exception($this->_('Unknown respondent.'));
            }
            $respondent->applyToMenuSource($this->menu->getParameterSource());
        }

        return $respondent;
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        return $this->getRespondent()->getId();
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
            if (! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface) {
                throw new \Gems_Exception($this->_('No respondent track specified!'));
            }

            $trackId    = $this->escort->getTrackId();
            $respTracks = $tracker->getRespondentTracks($respondent->getId(), $respondent->getOrganizationId());
            foreach ($respTracks as $respTrack) {
                if ($respTrack instanceof \Gems_Tracker_RespondentTrack) {
                    if ($trackId === $respTrack->getTrackId()) {
                        // Return the right track if it exists
                        break;
                    }
                }
            }
        }
        if (! $respTrack instanceof \Gems_Tracker_RespondentTrack) {
            throw new \Gems_Exception($this->_('No respondent track found!'));
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
        return $this->getRespondentTrack()->getTrackId();
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
            $engine = $this->getRespondentTrack()->getTrackEngine();

        } catch (\Exception $ex) {
            $engineId = $this->_getParam(\Gems_model::TRACK_ID);

            if (! $engineId) {
                throw new \Gems_Exception($this->_('No track engine specified!'));
            }

            $engine = $this->loader->getTracker()->getTrackEngine($engineId);
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
        return $this->getTrackEngine()->getTrackId();
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
     * Insert a single survey into a track
     *
     * @param array $params Optional parameters from child class
     * @param array $snippets Contains snippet names when other names are needed in a child class
     */
    public function insertAction(array $params = array(), array $snippets = array())
    {
        $params['formTitle'] = sprintf(
                $this->_('Inserting a survey in a track for respondent %s: %s'),
                $this->_getParam(\MUtil_Model::REQUEST_ID1),
                $this->getRespondent()->getFullName()
                );

        if (! $snippets) {
            $snippets[] = 'Tracker\\InsertSurveySnippet';
            // $snippets[] = 'Survey\\SurveyQuestionsSnippet';
        }
        $this->addSnippets($snippets, $params);
    }

    /**
     * Show information on a single track assigned to a respondent
     *
     * @param array $params Optional parameters from child class
     * @param array $snippets Contains snippet names when other names are needed in a child class
     */
    public function showTrackAction(array $params = array(), array $snippets = null)
    {
        $respondent  = $this->getRespondent();
        $respTrack   = $this->getRespondentTrack();
        $trackEngine = $respTrack->getTrackEngine();

        // Set params
        $params['contentTitle']      = sprintf(
                $this->_('%s track for respondent nr %s: %s'),
                $trackEngine->getTrackName(),
                $this->_getParam(\MUtil_Model::REQUEST_ID1),
                $this->getRespondent()->getFullName()
                );
        $params['model']             = $this->getModel();
        $params['multiTracks']       = ! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;
        $params['respondentTrack']   = $respTrack;
        $params['respondentTrackId'] = $respTrack->getRespondentTrackId();
        $params['trackEngine']       = $trackEngine;
        $params['trackId']           = $trackEngine->getTrackId();

        // Set snippets
        if (! $snippets) {
            $snippets = array(
                'Generic_ContentTitleSnippet',
                'ModelItemTableSnippetGeneric',
                'Tracker\\TrackUsageTextDetailsSnippet',
                'Tracker\\TrackTokenOverviewSnippet',
                'Tracker\\TrackUsageOverviewSnippet',
                );
        }
        $this->addSnippets($snippets, $params);
    }

    /**
     * Show information on a single track type assigned to a respondent
     *
     * @param array $params Optional parameters from child class
     * @param array $snippets Contains snippet names when other names are needed in a child class
     */
    public function viewAction(array $params = array(), array $snippets = null)
    {
        $respondent  = $this->getRespondent();
        $trackEngine = $this->getTrackEngine();

        // Set params
        $params['contentTitle']      = sprintf(
                $this->_('%s track assignments for respondent nr %s: %s'),
                $trackEngine->getTrackName(),
                $this->_getParam(\MUtil_Model::REQUEST_ID1),
                $this->getRespondent()->getFullName()
                );
        $params['multiTracks'] = ! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;
        $params['respondent']  = $respondent;
        $params['trackEngine'] = $trackEngine;
        $params['trackId']     = $trackEngine->getTrackId();

        // Set snippets
        if (! $snippets) {
            $snippets = array(
                'Tracker\\TrackUsageTextDetailsSnippet',
                'Generic_ContentTitleSnippet',
                'Tracker\\TrackUsageOverviewSnippet',
                'Generic_CurrentButtonRowSnippet',
                'Tracker\\TrackSurveyOverviewSnippet',
                );
        }
        $this->addSnippets($snippets, $params);
    }
}