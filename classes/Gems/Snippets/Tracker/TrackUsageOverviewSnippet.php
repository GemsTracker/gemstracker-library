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

use Gems\Model;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 30-apr-2015 16:37:27
 */
class TrackUsageOverviewSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditActions = ['edit-track'];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowActions = ['show-track'];

    /**
     * Are we working in a multi tracks environment?
     *
     * @var boolean
     */
    protected $multiTracks = true;

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

    /**
     *
     * @var \Gems\Tracker\TrackerInterface
     */
    protected $tracker;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->loader instanceof \Gems\Loader;
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
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
    public function hasHtmlOutput()
    {
        if (! $this->multiTracks) {
            return false;
        }

        $this->tracker = $this->loader->getTracker();
        $matchedParams = $this->requestInfo->getRequestMatchedParams();

        if (! $this->respondentTrackId && isset($matchedParams[Model::RESPONDENT_TRACK])) {
            $this->respondentTrackId = $matchedParams[Model::RESPONDENT_TRACK];
        }

        if ($this->respondentTrackId) {
            if (! $this->respondentTrack instanceof \Gems\Tracker\RespondentTrack) {
                $this->respondentTrack = $this->tracker->getRespondentTrack($this->respondentTrackId);
            }
        }
        if ($this->respondentTrack instanceof \Gems\Tracker\RespondentTrack) {
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
            if ($this->respondent instanceof \Gems\Tracker\Respondent) {
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


        if ((! $this->trackId) && $this->trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        return $this->trackId && $this->respondentId && $this->organizationId && parent::hasHtmlOutput();
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        $model->setFilter(array(
            'gr2t_id_track'        => $this->trackId,
            'gr2t_id_user'         => $this->respondentId,
            'gr2t_id_organization' => $this->organizationId,
        ));
        if ($this->respondentTrackId) {
            $model->addFilter(array(sprintf('gr2t_id_respondent_track != %d', intval($this->respondentTrackId))));
        }
        $model->setSort(array('gr2t_created' => SORT_DESC));
    }
}
