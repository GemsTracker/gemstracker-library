<?php

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