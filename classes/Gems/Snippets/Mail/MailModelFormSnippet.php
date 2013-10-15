<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Snippets\Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $id MailModelFormSnippet.php
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Mail_MailModelFormSnippet extends Gems_Snippets_ModelFormSnippetGeneric 
{
    /**
     * 
     * @var Gems_Mail_MailElements
     */
    protected $mailElements;

    protected $mailer;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * 
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    public function afterRegistry() {
        $this->mailElements = $this->loader->getMailLoader()->getMailElements();
        $this->mailTargets = $this->loader->getMailLoader()->getMailTargets();

        $this->mailTarget = false;
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->mailTarget = $data['gct_target'];
        } else {
            reset($this->mailTargets);
            $this->mailTarget = key($this->mailTargets);
        }
        $this->mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget);
        parent::afterRegistry();
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $this->initItems();

        
        $this->addItems($bridge, 'gct_id_template');

        $bridge->addSelect(
                    'gct_target', 
                    array(
                        'label' => $this->translate->_('Mail target'),
                        'multiOptions' => $this->mailTargets,
                        'onchange' => 'this.form.submit()'
                    )
                );

        $this->addItems($bridge, 'gct_name', 'gctt_subject');
        $mailBody = $bridge->addElement($this->mailElements->createBodyElement('gctt_body', $this->translate->_('Message'), $model->get('gctt_body', 'required')));
        if ($mailBody instanceof Gems_Form_Element_CKEditor) {
            $mailBody->config['availablefields'] = $this->mailer->getMailFields();
            $mailBody->config['availablefieldsLabel'] = $this->translate->_('Fields');

            $mailBody->config['extraPlugins'] .= ',availablefields';
            $mailBody->config['toolbar'][] = array('availablefields');
        }

        $this->addItems($bridge, 'gct_code');
        $this->addItems($bridge, 'gctt_lang');

        $bridge->addFakeSubmit('preview', array('label' => $this->translate->_('Preview')));

        $bridge->addElement($this->mailElements->createEmailElement('to', $this->translate->_('To (test)'), true));
        $bridge->addElement($this->mailElements->createEmailElement('from', $this->translate->_('From'), true));
        
        
        $bridge->addFakeSubmit('sendtest', array('label' => $this->translate->_('Send (test)')));
        
        $bridge->addElement($this->mailElements->createPreviewHtmlElement('Preview HTML'));
        $bridge->addElement($this->mailElements->createPreviewTextElement('Preview Text'));
        $bridge->addHtml('available_fields', array('label' => $this->translate->_('Available fields')));
    }

    /**
     * Load extra data not from the model into the form
     */
    protected function loadFormData() {
        parent::loadFormData();

        if ($this->formData['gctt_subject'] || $this->formData['gctt_body']) {
            $content = '[b]';
            $content .= $this->translate->_('Subject:');
            $content .= '[/b] [i]';
            $content .= $this->mailer->applyFields($this->formData['gctt_subject']);
            $content .= "[/i]\n\n";
            $content .= $this->mailer->applyFields($this->formData['gctt_body']);
        } else {
            $content = ' ';
        }
        $this->formData['preview_html'] = MUtil_Markup::render($content, 'Bbcode', 'Html');
        $this->formData['preview_text'] = MUtil_Markup::render($content, 'Bbcode', 'Text');

        $organization = $this->mailer->getOrganization();
        $this->formData['to'] = $this->formData['from'] = null;
        if ($organization->getEmail()) {
            $this->formData['to'] = $this->formData['from'] = $organization->getEmail();
        } elseif ($this->project->email['site']) {
            $this->formData['to'] = $this->formData['from'] = $this->project->email['site'];
        }

        $this->formData['available_fields'] = $this->mailElements->displayMailFields($this->mailer->getMailFields());
    }

    /**
     * When the form is submitted with a non 'save' button
     */
    protected function onFakeSubmit()
    { 
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            if (!empty($data['preview'])) {
                $this->addMessage($this->translate->_('Preview updated'));
            } elseif (!empty($data['sendtest'])) {
                $this->addMessage($this->translate->_('Test mail sent'));
            }
        }
    }
}