<?php

/**
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
class Gems_Snippets_Mail_MailModelFormSnippet extends \Gems_Snippets_ModelFormSnippetGeneric
{
    /**
     *
     * @var \Gems_Mail_MailElements
     */
    protected $mailElements;

    /**
     *
     * @var \Gems_Mail_MailerAbstract
     */
    protected $mailer;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->mailElements->setForm($bridge->getForm());
        $this->initItems();
        $this->addItems($bridge, 'gct_name');
        $this->addItems($bridge, 'gct_id_template', 'gct_target');
        $this->addItems($bridge, 'gct_code');

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
            CKEditorConfig = ".\Zend_Json::encode($config).";
            ");

        $bridge->addFakeSubmit('preview', array('label' => $this->_('Preview')));

        $bridge->addElement($this->mailElements->createEmailElement('to', $this->_('To (test)'), false));
        $bridge->addElement($this->mailElements->createEmailElement('from', $this->_('From'), false));

        //$bridge->addRadio('send_language', array('label' => $this->_('Test language'), 'multiOptions' => ))
        $bridge->addHidden('send_language');
        $bridge->addFakeSubmit('sendtest', array('label' => $this->_('Send (test)')));

        $bridge->addElement($this->getSaveButton($bridge->getForm()));

        $bridge->addElement($this->mailElements->createPreviewHtmlElement($this->_('Preview HTML')));
        $bridge->addElement($this->mailElements->createPreviewTextElement($this->_('Preview Text')));
        $bridge->addHtml('available_fields', array('label' => $this->_('Available fields')));
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
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

        $htmlView = \MUtil_Html::create()->div();
        $textView = \MUtil_Html::create()->div();

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

                $htmlView->div(array('class' => 'mailpreview'))->raw(\MUtil_Markup::render($content, 'Bbcode', 'Html'));
                $textView->pre(array('class' => 'mailpreview'))->raw(\MUtil_Markup::render($content, 'Bbcode', 'Text'));
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
        if (!isset($this->formData['to'])) {
            $organization = $this->mailer->getOrganization();
            $this->formData['to'] = $this->formData['from'] = null;
            if ($organization->getEmail()) {
                $this->formData['to'] = $this->formData['from'] = $organization->getEmail();
            } elseif ($this->project->getSiteEmail ()) {
                $this->formData['to'] = $this->formData['from'] = $this->project->getSiteEmail();
            }
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

            if (! empty($this->formData['preview'])) {
                $this->addMessage($this->_('Preview updated'));
                return;
            }

            if (! empty($this->formData['sendtest'])) {
                $this->mailer->setTo($this->formData['to']);

                // Make sure at least one template is set (for single language projects)
                $template   = reset($this->formData['gctt']);
                $languageId = key($this->formData['gctt']);
                if ($this->formData['send_language']) {
                    foreach($this->formData['gctt'] as $languageId => $templateLanguage) {
                        // Find the current template (for multi language projects)
                        if ($templateLanguage['gctt_lang'] == $this->formData['send_language']) {
                            $template = $templateLanguage;
                        }
                    }
                }

                // \MUtil_Echo::track($this->formData);
                $errors = false;
                if (! $template['gctt_subject']) {
                    $this->addMessage(sprintf(
                            $this->_('Subject required for %s part.'),
                            strtoupper($template['gctt_lang'])
                            ));
                    $errors = true;
                }
                if (! $template['gctt_body']) {
                    $this->addMessage(sprintf(
                            $this->_('Body required for %s part.'),
                            strtoupper($template['gctt_lang']))
                            );
                    $errors = true;
                }

                if ($errors) {
                   return;
                }

                $this->mailer->setFrom($this->formData['from']);
                $this->mailer->setSubject($template['gctt_subject']);
                $this->mailer->setBody($template['gctt_body'], 'Bbcode');
                $this->mailer->setTemplateId($this->formData['gct_id_template']);
                $this->mailer->send();

                $this->addMessage(sprintf($this->_('Test mail sent to %s'), $this->formData['to']));
            }
        }
    }
}