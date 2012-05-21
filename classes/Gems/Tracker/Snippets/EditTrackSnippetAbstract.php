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
 * Adds basic track editing snippet parameter processing and checking.
 *
 * This class supplies the model and adjusts the basic load & save functions.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Snippets_EditTrackSnippetAbstract extends Gems_Snippets_ModelFormSnippetAbstract
{
    const TRACKFIELDS_ID = 'gtrFields';

    /**
     * Required
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Optional, required when creating
     *
     * @var int Organization Id
     */
    protected $organizationId;

    /**
     * Optional, required when creating
     *
     * @var int Patient Id
     */
    protected $patientId;

    /**
     * Required
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Optional, required when editing or $respondentTrackId should be set
     *
     * @var Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

    /**
     * Optional, required when editing or $respondentTrack should be set
     *
     * @var int Respondent Track Id
     */
    protected $respondentTrackId;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show-track';

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * Optional, required when creating or $session should be set
     *
     * @var int The user Id of the one doing the changing
     */
    protected $userId;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->loader && $this->request && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->loader->getTracker()->getRespondentTrackModel();

        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gr2t_track_info',   'label', $this->_('Description'));
        $model->set('assigned_by',       'label', $this->_('Assigned by'));
        $model->set('gr2t_start_date',   'label', $this->_('Start'),
            'dateFormat', Gems_Tracker::DB_DATE_FORMAT,
            'formatFunction', $this->loader->getUtil()->getTranslated()->formatDate,
            'default', MUtil_Date::format(new Zend_date(), 'dd-MM-yyyy'));
        $model->set('gr2t_reception_code');
        $model->set('gr2t_comment',      'label', $this->_('Comment'));

        return $model;
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        if ($this->createData) {
            return $this->_('Add track');
        } else {
            return parent::getTitle();
        }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        // Try to get $this->respondentTrackId filled
        if (! $this->respondentTrackId) {
            if ($this->respondentTrack) {
                $this->respondentTrackId = $this->respondentTrack->getRespondentTrackId();
            } else {
                $this->respondentTrackId = $this->request->getParam(Gems_Model::RESPONDENT_TRACK);
            }
        }
        // Try to get $this->respondentTrack filled
        if ($this->respondentTrackId && (! $this->respondentTrack)) {
            $this->respondentTrack = $this->loader->getTracker()->getRespondentTrack($this->respondentTrackId);
        }

        // Set the user id
        if ((! $this->userId) && $this->session) {
            $this->userId = $this->session->user_id;
        }

        if ($this->respondentTrack) {
            // We are updating
            $this->createData = false;

            // Try to get $this->trackEngine filled
            if (! $this->trackEngine) {
                // Set the engine used
                $this->trackEngine = $this->respondentTrack->getTrackEngine();
            }

        } else {
            // We are inserting
            $this->createData = true;
            $this->saveLabel = $this->_($this->_('Add track'));

            // Try to get $this->trackId filled
            if (! $this->trackId) {
                if ($this->trackEngine) {
                    $this->trackId = $this->trackEngine->getTrackId();
                } else {
                    $this->trackId = $this->request->getParam(Gems_Model::TRACK_ID);
                }
            }
            // Try to get $this->trackEngine filled
            if ($this->trackId && (! $this->trackEngine)) {
                $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->trackId);
            }

            if (! ($this->trackEngine && $this->patientId && $this->organizationId && $this->userId)) {
                throw new Gems_Exception_Coding('Missing parameter for ' . __CLASS__  .
                        ': could not find data for editing a respondent track nor the track engine, patientId and organizationId needed for creating one.');
            }
        }

        return parent::hasHtmlOutput();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        // When creating and not posting nor having $this->formData set already
        // we gotta make a special call
        if ($this->createData && (! ($this->formData || $this->request->isPost()))) {

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
     * Set what to do when the form is 'finished'.
     *
     * @return Gems_Tracker_Snippets_EditTrackSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array($this->request->getActionKey() => $this->routeAction, Gems_Model::RESPONDENT_TRACK => $this->respondentTrackId);
        }

        return $this;
    }
}
