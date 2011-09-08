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
 */

/**
 * 
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package Gems
 * @subpackage Default
 */

/**
 * 
 * @author Matijs de Jong
 * @package Gems
 * @subpackage Default
 */
class Gems_Default_GroupAction  extends Gems_Controller_BrowseEditAction
{
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
        $bridge->addHidden('ggp_id_group');
        $bridge->addText('ggp_name', 'size', 15, 'minlength', 4, 'validator', $model->createUniqueValidator('ggp_name'));
        $bridge->addText('ggp_description', 'size', 40);
        $bridge->addSelect('ggp_role', 'disableTranslator', true);
        $bridge->addCheckbox('ggp_group_active');
        $bridge->addCheckbox('ggp_staff_members');
        $bridge->addCheckbox('ggp_respondent_members');
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
        $model = new MUtil_Model_TableModel('gems__groups');

        $model->set('ggp_name', 'label', $this->_('Name'));
        $model->set('ggp_description', 'label', $this->_('Description'));
        $model->set('ggp_role', 'label', $this->_('Role'), 'multiOptions', $this->util->getDbLookup()->getRoles());

        $yesNo = $this->util->getTranslated()->getYesNo();
        $model->set('ggp_group_active', 'label', $this->_('Active'), 'multiOptions', $yesNo);
        $model->set('ggp_staff_members', 'label', $this->_('Staff'), 'multiOptions', $yesNo);
        $model->set('ggp_respondent_members', 'label', $this->_('Respondents'), 'multiOptions', $yesNo);

        Gems_Model::setChangeFieldsByPrefix($model, 'ggp');

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('group', 'groups', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Administrative groups');
    }
}
