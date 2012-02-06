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
    public $sortKey = array('gtr_track_name' => SORT_ASC);

    public $summarizedActions = array('index', 'autofilter', 'check-all');

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

        if ($request->getActionName() == 'index') {
            if ($menuItem = $this->findAllowedMenuItem('show')) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
            }

            $menuItem = $this->findAllowedMenuItem('edit');
        } else {
            $menuItem = null;
        }

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

    public function checkAllAction()
    {
        $model = $this->getModel();
        $data = $model->load(null, $this->sortKey);

        if ($this->_getParam('confirmed')) {
            $this->checkTrack();
            $this->afterSaveRoute($this->getRequest());
        }

        $this->addMessage($this->_('This may take a while!'));
        $this->html->h3($this->_('Check all tracks'));
        $this->html->pInfo($this->_('Checking all tracks will update all existing rounds to the current surveys in the tracks instead of those in use when the track was created.'));
        $this->html->pInfo($this->_('Completed tracks will not be changed. No new tokens will be created when later tokens were completed.'));
        if ($data) {
            $rdata = MUtil_Lazy::repeat($data);
            $table = $this->html->table($rdata, array('class' => 'browser'));
            $table->th($this->getTopicTitle());
            $table->td()->a(array('action' => 'show', MUtil_Model::REQUEST_ID => $rdata->gtr_id_track), $rdata->gtr_track_name);

            $this->html->h4('Are you sure you want to check all tracks?');
            $this->html->actionLink(array('confirmed' => 1), $this->_('Yes'));
            $this->html->actionLink(array('action' => 'index'), $this->_('No'));
        } else {
            $this->html->pInfo(sprintf($this->_('No %s found'), $this->getTopic(0)));
        }
        $this->html->actionLink(array('action' => 'index'), $this->_('Cancel'));
    }

    public function checkTrack($cond = null)
    {
        $tracker = $this->loader->getTracker();
        $this->addMessage($tracker->checkTrackRounds($this->session->user_id, $cond));
    }

    public function checkTrackAction()
    {
        $this->checkTrack($this->db->quoteInto('gr2t_id_track = ?', $this->_getIdParam()));
        $this->afterSaveRoute($this->getRequest());
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
            } break;

            case "fields": {
                $model = new MUtil_Model_TableModel('gems__track_fields');
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
        $this->showList("rounds", array(MUtil_Model::REQUEST_ID => 'gro_id_track', 'rid' => 'gro_id_round'));

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
    private function showList($mode, array $keys)
    {
        $action = $this->getRequest()->getActionName();
        $this->getRequest()->setActionName($mode);

        $baseurl = array('action' => $mode) + $this->getRequest()->getParams();

        $model = $this->getModel();
        $repeatable = $model->loadRepeatable();

        $table = $this->getBrowseTable($baseurl);
        $table->setOnEmpty(sprintf($this->_('No %s found'), $this->_($mode)));
        $table->getOnEmpty()->class = 'centerAlign';
        $table->setRepeater($repeatable);

        $url = array(
        	'controller' => 'track-' . $mode,
            'action' => 'edit'
        );

        foreach ($keys as $idx => $key) {
            $url[$idx] = $repeatable->$key;
        }

        $href = new MUtil_Html_HrefArrayAttribute($url);
        $body = $table->tbody();
        $body[0]->onclick = array('location.href=\'', $href, '\';');

        $this->html->h3(sprintf($this->_('%s in track'), $this->_(ucfirst($mode))));
        $this->html[] = $table;
        $this->html->actionLink(array('controller' => 'track-' . $mode, 'action' => 'create', 'id' => $this->getRequest()->getParam(MUtil_Model::REQUEST_ID)), $this->_('Add'));

        $this->getRequest()->setActionName($action);
    }
}
