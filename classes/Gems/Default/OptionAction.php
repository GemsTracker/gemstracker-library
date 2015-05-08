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
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_OptionAction extends \Gems_Controller_BrowseEditAction
{
    /**
     *
     * @var boolean
     */
    public $autoFilter = false;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->add($name);
            }
        }
    }

    /**
     * Hook to perform action after a record (with changes) was saved
     *
     * As the data was already saved, it can NOT be changed anymore
     *
     * @param array $data
     * @param boolean $isNew
     * @return boolean  True when you want to display the default 'saved' messages
     */
    public function afterSave(array $data, $isNew)
    {
        // Reload the current user data
        $user = $this->loader->getCurrentUser();
        if ($user->getLoginName() === $data['gsf_login']) {
            $currentOrg = $user->getCurrentOrganizationId();

            $this->loader->getUserLoader()->unsetCurrentUser();
            $user = $this->loader->getUser($data['gsf_login'], $data['gsf_id_organization'])->setAsCurrentUser();
            $user->setCurrentOrganization($currentOrg);

            // If locale has changed, set it in a cookie
            \Gems_Cookies::setLocale($data['gsf_iso_lang'], GemsEscort::getInstance()->basepath);
            $this->_reroute();      // Force refresh
        }
    }

    /**
     *
     * @param array $data
     * @param boolean $isNew
     * @param \Zend_Form $form
     * @return boolean
     */
    public function beforeSave(array &$data, $isNew, \Zend_Form $form = null)
    {
        // fix privilege escalation

        // first load the current record from the database
        $model = $this->getModel();
        $model->setFilter(array('gsf_id_user' => $this->loader->getCurrentUser()->getUserId()));
        $databaseData = $model->loadFirst();

        // Now only take values from elements that allow input (exclude hidden)
        $validData = array();
        foreach($form->getElements() as $element) {
            if (! ($element instanceof \Zend_Form_Element_Hidden || $element instanceof \Zend_Form_Element_Submit)) {
                $validData[$element->getName()] = $data[$element->getName()];
            }
        }

        // Now add the other fields back from the current record so we have all id's etc.
        $data = $validData + $databaseData;

        return true;
    }

    /**
     * Allow a user to change his / her password.
     */
    public function changePasswordAction()
    {
        $user = $this->loader->getCurrentUser();

        $this->html->h3($this->_('Change password'));

        if (! $user->canSetPassword()) {
            $this->addMessage($this->_('You are not allowed to change your password.'));
            return;
        }

        /*************
         * Make form *
         *************/
        $form = $user->getChangePasswordForm(array('showReport' => false, 'useTableLayout' => true));

        /****************
         * Process form *
         ****************/
        if ($this->_request->isPost() && $form->isValid($_POST, false)) {
            $this->addMessage($this->_('New password is active.'));
			$user->gotoStartPage($this->menu, $this->getRequest());
            $this->_reroute(array($this->getRequest()->getActionKey() => 'edit'));
        } else {
            $this->addMessage($form->getErrorMessages());
        }

        /****************
         * Display form *
         ****************/
        if ($user->isPasswordResetRequired()) {
            $this->menu->setVisible(false);
        } else {
            $form->addButtons($this->createMenuLinks());
        }

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
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getStaffModel();

        $noscript = new \MUtil_Validate_NoScript();

        $model->set('gsf_login',          'label', $this->_('Login Name'), 'elementClass', 'Exhibitor');
        $model->set('gsf_email',          'label', $this->_('E-Mail'), 'size', 30,
                'validator', new \MUtil_Validate_SimpleEmail());
        $model->set('gsf_first_name',     'label', $this->_('First name'), 'validator', $noscript);
        $model->set('gsf_surname_prefix', 'label', $this->_('Surname prefix'), 'description', 'de, van der, \'t, etc...', 'validator', $noscript);
        $model->set('gsf_last_name',      'label', $this->_('Last name'), 'required', true, 'validator', $noscript);
        $model->set('gsf_gender',         'label', $this->_('Gender'), 'multiOptions', $this->util->getTranslated()->getGenders(),
                'elementClass', 'Radio', 'separator', '');
        $model->set('gsf_iso_lang',       'label', $this->_('Language'), 'multiOptions', $this->util->getLocalized()->getLanguages());

        return $model;
    }

    public function editAction()
    {
        $this->getModel()->setFilter(array('gsf_id_user' => $this->loader->getCurrentUser()->getUserId()));

        if ($form = $this->processForm()) {
            $this->html->h3(sprintf($this->_('Options'), $this->getTopic()));
            $this->html[] = $form;
        }
    }

    public function overviewAction()
    {
        $filter['gla_by'] = $this->loader->getCurrentUser()->getUserId();

        $this->addSnippet('Generic\\ContentTitleSnippet',
                'contentTitle', $this->_('Activity overview')
                );
        $this->addSnippet('Log\\LogTableSnippet',
                'browse', true,
                'extraFilter', $filter
                );

        $this->html->p($this->_('This overview provides information about the last login activity on your account.'));
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
