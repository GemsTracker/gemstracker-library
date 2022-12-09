<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\MenuNew\MenuSnippetHelper;
use Gems\Model;
use Gems\Snippets\ModelTableSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Tracker\Respondent;
use Gems\Tracker\RespondentTrack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 30-apr-2015 16:37:27
 */
class TrackUsageOverviewSnippet extends ModelTableSnippetAbstract
{
    public $extraSort = [
        'gr2t_created' => SORT_DESC
    ];

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditRoutes = ['edit-track'];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowRoutes = ['show-track'];

    /**
     * The oganization ID
     *
     * @var int
     */
    protected $organizationId;

    /**
     * The respondent
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    /**
     * The respondent ID
     *
     * @var int
     */
    protected $respondentId;

    /**
     * The respondent2track
     *
     * @var \Gems\Tracker\RespondentTrack
     */
    protected $respondentTrack;

    /**
     * The respondent2track ID
     *
     * @var int
     */
    protected $respondentTrackId;

    /**
     * Optional, can be source of the $trackId
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     *
     * @var int
     */
    protected $trackId;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected Tracker $tracker,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        $model = $this->tracker->getRespondentTrackModel();

        $model->applyBrowseSettings();

        return $model;
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
        $matchedParams = $this->requestInfo->getRequestMatchedParams();

        if (! $this->respondentTrackId && isset($matchedParams[Model::RESPONDENT_TRACK])) {
            $this->respondentTrackId = $matchedParams[Model::RESPONDENT_TRACK];
        }

        if ($this->respondentTrackId) {
            if (! $this->respondentTrack instanceof RespondentTrack) {
                $this->respondentTrack = $this->tracker->getRespondentTrack($this->respondentTrackId);
            }
        }
        if ($this->respondentTrack instanceof RespondentTrack) {
            if (! $this->respondentTrackId) {
                $this->respondentTrackId = $this->respondentTrack->getRespondentTrackId();
            }

            $this->trackId = $this->respondentTrack->getTrackId();

            if (! $this->respondentId) {
                $this->respondentId = $this->respondentTrack->getRespondentId();
            }
            if (! $this->organizationId) {
                $this->organizationId = $this->respondentTrack->getOrganizationId();
            }
            $this->caption = $this->_('Other assignments of this track to this respondent.');
            $this->onEmpty = $this->_('This track is assigned only once to this respondent.');

        } else {
            if ($this->respondent instanceof Respondent) {
                if (! $this->respondentId) {
                    $this->respondentId = $this->respondent->getId();
                }
                if (! $this->organizationId) {
                    $this->organizationId = $this->respondent->getOrganizationId();
                }
            }
            $this->caption = $this->_('Existing assignments of this track to this respondent.');
            $this->onEmpty = $this->_('This track is not assigned to this respondent.');
        }

        if (! $this->trackId && isset($matchedParams[Model::TRACK_ID])) {
            $this->trackId = $matchedParams[Model::TRACK_ID];
        }


        if ((! $this->trackId) && $this->trackEngine instanceof TrackEngineInterface) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        $this->extraFilter = [
            'gr2t_id_track'        => $this->trackId,
            'gr2t_id_user'         => $this->respondentId,
            'gr2t_id_organization' => $this->organizationId,
        ];
        if ($this->respondentTrackId) {
            $this->extraFilter[] = sprintf('gr2t_id_respondent_track != %d', $this->respondentTrackId);
        }


        return $this->trackId && $this->respondentId && $this->organizationId && parent::hasHtmlOutput();
    }
}
