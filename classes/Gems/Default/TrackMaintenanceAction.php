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
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_TrackMaintenanceAction extends \Gems_Default_TrackMaintenanceWithEngineActionAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

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
        'extraSort'   => array('gtr_track_name' => SORT_ASC),
        'trackEngine' => null,
        );

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('track', 'tracks');

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createParameters = array(
        'trackEngine' => null,
    );

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackMaintenance\\TrackMaintenanceSearchSnippet'
        );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Tracker\\Fields\\FieldsTitleSnippet',
        'Tracker\\Fields\\FieldsTableSnippet',
        'Tracker\\Buttons\\NewFieldButtonRow',
        'Tracker\\Rounds\\RoundsTitleSnippet',
        'Tracker\\Rounds\\RoundsTableSnippet',
        'Tracker\\Buttons\\NewRoundButtonRow',
        );

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
    public $summarizedActions = array('index', 'autofilter', 'check-all', 'recalc-all-fields');

    /**
     * Displays a textual explanation what check tracking does on the page.
     */
    protected function addCheckInformation()
    {
        $this->html->h2($this->_('Checks'));
        $ul = $this->html->ul();
        $ul->li($this->_('Updates existing token description and order to the current round description and order.'));
        $ul->li($this->_('Updates the survey of unanswered tokens when the round survey was changed.'));
        $ul->li($this->_('Removes unanswered tokens when the round is no longer active.'));
        $ul->li($this->_('Creates new tokens for new rounds.'));
        $ul->li($this->_('Checks the validity dates and times of unanswered tokens, using the current round settings.'));

        $this->html->pInfo($this->_('Run this code when a track has changed or when the code has changed and the track must be adjusted.'));
        $this->html->pInfo($this->_('If you do not run this code after changing a track, then the old tracks remain as they were and only newly created tracks will reflect the changes.'));
    }

    /**
     * Displays a textual explanation what recalculating does on the page.
     */
    protected function addRecalcInformation()
    {
        $this->html->h2($this->_('Track field recalculation'));
        $ul = $this->html->ul();
        $ul->li($this->_('Recalculates the values the fields should have.'));
        $ul->li($this->_('Couple existing appointments to tracks where an appointment field is not filled.'));
        $ul->li($this->_('Overwrite existing appointments to tracks e.g. when the filters have changed.'));
        $ul->li($this->_('Checks the validity dates and times of unanswered tokens, using the current round settings.'));

        $this->html->pInfo($this->_('Run this code when automatically calculated track fields have changed, when the appointment filters used by this track have changed or when the code has changed and the track must be adjusted.'));
        $this->html->pInfo($this->_('If you do not run this code after changing track fields, then the old fields values remain as they were and only newly changed and newly created tracks will reflect the changes.'));
    }

    /**
     * Action for making a copy of a track
     */
    public function copyAction()
    {
        $trackId    = $this->_getIdParam();
        $engine     = $this->getTrackEngine();
        $newTrackId = $engine->copyTrack($trackId);

        $this->_reroute(array('action' => 'edit', \MUtil_Model::REQUEST_ID => $newTrackId));
    }

    /**
     * Action for checking all assigned rounds using a batch
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->checkTrackRounds('trackCheckRoundsAll', $this->currentUser->getUserId());
        $this->_helper->BatchRunner($batch, $this->_('Checking round assignments for all tracks.'), $this->accesslog);

        $this->addCheckInformation();
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function checkTrackAction()
    {
        $id    = $this->_getIdParam();
        $track = $this->getTrackEngine();
        $where = $this->db->quoteInto('gr2t_id_track = ?', $id);
        $batch = $this->loader->getTracker()->checkTrackRounds(
                'trackCheckRounds' . $id,
                $this->currentUser->getUserId(),
                $where
                );

        $title = sprintf($this->_("Checking round assignments for track '%s'."), $track->getTrackName());
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addCheckInformation();
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction()
    {
        $this->createEditSnippets = $this->loader->getTracker()->getTrackEngineEditSnippets();

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
     * @return \Gems_Model_TrackModel
     */
    public function createModel($detailed, $action)
    {
        $tracker = $this->loader->getTracker();

        $model = $tracker->getTrackModel();
        $model->applyFormatting($detailed);
        $model->addFilter(array("gtr_track_class != 'SingleSurveyEngine'"));

        return $model;
    }

    /**
     * Edit a single item
     */
    public function editAction()
    {
        $this->createEditSnippets = $this->loader->getTracker()->getTrackEngineEditSnippets();

        parent::editAction();
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        if (isset($filter['org']) && strlen($filter['org'])) {
            $filter[] = 'gtr_organizations LIKE "%|' . $filter['org'] . '|%"';
            unset($filter['org']);
        }

        return $filter;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     * Action for checking all assigned rounds using a batch
     */
    public function recalcAllFieldsAction()
    {
        $batch = $this->loader->getTracker()->recalcTrackFields(
                'trackRecalcAllFields',
                $this->currentUser->getUserId()
                );
        $this->_helper->BatchRunner($batch, $this->_('Recalculating fields for all tracks.'), $this->accesslog);

        $this->addRecalcInformation();
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function recalcFieldsAction()
    {
        $id    = $this->_getIdParam();
        $track = $this->getTrackEngine();
        $where = $this->db->quoteInto('gr2t_id_track = ?', $id);
        $batch = $this->loader->getTracker()->recalcTrackFields(
                'trackRecalcFields' . $id,
                $this->currentUser->getUserId(),
                $where
                );

        $title = sprintf($this->_("Recalculating fields for track '%s'."), $track->getTrackName(), $this->accesslog);
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->addRecalcInformation();
    }
}
