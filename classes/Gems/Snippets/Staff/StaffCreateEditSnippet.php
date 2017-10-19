<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Snippets\Staff;

use Gems_Snippets_ModelFormSnippetGeneric as ModelFormSnippetGeneric;

/**
 * Description of StaffCreateEditSnippet
 *
 * @author 175780
 */
class StaffCreateEditSnippet extends ModelFormSnippetGeneric
{
    
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

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
                            \MUtil_Model::REQUEST_ID       => $result['gsf_id_user']
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
    
    public function setAfterSaveRoute()
    {
        $user = $this->loader->getUser($this->formData['gsf_login'], $this->formData['gsf_id_organization']);
        
        if (!$user->canSetPassword()) {
            $this->routeAction = 'index';
        }
        
        return parent::setAfterSaveRoute();
    }

}
