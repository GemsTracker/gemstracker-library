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
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: StaffAction.php 460 2011-08-31 16:17:26Z mjong $
 */

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_StaffAction  extends Gems_Controller_BrowseEditAction // implements Gems_Menu_ParameterSourceInterface
{
    public $filterStandard = array('gsf_active' => 1);
    public $sortKey = array('name' => SORT_ASC);

    protected $_instanceId;
    protected $_organizations;

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
        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('show')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }
        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addSortable($name, $label);
            }
        }
        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('edit')) {
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
        $dbLookup = $this->util->getDbLookup();

        $model->set('gsf_id_primary_group', 'multiOptions', MUtil_Lazy::call($dbLookup->getActiveStaffGroups));
        if ($new) {
            $model->set('gsf_id_primary_group', 'default', $dbLookup->getDefaultGroup());
        } else {
            $model->set('gsf_password', 'description', $this->_('Enter only when changing'));
            $model->setSaveWhenNotNull('gsf_password');
        }
        $model->setOnSave('gsf_password', array($this->escort, 'passwordHash'));

        $ucfirst = new Zend_Filter_Callback('ucfirst');

        $bridge->addHidden(  'gsf_id_user');
        $bridge->addText(    'gsf_login', 'size', 15, 'minlength', 4,
            'validator', $model->createUniqueValidator('gsf_login'));

        $bridge->addPassword('gsf_password',
            'label', $this->_('Password'),
            'minlength', $this->project->passwords['MinimumLength'],
            // 'renderPassword', true,
            'repeatLabel', $this->_('Repeat password'),
            'required', $new,
            'size', 15
            );
        $bridge->addRadio(   'gsf_gender',         'separator', '');
        $bridge->addText(    'gsf_first_name',     'label', $this->_('First name'));
        $bridge->addFilter(  'gsf_first_name',     $ucfirst);
        $bridge->addText(    'gsf_surname_prefix', 'label', $this->_('Surname prefix'), 'description', 'de, van der, \'t, etc...');
        $bridge->addText(    'gsf_last_name',      'label', $this->_('Last name'), 'required', true);
        $bridge->addFilter(  'gsf_last_name',      $ucfirst);
        $bridge->addText(    'gsf_email', array('size' => 30))->addValidator('SimpleEmail')->addValidator($model->createUniqueValidator('gsf_email'));

        if ($this->escort->hasPrivilege('pr.staff.edit.all')) {
            $this->_groups = MUtil_Lazy::call($dbLookup->getStaffGroups);

            $bridge->addSelect('gsf_id_organization');
            $bridge->addSelect('gsf_id_primary_group');
        } else {
            $sql = "SELECT ggp_id_group, ggp_name FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 AND ggp_role != 'super' ORDER BY ggp_name";
            $this->_groups = MUtil_Lazy::call(array($this->db, 'fetchPairs'), $sql);

            $bridge->addExhibitor('gsf_id_organization');
            $bridge->addSelect(  'gsf_id_primary_group', 'multiOptions', $dbLookup->getStaffGroupsNoSuper());
        }
        $bridge->addCheckbox('gsf_logout_on_survey', 'description', $this->_('If checked the user will logoff when answering a survey.'));

        $bridge->addSelect('gsf_iso_lang');
    }

    public function afterFormLoad(array &$data, $isNew)
    {
        if (array_key_exists('gsf_login', $data)) {
            $this->_instanceId = $data['gsf_login'];
        }

        $sql = "SELECT ggp_id_group,ggp_role FROM gems__groups WHERE ggp_id_group = " . (int) $data['gsf_id_primary_group'];
        $groups = $this->db->fetchPairs($sql);

        if (($this->session->user_role == 'admin' && isset($groups) && $groups[$data['gsf_id_primary_group']] == 'super')
            || (!$this->escort->hasPrivilege('pr.staff.edit.all') &&
             $data['gsf_id_organization'] != $this->escort->getCurrentOrganization())) {
                throw new Zend_Exception($this->_('You are not allowed to edit this staff member.'));
        }
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
        // MUtil_Model::$verbose = true;

        $model = new MUtil_Model_TableModel('gems__staff');
        //$model->resetOrder();

        $model->set('gsf_login',            'label', $this->_('Login'));
        $model->set('name',                 'label', $this->_('Name'),
            'column_expression', "CONCAT(COALESCE(CONCAT(gsf_last_name, ', '), '-, '), COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(gsf_surname_prefix, ''))");
        $model->set('gsf_email',            'label', $this->_('E-Mail'), 'itemDisplay', 'MUtil_Html_AElement::ifmail');

        $model->set('gsf_id_organization',  'label', $this->_('Organization'),
            'multiOptions', $this->util->getDbLookup()->getOrganizations(),
            'default', $this->escort->getCurrentOrganization());

        $model->set('gsf_id_primary_group', 'label', $this->_('Primary function'), 'multiOptions', MUtil_Lazy::call($this->util->getDbLookup()->getStaffGroups));
        $model->set('gsf_gender',           'label', $this->_('Gender'), 'multiOptions', $this->util->getTranslated()->getGenders());

        if ($detailed) {
            $model->set('gsf_iso_lang',         'label', $this->_('Language'), 'multiOptions', $this->util->getLocalized()->getLanguages());
            $model->set('gsf_logout_on_survey', 'label', $this->_('Logout on survey'), 'multiOptions', $this->util->getTranslated()->getYesNo());
        }

        $model->setDeleteValues('gsf_active', 0);

        Gems_Model::setChangeFieldsByPrefix($model, 'gsf');

        return $model;
    }

    protected function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements = parent::getAutoSearchElements($model, $data);

        // Select organization
        $options = array('' => $this->_('(all organizations)')) + $this->getModel()->get('gsf_id_organization', 'multiOptions');
        $select = new Zend_Form_Element_Select('gsf_id_organization', array('multiOptions' => $options));

        // Position as second element
        $search = array_shift($elements);
        array_unshift($elements, $search, $select);

        return $elements;
    }

    public function getInstanceId()
    {
        if ($this->_instanceId) {
            return $this->_instanceId;
        }

        return parent::getInstanceId();
    }

    /*
    public function getMenuParameter($name, $default)
    {

    } // */

    public function getTopic($count = 1)
    {
        return $this->plural('staff member', 'staff members', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Staff');
    }
}
