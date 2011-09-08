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
 * @author Michiel Rook
 * @since 1.0
 * @version 1.3
 * @package Gems
 * @subpackage Default
 */

/**
 * 
 * @author Michiel Rook
 * @package Gems
 * @subpackage Default
 */
class Gems_Default_RoleAction  extends Gems_Controller_BrowseEditAction
{
    
    /**
     * Check the disabled (=inherited) privileges
     * 
     * @param Gems_Form $form
     * @param boolean $isNew
     * @return Gems_Form
     */
    public function beforeFormDisplay($form, $isNew) {
        $form = parent::beforeFormDisplay($form, $isNew);
        $checkbox = $form->getElement('grl_privileges');
        $values = $checkbox->getValue();
        $disabled = $checkbox->getAttrib('disable');
        
        $values = array_merge($values, $disabled);
        $checkbox->setValue($values);
        return $form;
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
        if (isset($data['grl_parents'])) {
            $data['grl_parents'] = implode(',', $data['grl_parents']);
        }

        if (isset($data['grl_privileges'])) {
            $data['grl_privileges'] = implode(',', $data['grl_privileges']);
        }
        
        return true;
    }
    
    /**
     * @param array $data
     * @param bool  $isNew
     * @return array
     */
    public function afterFormLoad(array &$data, $isNew)
    {
        if (isset($data['grl_parents']) && (! is_array($data['grl_parents']))) {
            $data['grl_parents'] = explode(',', $data['grl_parents']);
        }

        if (isset($data['grl_privileges']) && (! is_array($data['grl_privileges']))) {
            $data['grl_privileges'] = explode(',', $data['grl_privileges']);
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
     * @param optional boolean $new
     * @return void
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        $bridge->addHidden('grl_id_role');
        $bridge->addText('grl_name', 'size', 15, 'minlength', 4, 'validator', $model->createUniqueValidator('grl_name'));
        $bridge->addText('grl_description', 'size', 40);

        $roles = $this->acl->getRoles();
        $parents = array_combine($roles, $roles);
        $bridge->addMultiCheckbox('grl_parents', 'disableTranslator', true, 'multiOptions', $parents, 'required', false);

        $checkbox = $bridge->addMultiCheckbox('grl_privileges', 'disableTranslator', true, 'multiOptions', $this->getUsedPrivileges(), 'required', false);

        //Get inherited privileges and disable tem
        $result = $this->escort->acl->getRolePrivileges();
        $disable = array();
        foreach($result[$data['grl_name']][MUtil_Acl::INHERITED][Zend_Acl::TYPE_ALLOW] as $key=>$value) {
            $disable[] = $value;
        }       
        $checkbox->setAttrib('disable', $disable);

        //Don't use escaping, so the line breaks work
        $checkbox->setAttrib('escape', false);
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
        $model = new MUtil_Model_TableModel('gems__roles');

        $model->set('grl_name', 'label', $this->_('Name'));
        $model->set('grl_description', 'label', $this->_('Description'));
        $model->set('grl_parents', 'label', $this->_('Parents'));
        $model->set('grl_privileges', 'label', $this->_('Privileges'), 'formatFunction', array($this, 'formatLongLine'));

        Gems_Model::setChangeFieldsByPrefix($model, 'grl');
        
        return $model;
    }
    
    public function formatLongLine($line)
    {
        if (strlen($line) > 50) {
            return substr($line, 0, 50) . '...';       
        } else {
            return $line;
        }
    }

    public function getTopic($count = 1)
    {
        return $this->plural('role', 'roles', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Administrative roles');
    }
    
    protected function getUsedPrivileges()
    {
        $privileges = $this->menu->getUsedPrivileges();

        asort($privileges);
        return $privileges;
    }    
}
