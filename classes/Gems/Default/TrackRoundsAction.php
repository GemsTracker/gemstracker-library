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
 * @since      Class available since version 1.3
 */
class Gems_Default_TrackRoundsAction  extends Gems_Controller_BrowseEditAction
{
    /**
     *
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    public $sortKey = array('gro_id_order' => SORT_ASC);

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        parent::addBrowseTableColumns($bridge, $model);

        $table = $bridge->getTable();
        $rep   = $table->getRepeater();

        foreach ($table->tbody() as $tr) {
            $tr->appendAttrib('class', $bridge->row_class);
        }
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
        $elements[] = new Zend_Form_Element_Hidden(MUtil_Model::REQUEST_ID);

        return $elements;
    }

    /**
     * Create a new round
     */
    public function createAction()
    {
        $trackId = $this->_getIdParam();

        if (! $trackId) {
            throw new Gems_Exception($this->_('Missing track identifier.'));
        }

        $menuSource = $this->menu->getParameterSource();
        $trackEngine = $this->loader->getTracker()->getTrackEngine($trackId);
        $trackEngine->applyToMenuSource($menuSource);
        $menuSource->setRequestId($trackId); // Tell the menu we're using track id as request id

        $this->addSnippets($trackEngine->getRoundEditSnippetNames(), 'trackEngine', $trackEngine, 'trackId', $trackId, 'userId', $this->session->user_id);
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
        $trackId = $this->_getIdParam();

        if (! $trackId) {
            throw new Gems_Exception($this->_('Missing track identifier.'));
        }
        $menuSource = $this->menu->getParameterSource();

        $this->trackEngine = $this->loader->getTracker()->getTrackEngine($trackId);
        $this->trackEngine->applyToMenuSource($menuSource);
        $menuSource->setRequestId($trackId); // Tell the menu we're using track id as request id

        $model  = $this->trackEngine->getRoundModel($detailed, $action, $this->getRequest()->isPost() ? $_POST : null);
        $model->set('gro_id_track', 'default', $trackId);

        if ($detailed) {
            if ($action == 'create') {
                // Set the default round order
                $new_order = $this->db->fetchOne(
                        "SELECT MAX(gro_id_order) FROM gems__rounds WHERE gro_id_track = ?",
                        $this->_getParam(MUtil_Model::REQUEST_ID));

                if ($new_order) {
                    $model->set('gro_id_order', 'default', $new_order + 10);
                }
            }
        }

        return $model;
    }

    /**
     * Creates a form to delete a record
     *
     * Uses $this->getModel()
     *      $this->addFormElements()
     */
    public function deleteAction()
    {
        $roundId = $this->_getParam(Gems_Model::ROUND_ID);
        $used    = $this->db->fetchOne("SELECT COUNT(*) FROM gems__tokens WHERE gto_id_round = ? AND gto_start_time IS NOT NULL", $roundId);

        if ($used) {
            $title = $this->_('Deactivate %s');
        } else {
            $title = $this->_('Delete %s');
        }
        if ($this->isConfirmedItem($title)) {
            if ($used) {
                $deleted = $this->db->update('gems__rounds', array('gro_active' => 0), $this->db->quoteInto('gro_id_round = ?', $roundId));

                $this->addMessage(sprintf($this->_('%2$u %1$s deactivated'), $this->getTopic($deleted), $deleted));
                $this->_reroute(array('action' => 'show', 'confirmed' => null), false);
            } else {
                $model   = $this->getModel();
                $deleted = $model->delete();

                $this->addMessage(sprintf($this->_('%2$u %1$s deleted'), $this->getTopic($deleted), $deleted));
                $this->_reroute(array('action' => 'index', MUtil_Model::REQUEST_ID => $this->_getIdParam()), true);
            }
        } elseif ($used) {
            $this->addMessage(sprintf($this->plural('This round has been completed %s time.', 'This round has been completed %s times.', $used), $used));
            $this->addMessage($this->_('This round cannot be deleted, only deactivated.'));

        }
    }

    /**
     * Edit a single round
     */
    public function editAction()
    {
        $trackId = $this->_getIdParam();

        if (! $trackId) {
            throw new Gems_Exception($this->_('Missing track identifier.'));
        }

        $menuSource = $this->menu->getParameterSource();
        $trackEngine = $this->loader->getTracker()->getTrackEngine($trackId);
        $trackEngine->applyToMenuSource($menuSource);
        $menuSource->setRequestId($trackId); // Tell the menu we're using track id as request id

        $this->addSnippets($trackEngine->getRoundEditSnippetNames(), 'roundId', $this->_getParam(Gems_Model::ROUND_ID), 'trackEngine', $trackEngine, 'trackId', $trackId, 'userId', $this->session->user_id);
    }

    public function getTopic($count = 1)
    {
        return $this->plural('round', 'rounds', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Rounds');
    }

    public function isConfirmedItem($title, $question = null, $info = null)
    {
        $trackId = $this->_getIdParam();
        $roundId = $this->_getParam(Gems_Model::ROUND_ID);

        if (! $trackId) {
            throw new Gems_Exception($this->_('Missing track identifier.'));
        }

        if ($this->_getParam('confirmed')) {
            return true;
        }

        if (null === $question) {
            $question = $this->_('Are you sure?');
        }

        $this->html->h3(sprintf($title, $this->getTopic()));

        if ($info) {
            $this->html->pInfo($info);
        }

        $menuSource  = $this->menu->getParameterSource();
        $trackEngine = $this->loader->getTracker()->getTrackEngine($trackId);
        $trackEngine->applyToMenuSource($menuSource);
        $menuSource->setRequestId($trackId); // Tell the menu we're using track id as request id

        $this->html->p($question);
        foreach ($trackEngine->getRoundShowSnippetNames() as $snippet) {
            $this->html->append($this->getSnippet($snippet, 'roundId', $roundId, 'trackEngine', $trackEngine, 'trackId', $trackId, 'showMenu', false, 'showTitle', false));
        }

        $footer = $this->html->p($question, ' ', array('class' => 'centerAlign'));
        $footer->actionLink(array('confirmed' => 1), $this->_('Yes'));
        $footer->actionLink(array('action' => 'show'), $this->_('No'));

        $this->html->buttonDiv($this->createMenuLinks());

        return false;
    }

    /**
     * Show a single round
     */
    public function showAction()
    {
        $trackId = $this->_getIdParam();

        if (! $trackId) {
            throw new Gems_Exception($this->_('Missing track identifier.'));
        }

        $menuSource = $this->menu->getParameterSource();
        $trackEngine = $this->loader->getTracker()->getTrackEngine($trackId);
        $trackEngine->applyToMenuSource($menuSource);
        $menuSource->setRequestId($trackId); // Tell the menu we're using track id as request id

        $this->addSnippets($trackEngine->getRoundShowSnippetNames(), 'roundId', $this->_getParam(Gems_Model::ROUND_ID), 'trackEngine', $trackEngine, 'trackId', $trackId);
    }
}