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
class Gems_Default_OptionAction  extends Gems_Controller_BrowseEditAction
{
    public $autoFilter = false;

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
        $bridge->addHidden(   'gsu_id_user');
        $bridge->addHidden(   'gsu_id_organization');
        $bridge->addHidden(   'gsf_id_user');
        $bridge->addExhibitor('gsu_login', array('size' => 15, 'minlength' => 4));
        $bridge->addText(     'gsf_first_name');
        $bridge->addText(     'gsf_surname_prefix');
        $bridge->addText(     'gsf_last_name');
        $bridge->addText(     'gsf_email', array('size' => 30));

        $bridge->addRadio(    'gsf_gender', 'separator', '');

        $bridge->addSelect(   'gsf_iso_lang', array('label' => $this->_('Language'), 'multiOptions' => $this->util->getLocalized()->getLanguages()));
    }

    public function afterSave(array $data, $isNew)
    {
        $this->escort->loadLoginInfo($data['gsu_login']);
    }

    public function changePasswordAction()
    {
        /*************
         * Make form *
         *************/
        $form = $this->createForm();

        $sql = "SELECT CASE WHEN gsu_password IS NULL THEN 0 ELSE 1 END FROM gems__users WHERE gsu_id_user = ? AND gsu_id_organization = ?";
        if ($this->db->fetchOne($sql, array($this->session->user_id, $this->session->user_organization_id))) {
            // Veld current password
            $element = new Zend_Form_Element_Password('old_password');
            $element->setLabel($this->_('Current password'));
            $element->setAttrib('size', 10);
            $element->setAttrib('maxlength', 20);
            $element->setRenderPassword(true);
            $element->setRequired(true);
            $element->addValidator(new Gems_Validate_GemsPasswordUsername($this->session->user_login, 'old_password', $this->db));
            $form->addElement($element);
        }

        // Veld new password
        $element = new Zend_Form_Element_Password('new_password');
        $element->setLabel($this->_('New password'));
        $element->setAttrib('size', 10);
        $element->setAttrib('maxlength', 20);
        $element->setRequired(true);
        $element->setRenderPassword(true);
        $element->addValidator('StringLength', true, array('min' => $this->project->passwords['MinimumLength'], 'max' => 20));
        $element->addValidator(new MUtil_Validate_IsConfirmed('repeat_password', $this->_('Repeat password')));
        $form->addElement($element);

        // Veld repeat password
        $element = new Zend_Form_Element_Password('repeat_password');
        $element->setLabel($this->_('Repeat password'));
        $element->setAttrib('size', 10);
        $element->setAttrib('maxlength', 20);
        $element->setRequired(true);
        $element->setRenderPassword(true);
        $element->addValidator(new MUtil_Validate_IsConfirmed('new_password', $this->_('New password')));
        $form->addElement($element);

        $element = new Zend_Form_Element_Submit('submit');
        $element->setAttrib('class', 'button');
        $element->setLabel($this->_('Save'));
        $form->addElement($element);

        /****************
         * Process form *
         ****************/
        if ($this->_request->isPost() && $form->isValid($_POST)) {

            $data['gsu_id_user']         = $this->session->user_id;
            $data['gsu_id_organization'] = $this->session->user_organization_id;
            $data['gsu_password']        = $this->escort->passwordHash(null, $_POST['new_password']);

            $this->getModel()->save($data);

            // $data = $_POST;
            // $data['name'] = '';
            // $data['type'] = $this->_('raw');

            // $results = array();
            // $this->_runScript($data, $results);
            $this->addMessage($this->_('New password is active.'));
            $this->afterSaveRoute($this->getRequest());

        } else {
            if (isset($_POST['old_password'])) {
                if ($_POST['old_password'] === strtoupper($_POST['old_password'])) {
                    $this->addMessage($this->_('Caps Lock seems to be on!'));
                }
            }
            $form->populate($_POST);
        }

        /****************
         * Display form *
         ****************/
        $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
        $table->setAsFormLayout($form, true, true);
        $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.

        if ($links = $this->createMenuLinks()) {
            $table->tf(); // Add empty cell, no label
            $linksCell = $table->tf($links);
        }

        $this->html->h3($this->_('Change password'));
        $this->html[] = $form;
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
        $model = new Gems_Model_UserModel('staff', 'gems__staff', array('gsu_id_user' => 'gsf_id_user'), 'gsf');
        $model->copyKeys();

        $model->set('gsu_login',            'label', $this->_('Login Name'));
        $model->set('gsf_email',            'label', $this->_('E-Mail'));
        $model->set('gsf_first_name',       'label', $this->_('First name'));
        $model->set('gsf_surname_prefix',   'label', $this->_('Surname prefix'), 'description', 'de, van der, \'t, etc...');
        $model->set('gsf_last_name',        'label', $this->_('Last name'), 'required', true);

        $model->set('gsf_gender',           'label', $this->_('Gender'), 'multiOptions', $this->util->getTranslated()->getGenders());

        return $model;
    }

    public function editAction()
    {
        $this->getModel()->setFilter(array('gsu_id_user' => $this->session->user_id));

        if ($form = $this->processForm()) {
            $this->html->h3(sprintf($this->_('Options'), $this->getTopic()));
            $this->html[] = $form;
        }
    }

    public function overviewAction()
    {
        $this->html->h3($this->_('Activity overview'));

        $this->html->p($this->_('This overview provides information about the last login activity on your account.'));
        $this->html->br();

        $sql = "SELECT glua.glua_remote_ip,UNIX_TIMESTAMP(glua.glua_created) AS glua_created
        FROM gems__log_actions glac LEFT JOIN gems__log_useractions glua
        ON glac.glac_id_action = glua_action AND glua_by = ?
        WHERE glac.glac_name = 'index.login'
        ORDER BY glua.glua_created DESC LIMIT 10";

        $activity = $this->db->fetchAll($sql, $this->session->user_id);

        foreach (array_keys($activity) as $key) {
            $date = new MUtil_Date($activity[$key]['glua_created']);

            $activity[$key]['glua_created'] = (string) $date . " (" . $date->diffReadable(new Zend_Date(), $this->translate) . ")";
        }

        $this->addSnippet('SelectiveTableSnippet',
                'data', $activity,
                'class', 'browser',
                'columns', array('glua_remote_ip' => $this->_('IP address'), 'glua_created' => $this->_('Date / time'))
                );
    }

    public function getTopic($count = 1)
    {
        return $this->plural('item', 'items', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Item');
    }
}
