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
class Gems_Default_OptionAction  extends Gems_Controller_BrowseEditAction
{
    public $autoFilter = false;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    public $project;

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
        $this->loader->getUser($data['gsf_login'], $data['gsf_id_organization']);
    }

    /**
     * Allow a user to change his / her password.
     */
    public function changePasswordAction()
    {
        $user = $this->loader->getCurrentUser();

        if (! $user->canSetPassword()) {
            $this->addMessage($this->_('You are not allowed to change your password.'));
            return;
        }

        /*************
         * Make form *
         *************/
        $form = $user->getPasswordChangeForm();

        // Show password info
        if ($info = $user->reportPasswordWeakness()) {
            $element = new MUtil_Form_Element_Html('rules');
            $element->setLabel($this->_('Password rules'));

            if (1 == count($info)) {
                $element->div(sprintf($this->_('A password %s.'), reset($info)));
            } else {
                foreach ($info as &$line) {
                    $line .= ',';
                }
                $line[strlen($line) - 1] = '.';

                $element->div($this->_('A password:'))->ul($info);
            }
            $form->addElement($element);
        }

        /****************
         * Process form *
         ****************/
        if ($this->_request->isPost() && $form->isValid($_POST, false)) {
            $user->setPassword($_POST['new_password']);

            $this->addMessage($this->_('New password is active.'));
            $this->_reroute(array($this->getRequest()->getActionKey() => 'edit'));

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

        if ($user->isPasswordResetRequired()) {
            $this->menu->setVisible(false);
        } elseif ($links = $this->createMenuLinks()) {
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
        $model = $this->loader->getModels()->getStaffModel();

        $model->set('gsf_login',          'label', $this->_('Login Name'), 'elementClass', 'Exhibitor');
        $model->set('gsf_email',          'label', $this->_('E-Mail'), 'size', 30);
        $model->set('gsf_first_name',     'label', $this->_('First name'));
        $model->set('gsf_surname_prefix', 'label', $this->_('Surname prefix'), 'description', 'de, van der, \'t, etc...');
        $model->set('gsf_last_name',      'label', $this->_('Last name'), 'required', true);
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
        $this->html->h3($this->_('Activity overview'));

        $this->html->p($this->_('This overview provides information about the last login activity on your account.'));
        $this->html->br();

        $sql = "SELECT glua.glua_remote_ip,UNIX_TIMESTAMP(glua.glua_created) AS glua_created
        FROM gems__log_actions glac LEFT JOIN gems__log_useractions glua
        ON glac.glac_id_action = glua_action AND glua_by = ?
        WHERE glac.glac_name = 'index.login'
        ORDER BY glua.glua_created DESC LIMIT 10";

        $activity = $this->db->fetchAll($sql, $this->loader->getCurrentUser()->getUserId());

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
