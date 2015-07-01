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
 * @version    $Id$
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

    /**
     *
     * @var Gems_Mail_MailerAbstract
     */
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

    protected $util;

    protected $view;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * @param MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(MUtil_Model_Bridge_FormBridgeInterface $bridge, MUtil_Model_ModelAbstract $model)
    {
        $this->mailElements->setForm($bridge->getForm());
        $this->initItems();
        $this->addItems($bridge, 'gct_name');
        $this->addItems($bridge, 'gct_id_template', 'gct_target');

        $bridge->getForm()->getElement('gct_target')->setAttrib('onchange', 'this.form.submit()');

        $defaultTab = $this->project->getLocaleDefault();
        $this->mailElements->addFormTabs($bridge, 'gctt', 'active', $defaultTab, 'tabcolumn', 'gctt_lang', 'selectedTabElement', 'send_language');

        $config = array(
        'extraPlugins' => 'bbcode,availablefields',
        'toolbar' => array(
            array('Source','-','Undo','Redo'),
            array('Find','Replace','-','SelectAll','RemoveFormat'),
            array('Link', 'Unlink', 'Image', 'SpecialChar'),
            '/',
            array('Bold', 'Italic','Underline'),
            array('NumberedList','BulletedList','-','Blockquote'),
            array('Maximize'),
            array('availablefields')
            )
        );

        
        $mailfields = $this->mailer->getMailFields();
        foreach($mailfields as $field => $value) {
            $mailfields[$field] = utf8_encode($value);
        }
        $config['availablefields'] = $mailfields;


        $config['availablefieldsLabel'] = $this->_('Fields');
        $this->view->inlineScript()->prependScript("
            CKEditorConfig = ".Zend_Json::encode($config).";
            ");

        $bridge->addFakeSubmit('preview', array('label' => $this->_('Preview')));

        $bridge->addElement($this->mailElements->createEmailElement('to', $this->_('To (test)'), false));
        $bridge->addElement($this->mailElements->createEmailElement('from', $this->_('From'), false));

        //$bridge->addRadio('send_language', array('label' => $this->_('Test language'), 'multiOptions' => ))
        $bridge->addHidden('send_language');
        $bridge->addFakeSubmit('sendtest', array('label' => $this->_('Send (test)')));

        $bridge->addElement($this->getSaveButton($bridge->getForm()));

        $bridge->addElement($this->mailElements->createPreviewHtmlElement('Preview HTML'));
        $bridge->addElement($this->mailElements->createPreviewTextElement('Preview Text'));
        $bridge->addHtml('available_fields', array('label' => $this->_('Available fields')));
        $this->addItems($bridge, 'gct_code');
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry() {
        $this->mailElements = $this->loader->getMailLoader()->getMailElements();
        $this->mailTargets  = $this->loader->getMailLoader()->getMailTargets();

        parent::afterRegistry();
    }


    /**
     * Style the template previews
     * @param  array $templateArray template data
     * @return array html and text views
     */
    protected function getPreview($templateArray)
    {
        $multi = false;
        if (count($templateArray) > 1) {
            $multi = true;
            $allLanguages = $this->util->getLocalized()->getLanguages();
        }

        $htmlView = MUtil_Html::create()->div();
        $textView = MUtil_Html::create()->div();
        foreach($templateArray as $template) {


            $content = '';
            if ($template['gctt_subject'] || $template['gctt_body']) {
                if ($multi) {
                    $htmlView->h3()->append($allLanguages[$template['gctt_lang']]);
                    $textView->h3()->append($allLanguages[$template['gctt_lang']]);
                }


                $content .= '[b]';
                $content .= $this->_('Subject:');
                $content .= '[/b] [i]';
                $content .= $this->mailer->applyFields($template['gctt_subject']);
                $content .= "[/i]\n\n";
                $content .= $this->mailer->applyFields($template['gctt_body']);



                $htmlView->div(array('class' => 'mailpreview'))->raw(MUtil_Markup::render($content, 'Bbcode', 'Html'));
                $textView->pre(array('class' => 'mailpreview'))->raw(MUtil_Markup::render($content, 'Bbcode', 'Text'));
            }

        }

        return array('html' => $htmlView, 'text' => $textView);
    }

    protected function getSaveButton($form)
    {
        $options = array('label' => $this->_('Save'),
                  'attribs' => array('class' => $this->buttonClass)
                );
        return $form->createElement('submit', 'save_button', $options);
    }

    /**
     * Load extra data not from the model into the form
     */
    protected function loadFormData()
    {
        parent::loadFormData();
        $this->loadMailer();


        if (isset($this->formData['gctt'])) {
            $multi = false;
            if (count($this->formData['gctt']) > 1) {
                $multi = true;
                $allLanguages = $this->util->getLocalized()->getLanguages();
            }

            $preview = $this->getPreview($this->formData['gctt']);

            $this->formData['preview_html'] = $preview['html'];
            $this->formData['preview_text'] = $preview['text'];
        }

        $organization = $this->mailer->getOrganization();
        $this->formData['to'] = $this->formData['from'] = null;
        if ($organization->getEmail()) {
            $this->formData['to'] = $this->formData['from'] = $organization->getEmail();
        } elseif ($this->project->getSiteEmail ()) {
            $this->formData['to'] = $this->formData['from'] = $this->project->getSiteEmail();
        }

        $this->formData['available_fields'] = $this->mailElements->displayMailFields($this->mailer->getMailFields());
    }

    /**
     * Loads the correct mailer
     */
    protected function loadMailer()
    {
        $this->mailTarget = false;

        if (isset($this->formData['gct_target']) && isset($this->mailTargets[$this->formData['gct_target']])) {
            $this->mailTarget = $this->formData['gct_target'];
        } else {
            reset($this->mailTargets);
            $this->mailTarget = key($this->mailTargets);
        }
        $this->mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget);
    }

    /**
     * When the form is submitted with a non 'save' button
     */
    protected function onFakeSubmit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            if (!empty($data['preview'])) {
                $this->addMessage($this->_('Preview updated'));
            } elseif (!empty($data['sendtest'])) {
                $this->mailer->setTo($data['to']);

                // Make sure at least one template is set (for single language projects)
                $template = reset($data['gctt']);
                foreach($data['gctt'] as $templateLanguage) {
                    // Find the current template (for multi language projects)
                    if ($templateLanguage['gctt_lang'] == $data['send_language']) {
                        $template = $templateLanguage;
                    }
                }

                $this->mailer->setFrom($data['from']);
                $this->mailer->setSubject($template['gctt_subject']);
                $this->mailer->setBody($template['gctt_body'], 'Bbcode');
                $this->mailer->setTemplateId($data['gct_id_template']);
                $this->mailer->send();

                $this->addMessage(sprintf($this->_('Test mail sent to %s'), $data['to']));
            }
        }
    }
}