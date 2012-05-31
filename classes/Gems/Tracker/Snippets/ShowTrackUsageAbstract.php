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
 * Displays the assignments of a track to a respondent.
 *
 * This code contains some display options for excluding or marking a single track
 * and for processing the passed parameters identifying the respondent and the
 * optional single track.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class Gems_Tracker_Snippets_ShowTrackUsageAbstract extends Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('gr2t_created' => SORT_DESC);

    /**
     * Optional, when true current item is not shown, when false the current row is marked as the currentRow.
     *
     * @var boolean
     */
    protected $excludeCurrent = false;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Required
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Optional, required when using $trackEngine or $trackId only
     *
     * @var int Organization Id
     */
    protected $organizationId;

    /**
     * Optional, required when using $trackEngine or $trackId only
     *
     * @var int Patient Id
     */
    protected $patientId;

    /**
     * Optional, one of $respondentTrack, $respondentTrackId, $trackEngine, $trackId should be set
     *
     * @var Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

    /**
     *
     * @var int Respondent Track Id
     */
    protected $respondentTrackId;

    /**
     * Optional, one of $respondentTrack, $respondentTrackId, $trackEngine, $trackId should be set
     *
     * $trackEngine and TrackId need $patientId and $organizationId to be set as well
     *
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, one of $respondentTrack, $respondentTrackId, $trackEngine, $trackId should be set
     *
     * $trackEngine and TrackId need $patientId and $organizationId to be set as well
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
        return $this->db && $this->loader && parent::checkRegistryRequestsAnswers();
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
        $model->set('gr2t_track_info',   'label', $this->_('Description'),
            'description', $this->_('Enter the particulars concerning the assignment to this respondent.'));
        $model->set('assigned_by',       'label', $this->_('Assigned by'));
        $model->set('gr2t_start_date',   'label', $this->_('Start'),
            'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $this->loader->getUtil()->getTranslated()->formatDate,
            'default', new Zend_Date());
        $model->set('gr2t_reception_code');

        return $model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $seq = $this->getHtmlSequence();

        $seq->h3($this->getTitle());

        $table = parent::getHtmlOutput($view);
        $this->applyHtmlAttributes($table);

        $seq->append($table);

        return $seq;
    }

    abstract protected function getTitle();

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
        // Try to set $this->respondentTrackId, it can be ok when not set
        if (! $this->respondentTrackId) {
            if ($this->respondentTrack) {
                $this->respondentTrackId = $this->respondentTrack->getRespondentTrackId();
            } else {
                $this->respondentTrackId = $this->request->getParam(Gems_Model::RESPONDENT_TRACK);
            }
        }
        // First attempt at trackId
        if ((! $this->trackId) && $this->trackEngine) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        // Check if a sufficient set of data is there
        if (! ($this->trackId || $this->patientId || $this->organizationId)) {
            // Now we really need $this->respondentTrack
            if (! $this->respondentTrack) {
                if ($this->respondentTrackId) {
                    $this->respondentTrack = $this->loader->getTracker()->getRespondentTrack($this->respondentTrackId);
                } else {
                    // Parameters not valid
                    return false;
                }
            }
        }

        if (! $this->trackId) {
            $this->trackId = $this->respondentTrack->getTrackId();
        }
        if (! $this->patientId) {
            $this->patientId = $this->respondentTrack->getPatientNumber();
        }
        if (! $this->organizationId) {
            $this->organizationId = $this->respondentTrack->getOrganizationId();
        }

        // MUtil_Echo::track($this->trackId, $this->patientId, $this->organizationId, $this->respondentTrackId);

        return parent::hasHtmlOutput();
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        if ($this->request) {
            $this->processSortOnly($model);
        }

        $filter['gtr_id_track']         = $this->trackId;
        $filter['gr2o_patient_nr']      = $this->patientId;
        $filter['gr2o_id_organization'] = $this->organizationId;

        if ($this->excludeCurrent) {
            $filter[] = $this->db->quoteInto('gr2t_id_respondent_track != ?', $this->respondentTrackId);
        }

        $model->setFilter($filter);
    }
}
