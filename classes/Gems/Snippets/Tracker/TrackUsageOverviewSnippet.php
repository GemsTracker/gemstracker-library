<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrackUsageOverviewSnippet.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Snippets\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 30-apr-2015 16:37:27
 */
class TrackUsageOverviewSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The oganization ID
     *
     * @var int
     */
    protected $organizationId;

    /**
     * The respondent ID
     *
     * @var int
     */
    protected $respondentId;

    /**
     * The respondent2track ID
     *
     * @var int
     */
    protected $respondentTrackId;

    /**
     * Optional, can be source of the $trackId
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     *
     * @var int
     */
    protected $trackId;

    /**
     *
     * @var \Gems_Tracker_TrackerInterface
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
        return $this->loader instanceof \Gems_Loader;
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
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
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        $this->tracker = $this->loader->getTracker();

        if (! $this->respondentTrackId) {
            $this->respondentTrackId = $this->request->getParam(\Gems_Model::RESPONDENT_TRACK);
        }

        if ($this->respondentTrackId) {
            $respTrack     = $this->tracker->getRespondentTrack($this->respondentTrackId);
            $this->trackId = $respTrack->getTrackId();

            if (! $this->respondentId) {
                $this->respondentId = $respTrack->getRespondentId();
            }
            if (! $this->organizationId) {
                $this->organizationId = $respTrack->getOrganizationId();
            }
            $this->caption = $this->_('Other assignments of this track to this respondent.');
            $this->onEmpty = $this->_('This track is assigned only once to this respondent.');

        } else {
            $this->caption = $this->_('Assignments of this track to this respondent.');
            $this->onEmpty = $this->_('This track is not assigned to this respondent.');
        }

        if (! $this->trackId) {
            $this->trackId = $this->request->getParam(\Gems_Model::TRACK_ID);
        }
        if ((! $this->trackId) && $this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        return $this->trackId && $this->respondentId && $this->organizationId;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
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
