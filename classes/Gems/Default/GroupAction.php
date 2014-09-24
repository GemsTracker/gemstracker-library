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
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_GroupAction extends Gems_Controller_BrowseEditAction
{
    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_Bridge_FormBridgeInterface $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        $user      = $this->loader->getCurrentUser();
        $roles     = $model->get('ggp_role', 'multiOptions');
        $userRoles = $user->getAllowedRoles();

        // Make sure we get the roles as they are labeled
        foreach ($roles as $role => $label) {
            if (! isset($userRoles[$role])) {
                unset($roles[$role]);
            }
        }

        if ($data['ggp_role'] && (! $user->hasRole($data['ggp_role']))) {
            if ('create' === $this->getRequest()->getActionName()) {
                $data['ggp_role'] = reset($roles);
            } else {
                $this->addMessage($this->_('You do not have sufficient privilege to edit this group.'));
                $router = new Zend_Controller_Action_Helper_Redirector();
                $router->gotoRouteAndExit(array('action' => 'show'), null, false);
                return;
            }
        }

        $bridge->addHidden('ggp_id_group');
        $bridge->addText('ggp_name', 'size', 15, 'minlength', 4, 'validator', $model->createUniqueValidator('ggp_name'));
        $bridge->addText('ggp_description', 'size', 40);
        $bridge->addSelect('ggp_role', 'multiOptions', $roles);
        $bridge->addCheckbox('ggp_group_active');
        $options = array(
            '1'=>$model->get('ggp_staff_members', 'label'),
            '2'=>$model->get('ggp_respondent_members', 'label')
            );
        $bridge->addRadio('staff_respondent', 'label', $this->_('Can be assigned to'), 'multiOptions', $options);
        if (!isset($data['staff_respondent'])) {
            if (isset($data['ggp_staff_members']) && $data['ggp_staff_members'] == 1) {
                $data['staff_respondent'] = 1;
            } else if (isset($data['ggp_respondent_members']) && $data['ggp_respondent_members'] == 1) {
                $data['staff_respondent'] = 2;
            }
        }
        $bridge->addText('ggp_allowed_ip_ranges', 'size', 50, 'validator', new Gems_Validate_IPRanges(), 'maxlength', 500);

        return $data;
    }

    public function beforeSave(array &$data, $isNew, \Zend_Form $form = null)
    {
        $data['ggp_staff_members'] = 0;
        $data['ggp_respondent_members'] = 0;
        if (isset($data['staff_respondent'])) {
            if ($data['staff_respondent'] == 1) {
                $data['ggp_staff_members'] = 1;
            } elseif ($data['staff_respondent'] == 2) {
                $data['ggp_respondent_members'] = 1;
            }
            unset($data['staff_respondent']);
        }

        return parent::beforeSave($data, $isNew, $form);
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

        $model->set('ggp_allowed_ip_ranges',
            'label', $this->_('Allowed IP Ranges'),
            'description', $this->_('Separate with | example: 10.0.0.0-10.0.0.255 (subnet masks are not supported)')
            );

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
