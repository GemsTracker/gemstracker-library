<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Gems\Model;
use Gems\Util\Translated;

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
class EditTrackSnippetAbstract extends \Gems\Snippets\ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * Required
     *
     * @var \Gems\Loader
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
     * @var string Patient "nr"
     */
    protected $patientId;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * The respondent
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    /**
     * Optional, required when editing or $respondentTrackId should be set
     *
     * @var \Gems\Tracker\RespondentTrack
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
     * Optional, required when creating or $trackId should be set
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * Optional, required when creating or loader should be set
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
        if ((! $this->userId) && $this->currentUser) {
            $this->userId = $this->currentUser->getUserId();
        }
        return $this->loader && $this->request && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        $tracker = $this->loader->getTracker();
        $model   = $tracker->getRespondentTrackModel();

        if (! $this->trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
            if (! $this->respondentTrack) {
                $this->respondentTrack = $tracker->getRespondentTrack($this->respondentTrackId);
            }
            $this->trackEngine = $this->respondentTrack->getTrackEngine();
        }
        $model->applyEditSettings($this->trackEngine);

        return $model;
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
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->respondent instanceof \Gems\Tracker\Respondent) {
            if (! $this->patientId) {
                $this->patientId = $this->respondent->getPatientNumber();
            }
            if (! $this->organizationId) {
                $this->organizationId = $this->respondent->getOrganizationId();
            }
        }

        // Try to get $this->respondentTrackId filled
        if (! $this->respondentTrackId) {
            if ($this->respondentTrack) {
                $this->respondentTrackId = $this->respondentTrack->getRespondentTrackId();
            } else {
                $matchedParams = $this->requestInfo->getRequestMatchedParams();
                if (isset($matchedParams[Model::RESPONDENT_TRACK])) {
                    $this->respondentTrackId = $matchedParams[Model::RESPONDENT_TRACK];
                }
            }
        }
        // Try to get $this->respondentTrack filled
        if ($this->respondentTrackId && (! $this->respondentTrack)) {
            $this->respondentTrack = $this->loader->getTracker()->getRespondentTrack($this->respondentTrackId);
        }

        // Set the user id
        if (! $this->userId) {
            $this->userId = $this->loader->getCurrentUser()->getUserId();
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
                    $this->trackId = $this->request->getParam(\Gems\Model::TRACK_ID);
                }
            }
            // Try to get $this->trackEngine filled
            if ($this->trackId && (! $this->trackEngine)) {
                $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->trackId);
            }

            if (! ($this->trackEngine && $this->patientId && $this->organizationId && $this->userId)) {
                throw new \Gems\Exception\Coding('Missing parameter for ' . __CLASS__  .
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
        $model = $this->getModel();

        // When creating and not posting nor having $this->formData set already
        // we gotta make a special call
        if ($this->createData && (! ($this->formData || $this->requestInfo->isPost()))) {

            $filter['gtr_id_track']         = $this->trackId;
            $filter['gr2o_patient_nr']      = $this->patientId;
            $filter['gr2o_id_organization'] = $this->organizationId;

            $this->formData = $model->loadNew(null, $filter);
        } else {
            parent::loadFormData();
        }
        if (isset($this->formData['gr2t_completed']) && $this->formData['gr2t_completed']) {
            // Cannot change start date after first answered token
            $model->set('gr2t_start_date', 'elementClass', 'Exhibitor',
                    'formatFunction', $this->translatedUtil->formatDateUnknown,
                    'description', $this->_('Cannot be changed after first answered token.')
                    );
        }
        if ((! $this->createData) && isset($this->formData['grc_success']) && (! $this->formData['grc_success'])) {
            $model->set('grc_description', 'label', $this->_('Rejection code'),
                    'elementClass', 'Exhibitor'
                    );
        }
    }

    /**
     * If menu item does not exist or is not allowed, redirect to index
     *
     * @return \Gems\Snippets\ModelFormSnippetAbstract
     */
    protected function setAfterSaveRoute()
    {
        parent::setAfterSaveRoute();

        if (is_array($this->afterSaveRouteUrl)) {
            if (isset($this->afterSaveRouteUrl['action'], $this->formData['gr2t_id_respondent_track']) &&
                    'index' !== $this->afterSaveRouteUrl['action']) {
                $this->afterSaveRouteUrl[\Gems\Model::RESPONDENT_TRACK] = $this->formData['gr2t_id_respondent_track'];
            }
        }
    }
}
