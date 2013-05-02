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
class Gems_Default_TrackAction extends Gems_Default_TrackActionAbstract
{
    /**
     * Use these snippets to show the content of a track
     *
     * @var mixed can be empty;
     */
    public $addTrackContentSnippets = 'TrackSurveyOverviewSnippet';

    public $sortKey = array('gr2t_created' => SORT_DESC);

    public $summarizedActions = array('index', 'autofilter', 'create', 'delete-track', 'edit-track', 'show-track');

    public $trackType = 'T';

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @rturn void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->gr2t_id_respondent_track; //For show and edit button

        $bridge->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);

        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('show-track')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addSortable($name, $label);
            }
        }

        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('edit-track')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        if ($model instanceof Gems_Tracker_Model_RespondentTrackModel) {
            $bridge->addHidden(   'gr2t_id_respondent_track');
            $bridge->addHidden(   'gr2t_id_user');
            $bridge->addHidden(   'gr2t_id_track');
            $bridge->addHidden(   'gr2t_id_organization');
            $bridge->addHidden(   'gr2t_active');
            $bridge->addHidden(   'gr2t_count');
            $bridge->addHidden(   'gr2t_reception_code');
            $bridge->addHidden(   'gr2o_id_organization');
            $bridge->addHidden(   'gtr_id_track');

            $bridge->addExhibitor('gtr_track_name');
            $bridge->addExhibitor('gr2o_patient_nr', 'label', $this->_('Respondent number'));
            $bridge->addExhibitor('respondent_name', 'label', $this->_('Respondent name')); // TODO: respondent nr

            if ('delete-track' === $model->getMeta('action')) {
                $bridge->addExhibitor('gr2t_track_info');
                $bridge->addExhibitor('gr2t_start_date');

                $sql = 'SELECT grc_id_reception_code, grc_description FROM gems__reception_codes WHERE grc_active = 1 AND grc_for_tracks = 1 ORDER BY grc_description';
                $options = $this->db->fetchPairs($sql);

                $bridge->addSelect('gr2t_reception_code',
                    'label', $this->_('Rejection code'),
                    'multiOptions', $options,
                    'order', 13,  // TODO: Do not know why this is needed at all, but becomes first item otherwise
                    'required', true,
                    'size', max(7, min(3, count($options) + 1)));
            }
        } else {
            parent::addFormElements($bridge, $model, $data, $new);
        }
    }

    protected function addTrackUsage($respId, $orgId, $trackId, $baseUrl)
    {
        $model  = $this->createRespondentTrackModel(true, 'usage');
        $filter = array('gr2o_patient_nr' => $respId, 'gr2o_id_organization' => $orgId, 'gtr_id_track' => $trackId);
        $sort   = $model->getRequestSort($this->getRequest()) + array('gr2t_created' => SORT_DESC);

        // MUtil_Echo::r($filter);
        if ($data = $model->load($filter, $sort)) {
            $bridge  = new MUtil_Model_TableBridge($model, array('class' => 'browser'));
            $bridge->setBaseUrl($baseUrl);
            $bridge->setRepeater($data);
            $bridge->setSort($sort);

            $tr = $bridge->tbody()->tr();
            $tr->appendAttrib('class', $bridge->row_class);

            // Add show-track button if allowed, otherwise show, again if allowed
            if ($menuItem = $this->findAllowedMenuItem('show-track', 'show')) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
            }

            foreach($model->getItemsOrdered() as $name) {
                if ($label = $model->get($name, 'label')) {
                    $bridge->addSortable($name, $label);
                }
            }

            // Add edit-track button if allowed, otherwise edit, again if allowed
            if ($menuItem = $this->findAllowedMenuItem('edit-track', 'edit')) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
            }

            $this->html->h3(sprintf($this->_('Assignments of this track to %s: %s'), $respId, $this->getRespondentName()));
            $this->html[] = $bridge->getTable();

            return true;
        }
    }

    public function beforeSave(array &$data, $isNew, Zend_Form $form = null)
    {
        if (isset($data['gtf_field'])) {
            // concatenate user input (gtf_field fields)
            $data['gr2t_track_info'] = trim(implode(' ', MUtil_Ra::flatten($data['gtf_field'])));
        }

        $data['gr2t_track_info'] = trim($info);

        return true;
    }

    public function afterSave(array $data, $isNew)
    {
        $model = $this->getModel();

        if ($model instanceof Gems_Tracker_Model_RespondentTrackModel) {
            if ('delete-track' === $model->getMeta('action')) {
                // Is really removed
                if ($data['gr2t_reception_code'] != GemsEscort::RECEPTION_OK) {

                    $tracker = $this->loader->getTracker();
                    $tokenSelect = $tracker->getTokenSelect(true);
                    $tokenSelect
                            ->andReceptionCodes()
                            ->andConsents()
                            ->forRespondentTrack($data['gr2t_id_respondent_track']);

                    // Update reception code for tokens
                    $tokens  = $tokenSelect->fetchAll();

                    // When a TRACK is removed, all tokens are automatically revoked
                    foreach ($tokens as $tokenData) {
                        $token = $tracker->getToken($tokenData);
                        if ($token->hasSuccesCode()) {
                            $token->setReceptionCode($data['gr2t_reception_code'], null, $this->session->user_id);
                        }
                    }
                }
            }

            return true;

        } else {
            return parent::afterSave($data, $isNew);
        }
    }

    public function afterSaveRoute($data)
    {
        $model = $this->getModel();
        if ($model instanceof Gems_Tracker_Model_RespondentTrackModel) {
            if ($this->menu) {
                if ($data instanceof Zend_Controller_Request_Abstract) {
                    $refData = $data;
                } elseif (is_array($data)) {
                    $refData = $model->getKeyRef($data) + $data;
                } else {
                    throw new Gems_Exception_Coding('The variable $data must be an array or a Zend_Controller_Request_Abstract object.');
                }

                if ($menuItem = $this->menu->find(array('controller' => 'track', 'action' => 'show-track', 'allowed' => true))) {
                    $url = $menuItem->toRouteUrl($refData);

                    if (null !== $url) {
                        $this->_helper->redirector->gotoRoute($url, null, true);
                        return true;
                    }
                }
            }
        }

        return parent::afterSaveRoute($data);
    }

    protected function createMenuLinks($includeLevel = 2, $parentLabel = true)
    {
        if ($includeLevel <= 10) {
            $includeLevel = 2;
        }

        $request = $this->getRequest();
        $links   = parent::createMenuLinks($includeLevel, $parentLabel);
        $parent  = reset($links);

        switch (key($links)) {
            case 'track.show-track':
                $parent[0] = $this->_('Show track');
                // Add show patient button if allowed, otherwise show, again if allowed
                if ($menuItem = $this->menu->find(array('controller' => 'track', 'action' => 'index', 'allowed' => true))) {
                    $links['track.index'] = $menuItem->toActionLink($request, $this, $this->_('Show tracks'));
                }
                if (isset($links['track.show'])) {
                    $link = $links['track.show'];
                    unset($links['track.show']);
                    $link[0] = $this->_('Show token');

                    $links['track.show'] = $link;
                }

                break;

            case 'track.index':
                $parent[0] = $this->_('Show tracks');
                if (isset($links['track.show-track'])) {
                    $link = $links['track.show-track'];
                    $link[0] = $this->_('Show track');

                    unset($links['track.index']);
                    $links['track.index'] = $parent;
                }

                break;
        }

        if (! isset($links['respondent.show'])) {
            // Add show patient button if allowed, otherwise show, again if allowed
            if ($menuItem = $this->menu->find(array('controller' => 'respondent', 'action' => 'show', 'allowed' => true))) {
                $links['respondent.show'] = $menuItem->toActionLink($request, $this, $this->_('Show respondent'));
            }
        }

        return $links;
    }

    protected function createModel($detailed, $action)
    {
        if ($detailed) {
            return $this->createTokenModel($detailed, $action);
        } else {
            return $this->createRespondentTrackModel($detailed, $action);
        }
    }

    public function createRespondentTrackModel($detailed, $action)
    {
        $model = parent::createRespondentTrackModel($detailed, $action);

        $model->addColumn('CONCAT(gr2t_completed, \'' . $this->_(' of ') . '\', gr2t_count)', 'progress');
        $model->set('progress', 'label', $this->_('Progress')); // , 'tdClass', 'rightAlign', 'thClass', 'rightAlign');

        return $model;
    }

    public function createTrackModel($detailed, $action)
    {
        $model = parent::createTrackModel($detailed, $action);

        //$model->resetOrder();
        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gtr_survey_rounds', 'label', $this->_('Survey #'), 'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        $model->set('gtr_date_start',    'label', $this->_('From'),  'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $this->util->getTranslated()->formatDate);
        $model->set('gtr_date_until',    'label', $this->_('Until'), 'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $this->util->getTranslated()->formatDateNa);

        return $model;
    }

    public function deleteTrackAction()
    {
        //*
        $respTrackId = $this->_getParam(Gems_Model::RESPONDENT_TRACK);
        $respTrack   = $this->loader->getTracker()->getRespondentTrack($respTrackId);

        // Set variables for the menu
        $respTrack->applyToMenuSource($this->menu->getParameterSource());

        $this->addSnippets($respTrack->getDeleteSnippets(), 'excludeCurrent', true, 'respondentTrack', $respTrack, 'respondentTrackId', $respTrackId, 'trackId', $respTrack->getTrackId());
        return;
        // */

        $deleteForm = true;

        $model   = $this->getModel();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost();

        } elseif ($request->getParam('confirmed')) {

            $track = $request->getParam(Gems_Model::RESPONDENT_TRACK);
            $sql1  = 'DELETE FROM gems__tokens WHERE gto_id_respondent_track = ?';
            $sql2  = 'DELETE FROM gems__respondent2track WHERE gr2t_id_respondent_track = ?';

            $this->db->query($sql1, $track);
            $this->db->query($sql2, $track);
            $this->addMessage($this->_('Track deleted!'));
            $this->_reroute(array('action' => 'index'), true);
            return;

        } else {
            $model->applyRequest($request);
            $data = $model->loadFirst();

            if ($data['grc_success']) {
                $sql = 'SELECT gto_id_token
                    FROM gems__tokens
                    WHERE (
                        gto_in_source = 1
                            OR
                        gto_reception_code IN (SELECT grc_id_reception_code FROM gems__reception_codes WHERE grc_success = 0)
                        ) AND gto_id_respondent_track = ? LIMIT 1';

                $deleteForm = $this->db->fetchOne($sql, $data['gr2t_id_respondent_track']);

            } else {
                $this->addMessage($this->_('Track was already deleted.'));
                $this->_reroute(array('action' => 'index'), true);
            }
        }

        $this->setMenuParameters($data);

        if ($deleteForm) {
            $title  = sprintf($this->_('Delete %s!'), $this->getTopic());

            // addFormElements stelt juiste parameters in
            if ($form = $this->processForm($title, $data)) {
                $this->addMessage(sprintf($this->_('Watch out! You cannot undo a %s deletion!'), $this->getTopic()));

                $this->html->h3($title);
                $this->html[] = $form;

                if ($request->isPost()) {
                    $this->addMessage($this->_('Choose a reception code to delete.'));
                }
            }

        } else {
            $question = $this->_('Do you want to delete this track?');

            $this->html->h3($this->_('Delete track'));

            $table = $this->getShowTable();
            $table->caption($question);
            $table->setRepeater(array($data));

            $footer = $table->tfrow($question, ' ', array('class' => 'centerAlign'));
            $footer->actionLink(array('confirmed' => 1), $this->_('Yes'));
            $footer->actionLink(array('action' => 'show-track'), $this->_('No'));

            $this->html[] = $table;
            $this->html->buttonDiv($this->createMenuLinks());
        }
        $this->addSnippet('TrackTokenOverviewSnippet', 'trackData', $data);
    }

    /**
     * Edit the respondent track data
     */
    public function editTrackAction()
    {
        $respTrackId = $this->_getParam(Gems_Model::RESPONDENT_TRACK);
        $respTrack   = $this->loader->getTracker()->getRespondentTrack($respTrackId);

        // Set variables for the menu
        $respTrack->applyToMenuSource($this->menu->getParameterSource());

        $this->addSnippets($respTrack->getEditSnippets(),
                'excludeCurrent', true,
                'respondentTrack', $respTrack,
                'respondentTrackId', $respTrackId,
                'trackId', $respTrack->getTrackId(),
                'userId', $this->session->user_id);
    }

    public function getTopic($count = 1)
    {
        if ($this->getModel() instanceof Gems_Tracker_Model_StandardTokenModel) {
            return $this->plural('token', 'tokens', $count);
        } else {
            return $this->plural('track', 'tracks', $count);
        }
    }

    public function getTopicTitle()
    {
        if ($this->getModel() instanceof Gems_Tracker_Model_StandardTokenModel) {
            return $this->_('Token');
        } else {
            return sprintf($this->_('Tracks assigned to %s: %s'),
                    $this->_getParam(MUtil_Model::REQUEST_ID1),
                    $this->getRespondentName()
                );
        }
    }

    /**
     * Show information on a single track assigned to a respondent
     */
    public function showTrackAction()
    {
        $request = $this->getRequest();
        $model   = $this->getModel();

        // Gems_Menu::$verbose = true;

        if ($data = $model->applyRequest($request)->loadFirst()) {
            $this->setMenuParameters($data);
            // MUtil_Echo::track($data);
            if ($data['grc_description']) {
                $model->set('grc_description', 'label', $this->_('Rejection code'), 'formatFunction', array($this->translate, '_'));
            }

            $links = $this->createMenuLinks(10);

            $this->_setParam(Gems_Model::RESPONDENT_TRACK, $data['gr2t_id_respondent_track']);

            $this->html->h2(sprintf($this->_('%s track for respondent nr %s: %s'),
                    $data['gtr_track_name'],
                    $this->_getParam(MUtil_Model::REQUEST_ID1),
                    $this->getRespondentName($data)));

            if (! $this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
                $table = parent::getShowTable();
                $table->setRepeater(array($data));

                // Show the track is deleted
                if (! $data['grc_success']) {
                    foreach ($table->tbody() as $row) {
                        if (isset($row[1])) {
                            $row[1]->appendAttrib('class', 'deleted');
                        }
                    }
                }
                // lookup and display fields that are linked to this respondent track
                $sql = "SELECT gems__respondent2track2field.*,gems__track_fields.* FROM gems__respondent2track2field LEFT JOIN gems__track_fields ON gtf_id_field = gr2t2f_id_field WHERE gr2t2f_id_respondent_track = ? ORDER BY gtf_id_order";
                $fieldValues = $this->db->fetchAll($sql, array('gr2t2f_id_respondent_track' => $data['gr2t_id_respondent_track']));

                foreach ($fieldValues as $field) {
                    $table->tr();
                    $table->tdh($field['gtf_field_name']);
                    $table->td($field['gr2t2f_value']);
                }

                $this->html[] = $table;

                if ($links) {
                    $this->html->buttonDiv($links);
                }

                $this->addSnippet('TrackUsageTextDetailsSnippet', 'trackData', $data);
            }
            $baseUrl[MUtil_Model::REQUEST_ID1]     = $this->_getParam(MUtil_Model::REQUEST_ID1);
            $baseUrl[MUtil_Model::REQUEST_ID2]     = $this->_getParam(MUtil_Model::REQUEST_ID2);
            $baseUrl[Gems_Model::RESPONDENT_TRACK] = $this->_getParam(Gems_Model::RESPONDENT_TRACK);

            $this->addSnippet('TrackTokenOverviewSnippet', 'trackData', $data, 'baseUrl', $baseUrl);

            if (! $this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
                $this->addTrackUsage($data['gr2o_patient_nr'], $data['gr2t_id_organization'], $data['gr2t_id_track'], $baseUrl);
            }

            if ($links) {
                $this->html->buttonDiv($links);
            }

        } elseif ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {

            $data['gr2o_patient_nr']      = $this->_getIdParam();
            $data['gr2o_id_organization'] = $this->escort->getCurrentOrganization();
            $data['track_can_be_created'] = 1;
            $this->setMenuParameters($data);

            $model = $this->createTrackModel(false, 'index');

            // MUtil_Model::$verbose = true;

            $this->html->h3($this->_('Add track'));
            $this->html->pInfo($this->_('This respondent does not yet have an active track. Add one here.'));
            $this->addSnippet('Track_AvailableTracksSnippets', 'model', $model, 'trackType', $this->trackType);

        } else {
            $this->addMessage(sprintf($this->_('Unknown %s requested'), $this->getTopic()));
        }
    }
}
