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
 * @version    $Id: TrackFieldsAction.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Default_TrackFieldsAction  extends Gems_Controller_BrowseEditAction
{
    public $sortKey = array('gtf_id_order' => SORT_ASC);

    public $summarizedActions = array('index', 'autofilter');

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
        $bridge->addHidden('gtf_id_field');
        $bridge->addExhibitor('gtf_id_track');
        $bridge->addText('gtf_id_order');
        $bridge->addText('gtf_field_name', 'size', '30', 'minlength', 4, 'required', true, 'validator', $model->createUniqueValidator(array('gtf_field_name','gtf_id_track')));
        $bridge->addTextarea('gtf_field_values', 'minlength', 4, 'rows', 4, 'description', $this->_('Separate multiple values with a vertical bar (|)'), 'required', false);
        $bridge->addSelect('gtf_field_type');
        $bridge->addCheckBox('gtf_required');
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
        $types = array('select' => $this->_('Select one'), 'multiselect' => $this->_('Select multiple'), 'text' => $this->_('Free text'));

        $model = new MUtil_Model_TableModel('gems__track_fields');
        $model->setKeys(array('fid' => 'gtf_id_field', MUtil_Model::REQUEST_ID => 'gtf_id_track'));
        $model->set('gtf_id_track', 'label', $this->_('Track'), 'multiOptions', $this->util->getTrackData()->getAllTracks());
        $model->set('gtf_id_order', 'label', $this->_('Order'));
        $model->set('gtf_field_name', 'label', $this->_('Name'));
        $model->set('gtf_field_values', 'label', $this->_('Values'));
        $model->set('gtf_field_type', 'label', $this->_('Type'), 'multiOptions', $types);
        $model->set('gtf_required', 'label', $this->_('Required'), 'multiOptions', $this->util->getTranslated()->getYesNo());

        Gems_Model::setChangeFieldsByPrefix($model, 'gtf');

        if ($trackId) {
            $model->set('gtf_id_track', 'default', $trackId);
        }

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('field', 'fields', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Fields');
    }
}