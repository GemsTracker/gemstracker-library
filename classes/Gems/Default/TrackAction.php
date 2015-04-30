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
        'trckUsage'       => false,
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
        $params['respondentTrack']   = $respTrack;
        $params['respondentTrackId'] = $respTrack->getRespondentTrackId();
        $params['trackEngine']       = $trackEngine;
        $params['trackId']           = $trackEngine->getTrackId();

        // Set snippets
        if (! $snippets) {
            $singleTracks = $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;

            $snippets[] = 'EditTrackSnippet';

            if (! $singleTracks) {
                $snippets[] = 'Tracker\\TrackUsageTextDetailsSnippet';
            }
            $snippets[] = 'Tracker\\TrackTokenOverviewSnippet';

            if (! $singleTracks) {
                $snippets[] = 'Tracker\\TrackUsageOverviewSnippet';
            }
        }
        $this->addSnippets($snippets, $params);
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
        return array('gr2t_id_user' => $this->getRespondent()->getId());
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

        $menuSource = $this->menu->getParameterSource();
        $menuSource->setRespondentTrackId($respTrack->getRespondentTrackId())
                ->setTrackId($respTrack->getTrackId())
                ->offsetSet('can_edit', $respTrack->getReceptionCode()->isSuccess() ? 1 : 0);

        // Otherwise return the last created track (yeah some implementations are not correct!)
        return $respTrack;
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
        $params['respondentTrack']   = $respTrack;
        $params['respondentTrackId'] = $respTrack->getRespondentTrackId();
        $params['trackEngine']       = $trackEngine;
        $params['trackId']           = $trackEngine->getTrackId();

        // Set snippets
        if (! $snippets) {
            $singleTracks = $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;

            $snippets[] = 'Generic_ContentTitleSnippet';
            $snippets[] = 'ModelItemTableSnippetGeneric';

            if (! $singleTracks) {
                $snippets[] = 'Tracker\\TrackUsageTextDetailsSnippet';
            }
            $snippets[] = 'Tracker\\TrackTokenOverviewSnippet';

            if (! $singleTracks) {
                $snippets[] = 'Tracker\\TrackUsageOverviewSnippet';
            }
        }
        $this->addSnippets($snippets, $params);
    }
}