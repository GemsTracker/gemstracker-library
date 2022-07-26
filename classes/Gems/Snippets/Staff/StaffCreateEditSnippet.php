<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Snippets\Staff;

use Gems\Snippets\ModelFormSnippetGeneric as ModelFormSnippetGeneric;

/**
 * Description of StaffCreateEditSnippet
 *
 * @author 175780
 */
class StaffCreateEditSnippet extends ModelFormSnippetGeneric
{
    /**
     * When true this is the staff form
     *
     * @var boolean
     */
    protected $isStaff = true;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * When true we're switching from staff user to system user
     *
     * @var boolean
     */
    protected $switch = false;

     /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->switch) {
            if ($this->isStaff) {
                return $this->_('Save as staff');
            } else {
                return $this->_('Save as system user');
            }
        }
        return parent::getTitle();
    }

    /**
     * Hook that allows actions when the input is invalid
     *
     * When not rerouted, the form will be populated afterwards
     */
    protected function onInValid()
    {
        $form    = $this->_form;
        if ($element = $form->getElement('gsf_login')) {
            $errors = $element->getErrors();
            if (array_search('recordFound', $errors) !== false) {
                //We have a duplicate login!
                $model  = $this->getModel();
                $model->setFilter(array(
                    'gsf_login'           => $form->getValue('gsf_login'),
                    'gsf_id_organization' => $form->getValue('gsf_id_organization')
                ));
                $result = $model->load();

                if (count($result) == 1) {
                    $result = array_shift($result); //Get the first (only) row
                    if (($result['gsf_active'] == 0) || ($result['gul_can_login'] == 0)) {
                        //Ok we try to add an inactive user...
                        //now ask if this is the one we would like to reactivate?

                        $this->addMessage(sprintf($this->_('User with id %s already exists but is deleted, do you want to reactivate the account?'), $result['gsf_login']));
                        $this->afterSaveRouteUrl = array(
                            $this->request->getControllerKey() => $this->request->getControllerName(),
                            $this->request->getActionKey() => 'reactivate',
                            \MUtil\Model::REQUEST_ID       => $result['gsf_id_user']
                        );

                        return;
                    } else {
                        //User is active... this is a real duplicate so continue the flow
                    }
                }
            }
        }

        parent::onInValid();
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        if ($this->switch && $this->isStaff) {
            $this->formData['gsf_logout_on_survey'] = 0;
            $this->formData['gsf_is_embedded'] = 0;
        }

        parent::saveData();

        if (! $this->isStaff) {
            if (isset($this->formData['gul_two_factor_key'], $this->formData['gsf_id_user']) &&
                    $this->formData['gul_two_factor_key']) {

                $user = $this->loader->getUserLoader()->getUserByStaffId($this->formData['gsf_id_user']);

                if ($user->canSetPassword()) {
                    $this->addMessage(sprintf($this->_('Password saved for: %s'), $user->getLoginName()));
                    $user->setPassword($this->formData['gul_two_factor_key']);
                }
            }
        }
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil\Snippets\ModelFormSnippetAbstract (continuation pattern)
     */
    public function setAfterSaveRoute()
    {
        if ($this->switch) {
            $controller = $this->isStaff ? 'staff' : 'system-user';
            $this->afterSaveRouteUrl['controller'] = $controller;
            $this->routeAction = 'show';
            $this->resetRoute = true;
            $this->afterSaveRouteKeys = true;
        } else {
            $user = $this->loader->getUser($this->formData['gsf_login'], $this->formData['gsf_id_organization']);

            if (! $user->canSetPassword()) {
                $this->routeAction = 'show';
                $this->resetRoute = true;
                $this->afterSaveRouteKeys = true;
            }
        }

        parent::setAfterSaveRoute();
        if ($this->switch) {
            // Controller is reset in \MUtil\Snippets\ModelFormSnippetAbstract::setAfterSaveRoute()
            $this->afterSaveRouteUrl['controller'] = $controller;
        }
        return $this;
    }
}
