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
 * Adds basic single survey token editing snippet parameter processing and checking.
 *
 * This class supplies the model and adjusts the basic load & save functions.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Snippets_EditSingleSurveyTokenSnippetAbstract extends Gems_Snippets_ModelFormSnippetAbstract
{
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
     * Optional, required when editing or $respondentTrack should be set
     *
     * @var int Respondent Track Id
     */
    protected $respondentTrackId;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var Gems_Tracker_Token
     */
    protected $token;

    /**
     * Optional: id of the selected token to show
     *
     * Can be derived from $request or $token
     *
     * @var string
     */
    protected $tokenId;

    /**
     *
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
        if ($this->token) {
            $model = $this->token->getModel();
            $this->trackEngine->addFieldsToModel($model, true);
        } else {
            if (!$this->trackEngine && $this->trackId) {
                $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->trackId);
            }

            $model = $this->trackEngine->getTokenModel();
            $this->trackEngine->addFieldsToModel($model, true);
        }

        $model->addEditTracking();
        $model->set('gto_id_token', 'formatFunction', 'strtoupper');

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
        return $this->plural('survey', 'surveys', $count);
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
        if ($this->trackEngine && (! $this->trackId)) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        if (! ($this->token && $this->token->exists)) {
            $this->createData = $this->organizationId && $this->patientId && $this->trackId;
            $this->saveLabel = $this->_($this->_('Add survey'));
        } else {
            if (! $this->respondentTrackId) {
                $this->respondentTrackId = $this->token->getRespondentTrackId();
            }
        }


        if (! $this->trackEngine) {
            if ($this->createData) {
                throw new Gems_Exception_Coding('Missing track engine while adding survey.');
            } elseif ($this->token && $this->token->exists) {
                $this->trackEngine = $this->token->getTrackEngine();
            }
        }

        return parent::hasHtmlOutput();
    }
}
