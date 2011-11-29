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
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_StaffAction extends Gems_Controller_BrowseEditAction
{
    public $defaultStaffDefinition = Gems_User_UserLoader::USER_STAFF;

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
        // Sorry, for the time being no password complexity checking on new
        // users. Can be done, but is to complex for the moment.
        if ($new) {
            $user = false;
        } else {
            $user = $this->loader->getUserLoader()->getUserByStaffId($data['gsf_id_user']);
            // MUtil_Echo::track($data['gsf_id_user'], $user->getLoginName());
        }
        $dbLookup = $this->util->getDbLookup();

        switch ($data['gul_user_class']) {
            case Gems_User_UserLoader::USER_STAFF:
                Gems_Model::addUserPassword($model);
                $passwordField = 'gup_password';
                $model->setOnSave($passwordField, array($this->project, 'getValueHash'));
                break;

            case Gems_User_UserLoader::USER_OLD_STAFF:
                $passwordField = 'gsf_password';
                $model->setOnSave($passwordField, array($this, 'getOldPasswordHash'));
                break;

            default:
                $passwordField = false;
                break;
        }

        $model->set('gsf_id_primary_group', 'multiOptions', MUtil_Lazy::call($dbLookup->getAllowedStaffGroups));
        if ($new) {
            $model->set('gsf_id_primary_group', 'default', $dbLookup->getDefaultGroup());
        } elseif ($passwordField) {
            $model->set($passwordField, 'description', $this->_('Enter only when changing'));
            $model->setSaveWhenNotNull($passwordField);
        }

        $ucfirst = new Zend_Filter_Callback('ucfirst');

        $bridge->addHidden(  'gsf_id_user');
        $bridge->addHidden(  'gul_id_user');
        $bridge->addHidden(  'gup_id_user');
        $bridge->addHidden(  'gul_user_class');
        $bridge->addText(    'gsf_login', 'size', 15, 'minlength', 4,
            'validator', $model->createUniqueValidator('gsf_login', array('gsf_id_user')));

        // Can the organization be changed?
        if ($this->escort->hasPrivilege('pr.staff.edit.all')) {
            $bridge->addHiddenMulti($model->getKeyCopyName('gsf_id_organization'));
            $bridge->addSelect('gsf_id_organization');
        } else {
            $bridge->addExhibitor('gsf_id_organization');
        }

        if ($passwordField) {
            $pwdElem = $bridge->addPassword($passwordField,
                'label', $this->_('Password'),
                // 'renderPassword', true,
                'repeatLabel', $this->_('Repeat password'),
                'required', $new,
                'size', 15
                );

            if ($user instanceof Gems_User_User) {
                $pwdElem->addValidator(new Gems_User_UserNewPasswordValidator($user));
            }
        }
        $bridge->addRadio(   'gsf_gender',         'separator', '');
        $bridge->addText(    'gsf_first_name',     'label', $this->_('First name'));
        $bridge->addFilter(  'gsf_first_name',     $ucfirst);
        $bridge->addText(    'gsf_surname_prefix', 'label', $this->_('Surname prefix'), 'description', 'de, van der, \'t, etc...');
        $bridge->addText(    'gsf_last_name',      'label', $this->_('Last name'), 'required', true);
        $bridge->addFilter(  'gsf_last_name',      $ucfirst);
        $bridge->addText(    'gsf_email', array('size' => 30))->addValidator('SimpleEmail')->addValidator($model->createUniqueValidator('gsf_email'));

        $bridge->addSelect('gsf_id_primary_group');
        $bridge->addCheckbox('gul_can_login', 'description', $this->_('Users can only login when this box is checked.'));
        $bridge->addCheckbox('gsf_logout_on_survey', 'description', $this->_('If checked the user will logoff when answering a survey.'));

        $bridge->addSelect('gsf_iso_lang');
    }

    public function afterFormLoad(array &$data, $isNew)
    {
        if (array_key_exists('glf_login', $data)) {
            $this->_instanceId = $data['gsf_login'];
        }

        $sql = "SELECT ggp_id_group,ggp_role FROM gems__groups WHERE ggp_id_group = " . (int) $data['gsf_id_primary_group'];
        $groups = $this->db->fetchPairs($sql);

        if (! ($this->escort->hasPrivilege('pr.staff.edit.all') ||
             $data['gsf_id_organization'] == $this->escort->getCurrentOrganization())) {
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

        $model = $this->loader->getModels()->getStaffModel();

        $model->set('gsf_login',            'label', $this->_('Login'));
        $model->set('name',                 'label', $this->_('Name'),
            'column_expression', "CONCAT(COALESCE(CONCAT(gsf_last_name, ', '), '-, '), COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(gsf_surname_prefix, ''))");
        $model->set('gsf_email',            'label', $this->_('E-Mail'), 'itemDisplay', 'MUtil_Html_AElement::ifmail');

        if ($detailed || $this->escort->hasPrivilege('pr.staff.see.all')) {
            $this->menu->getParameterSource()->offsetSet('gsf_id_organization', $this->escort->getCurrentOrganization());

            $model->set('gsf_id_organization',  'label', $this->_('Organization'),
                'multiOptions', $this->util->getDbLookup()->getOrganizations(),
                'default', $this->escort->getCurrentOrganization());
        }

        $model->set('gsf_id_primary_group', 'label', $this->_('Primary function'), 'multiOptions', MUtil_Lazy::call($this->util->getDbLookup()->getStaffGroups));
        $model->set('gsf_gender',           'label', $this->_('Gender'), 'multiOptions', $this->util->getTranslated()->getGenders());

        if ($detailed) {
            $model->set('gul_user_class',       'default', $this->defaultStaffDefinition);
            $model->set('gsf_iso_lang',         'label', $this->_('Language'), 'multiOptions', $this->util->getLocalized()->getLanguages());
            $model->set('gul_can_login',        'label', $this->_('Can login'), 'multiOptions', $this->util->getTranslated()->getYesNo());
            $model->set('gsf_logout_on_survey', 'label', $this->_('Logout on survey'), 'multiOptions', $this->util->getTranslated()->getYesNo());
        }

        $model->setDeleteValues('gsf_active', 0, 'gul_can_login', 0);

        return $model;
    }

    protected function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements = parent::getAutoSearchElements($model, $data);

        if ($this->escort->hasPrivilege('pr.staff.see.all')) {
            // Select organization
            $options = array('' => $this->_('(all organizations)')) + $this->getModel()->get('gsf_id_organization', 'multiOptions');
            $select = new Zend_Form_Element_Select('gsf_id_organization', array('multiOptions' => $options));

            // Position as second element
            $search = array_shift($elements);
            array_unshift($elements, $search, $select);
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

        if (! $this->escort->hasPrivilege('pr.staff.see.all')) {
            $filter['gsf_id_organization'] = $this->escort->getCurrentOrganization();
        }
        return $filter;
    }

    public function getInstanceId()
    {
        if ($this->_instanceId) {
            return $this->_instanceId;
        }

        return parent::getInstanceId();
    }

    /**
     * Return an old style (< 1.5) hashed version of the input value.
     *
     * @param string $value The value to hash.
     * @param boolean $new Optional is new, is here for ModelAbstract setOnSave compatibility
     * @param string $name Optional name, is here for ModelAbstract setOnSave compatibility
     * @param array $context Optional, the other values being saved
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function getOldPasswordHash($value, $isNew = false, $name = null, array $context = array())
    {
        return md5($value);
    }

    public function getTopic($count = 1)
    {
        return $this->plural('staff member', 'staff members', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Staff');
    }
}
