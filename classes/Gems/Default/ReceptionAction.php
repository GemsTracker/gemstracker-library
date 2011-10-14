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
 * Controller for maintaining reception codes.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_ReceptionAction  extends Gems_Controller_BrowseEditAction
{
    public $sortKey = array('grc_id_reception_code' => SORT_ASC);

    public function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        $model->set('desc1', 'elementClass', 'Html', 'order', 55, 'label', $this->_('Can be assigned to'));
        $model->set('desc2', 'elementClass', 'Html', 'order', 85, 'label', $this->_('Additional action'));

        parent::addFormElements($bridge, $model, $data, $new);
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
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $yesNo = $this->util->getTranslated()->getYesNo();

        $model = new MUtil_Model_TableModel('gems__reception_codes');
        $model->copyKeys(); // The user can edit the keys.

        $model->set('grc_id_reception_code', 'label', $this->_('Code'), 'size', '10');
        $model->set('grc_description',       'label', $this->_('Description'), 'size', '20');

        $model->set('grc_success',           'label', $this->_('Is success code'),
            'multiOptions', $yesNo ,
            'disabled', true,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code is a success code.'));
        $model->set('grc_active',            'label', $this->_('Active'),
            'multiOptions', $yesNo ,
            'elementClass', 'CheckBox',
            'description', $this->_('Only active codes can be selected.'));
        $model->set('grc_for_respondents',   'label', $this->_('For respondents'),
            'multiOptions', $yesNo ,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code can be assigned to a respondent.'));
        $model->set('grc_for_tracks',        'label', $this->_('For tracks'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code can be assigned to a track.'));
        $model->set('grc_for_surveys',       'label', $this->_('For surveys'),
            'multiOptions', $yesNo ,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code can be assigned to a survey.'));
        $model->set('grc_redo_survey',       'label', $this->_('Redo survey'),
            'multiOptions', $this->util->getTranslated()->getRedoCodes(),
            'elementClass', 'Select',
            'description', $this->_('Redo a survey on this reception code.'));
        $model->set('grc_overwrite_answers', 'label', $this->_('Overwrite ansers'),
            'multiOptions', $yesNo ,
            'elementClass', 'CheckBox',
            'description', $this->_('Remove the consent from already answered surveys.'));

        if ($detailed) {
            $model->set('grc_id_reception_code', 'validator', $model->createUniqueValidator('grc_id_reception_code'));
            $model->set('grc_description',       'validator', $model->createUniqueValidator('grc_description'));
        }

        if ($this->project->multiLocale) {
            $model->set('grc_description',       'description', 'ENGLISH please! Use translation file to translate.');
        }

        Gems_Model::setChangeFieldsByPrefix($model, 'grc');

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('reception code', 'reception codes', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Reception codes');
    }
}
