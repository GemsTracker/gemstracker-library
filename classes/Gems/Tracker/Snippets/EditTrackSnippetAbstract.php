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

use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Model;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
class EditTrackSnippetAbstract extends ModelFormSnippetAbstract
{
    protected string $afterSaveRoutePart = 'show-track';

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
     * Optional, required when creating or loader should be set
     *
     * @var int The user ID of the one doing the changing
     */
    protected int $currentUserId;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected MaskRepository $maskRepository,
        protected Tracker $tracker,
        CurrentUserRepository $currentUserRepository,
        protected Translated $translatedUtil,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        $model   = $this->tracker->getRespondentTrackModel();

        if (! $this->trackEngine instanceof TrackEngineInterface) {
            if (! $this->respondentTrack) {
                $this->respondentTrack = $this->tracker->getRespondentTrack($this->respondentTrackId);
            }
            $this->trackEngine = $this->respondentTrack->getTrackEngine();
        }
        $model->setMaskRepository($this->maskRepository);
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
    public function hasHtmlOutput(): bool
    {
        if ($this->respondent instanceof Respondent) {
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
            $this->respondentTrack = $this->tracker->getRespondentTrack($this->respondentTrackId);
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
                    $this->trackId = $this->requestInfo->getParam(\Gems\Model::TRACK_ID);
                }
            }
            // Try to get $this->trackEngine filled
            if ($this->trackId && (! $this->trackEngine)) {
                $this->trackEngine = $this->tracker->getTrackEngine($this->trackId);
            }

            if (! ($this->trackEngine && $this->patientId && $this->organizationId && $this->currentUserId)) {
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
    protected function loadFormData(): array
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
        return $this->formData;
    }
}
