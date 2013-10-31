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
class Gems_Default_TrackMaintenanceAction  extends Gems_Controller_BrowseEditAction
{
    /**
     * Mode for the current addBrowse drawing.
     *
     * @var string
     */
    protected $browseMode;

    /**
     *
     * @var Zend_Cache_Core
     */
    public $cache;

    public $sortKey = array('gtr_track_name' => SORT_ASC);

    public $summarizedActions = array('index', 'autofilter', 'check-all');

    public $menuShowIncludeLevel = 10;

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
        $request = $this->getRequest();

        $actionKey  = $request->getActionKey();
        $contrKey   = $request->getControllerKey();
        $controller = $this->browseMode ? $this->browseMode : $request->getControllerName();

        if ($menuItem = $this->menu->find(array($contrKey => $controller, $actionKey => 'show'))) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        $menuItem = $this->menu->find(array($contrKey => $controller, $actionKey => 'edit'));

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addSortable($name, $label);
            }
        }

        if ($menuItem) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }
    }

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
        $bridge->addHidden(  'gtr_id_track');
        $bridge->addText(    'gtr_track_name', 'size', 30, 'minlength', 4, 'validator', $model->createUniqueValidator('gtr_track_name'));
        $bridge->addSelect(  'gtr_track_class');
        $bridge->addDate(    'gtr_date_start');
        $bridge->addDate(    'gtr_date_until');
        // $bridge->addList(    'gtr_start_date_field', 'label', $this->_('Date used for track'));
        $bridge->addCheckbox('gtr_active');
        $bridge->addMultiCheckbox('gtr_organizations', 'label', $this->_('Organizations'), 'multiOptions', $this->util->getDbLookup()->getOrganizations(), 'required', true);
    }

    /**
     * @param array $data
     * @param bool  $isNew
     * @return array
     */
    public function afterFormLoad(array &$data, $isNew)
    {
        // feature request #200
        if (isset($data['gtr_organizations']) && (! is_array($data['gtr_organizations']))) {
            $data['gtr_organizations'] = explode('|', trim($data['gtr_organizations'], '|'));
        }
    }

    /**
     * Hook to perform action after a record (with changes) was saved
     *
     * As the data was already saved, it can NOT be changed anymore
     *
     * @param array $data
     * @param boolean $isNew
     * @return boolean  True when you want to display the default 'saved' messages
     */
    public function afterSave(array $data, $isNew)
    {
        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('surveys', 'tracks'));

        return true;
    }

    /**
     *
     * @param array $data The data that will be saved.
     * @param boolean $isNew
     * $param Zend_Form $form
     * @return array|null Returns null if save was already handled, the data otherwise.
     */
    public function beforeSave(array &$data, $isNew, Zend_Form $form = null)
    {
        // feature request #200
        if (isset($data['gtr_organizations']) && is_array($data['gtr_organizations'])) {
            $data['gtr_organizations'] = '|' . implode('|', $data['gtr_organizations']) . '|';
        }
        if (isset($data['gtr_id_track'])) {
            $data['gtr_survey_rounds'] = $this->db->fetchOne("SELECT COUNT(*) FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ?", $data['gtr_id_track']);
        } else {
            $data['gtr_survey_rounds'] = 0;
        }

        return true;
    }

    public function copyAction()
    {
        $trackId = $this->_getIdParam();
        $trackModel = $this->getModel();
        $roundModel = $this->createModel(true, 'rounds');
        $fieldModel = $this->createModel(true, 'fields');

        // First load the track
        $trackModel->applyRequest($this->getRequest());
        $track = $trackModel->loadFirst();

        // Create an empty track
        $newTrack = $trackModel->loadNew();
        unset($track['gtr_id_track'], $track['gtr_changed'], $track['gtr_changed_by'], $track['gtr_created'], $track['gtr_created_by']);
        $track['gtr_track_name'] .= $this->_(' - Copy');
        $newTrack = $track + $newTrack;
        // Now save (not done yet)
        $savedValues = $trackModel->save($newTrack);
        $newTrackId = $savedValues['gtr_id_track'];

        // Now copy the rounds
        $roundModel->applyRequest($this->getRequest());
        $rounds = $roundModel->load();

        if ($rounds) {
            $numRounds = count($rounds);
            $newRounds = $roundModel->loadNew($numRounds);
            foreach ($newRounds as $idx => $newRound) {
                $round = $rounds[$idx];
                unset($round['gro_id_round'], $round['gro_changed'], $round['gro_changed_by'], $round['gro_created'], $round['gro_created_by']);
                $round['gro_id_track'] = $newTrackId;
                $newRounds[$idx] = $round + $newRounds[$idx];
            }
            // Now save (not done yet)
            $savedValues = $roundModel->saveAll($newRounds);
        } else {
            $numRounds = 0;
        }

        // Now copy the fields
        $fieldModel->applyRequest($this->getRequest());
        $fields = $fieldModel->load();

        if ($fields) {
            $numFields = count($fields);
            $newFields = $fieldModel->loadNew($numFields);
            foreach ($newFields as $idx => $newField) {
                $field = $fields[$idx];
                unset($field['gtf_id_field'], $field['gtf_changed'], $field['gtf_changed_by'], $field['gtf_created'], $field['gtf_created_by']);
                $field['gtf_id_track'] = $newTrackId;
                $newFields[$idx] = $field + $newFields[$idx];
            }
            // Now save (not done yet)
            $savedValues = $fieldModel->saveAll($newFields);
        } else {
            $numFields = 0;
        }

        //MUtil_Echo::track($track, $copy);
        //MUtil_Echo::track($rounds, $newRounds);
        //MUtil_Echo::track($fields, $newFields);
        $this->addMessage(sprintf($this->_('Copied track, including %s round(s) and %s field(s).'), $numRounds, $numFields));
        $this->_reroute(array('action' => 'edit', MUtil_Model::REQUEST_ID => $newTrackId));
    }

    /**
     * Action for checking all assigned rounds using a batch
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->checkTrackRounds('trackCheckRoundsAll', $this->loader->getCurrentUser()->getUserId());
        $this->_helper->BatchRunner($batch, $this->_('Checking round assignments for all tracks.'));

        $this->addCheckInformation();
    }

    /**
     * Action for checking all assigned rounds for a single track using a batch
     */
    public function checkTrackAction()
    {
        $id    = $this->_getIdParam();
        $track = $this->loader->getTracker()->getTrackEngine($id);
        $track->applyToMenuSource($this->menu->getParameterSource());
        $where = $this->db->quoteInto('gr2t_id_track = ?', $id);
        $batch = $this->loader->getTracker()->checkTrackRounds('trackCheckRounds' . $id, $this->loader->getCurrentUser()->getUserId(), $where);

        $title = sprintf($this->_("Checking round assignments for track '%s'."), $track->getTrackName());
        $this->_helper->BatchRunner($batch, $title);

        $this->addCheckInformation();
    }

    public function createAction()
    {
        $this->addSnippets($this->loader->getTracker()->getTrackEngineEditSnippets());
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
     * @return Gems_Model_TrackModel
     */
    public function createModel($detailed, $action)
    {
        $tracker = $this->loader->getTracker();

        switch ($action) {
            case "rounds": {
                $trackId = $this->_getIdParam();
                $engine = $tracker->getTrackEngine($trackId);
                $model  = $engine->getRoundModel(false, $action);
                $model->addLeftTable('gems__surveys', array('gro_id_survey' => 'gsu_id_survey'));
                $model->set('gsu_id_primary_group',   'label', $this->_('Group'), 'multiOptions', $this->util->getDbLookup()->getGroups());
            } break;

            case "fields": {
                $model = new MUtil_Model_TableModel('gems__track_fields');
                Gems_Model::setChangeFieldsByPrefix($model, 'gtf');
                $model->setKeys(array('id' => 'gtf_id_track'));
                $model->set('gtf_field_name', 'label', $this->_('Name'));
                $model->set('gtf_field_values', 'label', $this->_('Values'));
                $model->set('gtf_field_type', 'label', $this->_('Type'));
                $model->set('gtf_required', 'label', $this->_('Required'), 'multiOptions', $this->util->getTranslated()->getYesNo());
            } break;

            default: {
                $model = $this->loader->getTracker()->getTrackModel();
                $model->applyFormatting($detailed);
            }
        }

        return $model;
    }

    /**
     * Edit a single item
     */
    public function editAction()
    {
        $tracker     = $this->loader->getTracker();
        $trackId     = $this->_getIdParam();
        $trackEngine = $tracker->getTrackEngine($trackId);

        // Set variables for the menu
        $trackEngine->applyToMenuSource($this->menu->getParameterSource());

        $this->addSnippets($tracker->getTrackEngineEditSnippets(), 'trackEngine', $trackEngine, 'trackId', $trackId);
    }

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements = parent::getAutoSearchElements($model, $data);

        if ($elements) {
            $br = MUtil_Html::create('br');
            $elements[] = $this->_createSelectElement('gtr_track_class', $model, $this->_('(all track engines)'));

            $elements[] = $br;

            $element = $this->_createSelectElement('active', $this->util->getTranslated()->getYesNo(), $this->_('(both)'));
            $element->setLabel($model->get('gtr_active', 'label'));
            $elements[] = $element;

        }

        return $elements;
    }

    /**
     * Additional data filter statements for the user input.
     *
     * User input that has the same name as a model field is automatically
     * used as a filter, but if the name is different processing is needed.
     * That processing should happen here.
     *
     * @param array $data The current user input
     * @return array New filter statements
     */
    protected function getDataFilter(array $data)
    {
        $filter = parent::getDataFilter($data);

        if (isset($data['active']) && strlen($data['active'])) {
            $filter['gtr_active'] = $data['active'];
        }

        return $filter;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Tracks');
    }

    public function showAction()
    {
        $tracker     = $this->loader->getTracker();
        $trackId     = $this->_getIdParam();
        $trackEngine = $tracker->getTrackEngine($trackId);

        // Set variables for the menu
        $trackEngine->applyToMenuSource($this->menu->getParameterSource());

        // $this->addSnippets($tracker->getTrackEngineEditSnippets(), 'trackEngine', $trackEngine, 'trackId', $trackId);
        parent::showAction();

        $this->showList("fields", array(MUtil_Model::REQUEST_ID => 'gtf_id_track', 'fid' => 'gtf_id_field'));
        $this->showList("rounds", array(MUtil_Model::REQUEST_ID => 'gro_id_track', 'rid' => 'gro_id_round'), 'row_class');

        // explicitly translate
        $this->_('fields');
        $this->_('rounds');
    }

    /**
     * Shows a list
     * Enter description here ...
     * @param string $mode
     * @param array $keys
     */
    private function showList($mode, array $keys, $rowclassField = null)
    {
        $action = $this->getRequest()->getActionName();
        $this->getRequest()->setActionName($mode);

        $baseurl = array('action' => $mode) + $this->getRequest()->getParams();

        $model = $this->getModel();
        $repeatable = $model->loadRepeatable();

        $this->browseMode = 'track-' . $mode;

        $table = $this->getBrowseTable($baseurl);
        if ($rowclassField) {
            foreach ($table->tbody() as $tr) {
                $tr->appendAttrib('class', $repeatable->$rowclassField);
            }
        }
        $table->setOnEmpty(sprintf($this->_('No %s found'), $this->_($mode)));
        $table->getOnEmpty()->class = 'centerAlign';
        $table->setRepeater($repeatable);

        $this->html->h3(sprintf($this->_('%s in track'), $this->_(ucfirst($mode))));
        $this->html[] = $table;
        $this->html->actionLink(array('controller' => 'track-' . $mode, 'action' => 'create', 'id' => $this->getRequest()->getParam(MUtil_Model::REQUEST_ID)), $this->_('Add'));

        $this->getRequest()->setActionName($action);
    }
}
