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
 * @author     Michel Rooks <info@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Default_TrackFieldsAction extends Gems_Controller_ModelSnippetActionAbstract
{
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
        'extraSort' => array('gtf_id_order' => SORT_ASC),
        );

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('track', 'tracks');

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'ModelFormSnippetGeneric';

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array(
        'Generic_ContentTitleSnippet',
        'Gems_Tracker_Snippets_Fields_FieldsAutosearchForm'
        );

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
        $request = $this->getRequest();
        $data    = $request->getParams();
        $trackId = $this->_getIdParam();
        $engine  = $this->loader->getTracker()->getTrackEngine($trackId);

        $model   = $engine->getFieldsMaintenanceModel($detailed, $action, $data);

        return $model;
    }

    /**
     * Creates a form to delete a record
     *
     * Uses $this->getModel()
     *      $this->addFormElements()
     * /
    public function deleteAction()
    {
        $field = $this->_getParam('fid');
        $used  = $this->db->fetchOne("SELECT COUNT(*) FROM gems__respondent2track2field WHERE gr2t2f_id_field = ? AND gr2t2f_value IS NOT NULL", $field);

        if ($this->isConfirmedItem($this->_('Delete %s'))) {
            $model   = $this->getModel();
            $deleted = $model->delete();

            // Always perform delete, fields may be empty
            $this->db->delete('gems__respondent2track2field', $this->db->quoteInto('gr2t2f_id_field = ?', $field));

            $this->addMessage(sprintf($this->_('%2$u %1$s deleted'), $this->getTopic($deleted), $deleted));

            if ($used) {
                $this->addMessage(sprintf($this->plural('Field also deleted from %s assigned track.', 'Field also deleted from %s assigned tracks.', $used), $used));
            }

            $this->_reroute(array('action' => 'index', MUtil_Model::REQUEST_ID => $this->_getParam(MUtil_Model::REQUEST_ID)), true);

        } elseif ($used) {
            $this->addMessage(sprintf($this->plural('This field will be deleted from %s assigned track.', 'This field will be deleted from %s assigned tracks.', $used), $used));
        }
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return sprintf($this->_('Fields %s'), $this->util->getTrackData()->getTrackTitle($this->_getIdParam()));
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('field', 'fields', $count);
    }

    /**
     * Set the menu
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        // Make sure the menu knows the track type
        $source = $this->menu->getParameterSource();
        $source->setTrackType($this->db->fetchOne('SELECT gtr_track_type FROM gems__tracks WHERE gtr_id_track = ?', $this->_getIdParam()));

        return parent::init();
    }
}