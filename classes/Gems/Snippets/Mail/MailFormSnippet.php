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
 * @version    $Id: MailFormSnippet.php $
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
class Gems_Snippets_Mail_MailFormSnippet extends MUtil_Snippets_ModelSnippetAbstract 
{
    protected $afterSendRouteUrl;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    protected $form;

    /**
     * The form class
     * @var string
     */
    protected $formClass = 'formTable';
    
    protected $formData = array();

    public $formTitle;

    protected $fromOptions;

    protected $identifier;

    /**
     * Class of every label
     * @var string
     */
    protected $labelClass = 'label';

    /**
     * 
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * 
     * @var Gems_Mail_MailElements
     */
    protected $mailElements;

    /**
     * The mailtarget 
     * @var string
     */
    protected $mailTarget;

    /**
     *
     * @var Gems_Menu
     */
    protected $menu;

    protected $model;

    /**
     * 
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'index';

    /**
     * 
     * @var Zend_Session
     */
    protected $session;

    /**
     * Is it only allowed to select a template, or can you edit the text after.
     * @var boolean
     */
    protected $templateOnly;

    /**
     *
     * @var Zend_View
     */
    protected $view;


    public function afterRegistry()
    {
        $this->mailElements = $this->loader->getMailLoader()->getMailElements();
        $this->mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget, $this->identifier);

        parent::afterRegistry();
    }

    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        
        $bridge->addHtml('to', 'label', $this->_('To'));
        $bridge->addHtml('prefered_language', 'label', $this->_('Prefered Language'));

        $bridge->addElement($this->mailElements->createTemplateSelectElement('select_template', $this->_('Template'),$this->mailTarget, $this->templateOnly, true));

        if ($this->templateOnly) {
            $bridge->addHidden('subject');
        } else {
            $bridge->addText('subject', 'label', $this->_('Subject'), 'size', 50);
        }

        $mailBody = $bridge->addElement($this->mailElements->createBodyElement('body', $this->_('Message'), $model->get('gctt_body', 'required'), $this->templateOnly));
        if ($mailBody instanceof Gems_Form_Element_CKEditor) {
            $mailBody->config['availablefields'] = $this->mailer->getMailFields();
            $mailBody->config['availablefieldsLabel'] = $this->_('Fields');

            $mailBody->config['extraPlugins'] .= ',availablefields';
            $mailBody->config['toolbar'][] = array('availablefields');
        }
        if (!$this->templateOnly) { 
            $bridge->addFakeSubmit('preview', array('label' => $this->_('Preview')));
        }

        $bridge->addElement($this->createFromSelect('from', $this->_('From')));

        $bridge->addElement($this->mailElements->createSubmitButton('send', $this->_('Send')));
        
        $bridge->addElement($this->mailElements->createPreviewHtmlElement('Preview HTML'));
        $bridge->addElement($this->mailElements->createPreviewTextElement('Preview Text'));
        if (!$this->templateOnly) {
            $bridge->addHtml('available_fields', array('label' => $this->_('Available fields')));
        }        
    }

    protected function beforeDisplay()
    {
        $table = new MUtil_Html_TableElement(array('class' => $this->formClass));
        $table->setAsFormLayout($this->form, true, true);

        // There is only one row with formLayout, so all in output fields get class.
        $table['tbody'][0][0]->appendAttrib('class', $this->labelClass);
    }

    /**
     *
     * Create a new form instance
     */
    protected function createForm()
    {
        if (!$this->form) {
            $this->form = new Gems_Form();
            return $this->form;
        }
    }

    /**
     * Get the comm Model
     */
    public function createModel()
    {
        $this->model = $loader->getModels()->getCommTemplateModel();
    }

    /**
     * Create the option values with links for the select
     * @param  array   $requestData   Needed for the links
     * @param  string  $name          Sender name
     * @param  string  $email         Sender Email
     * @param  string  $extra         Extra info after the mail address
     * @param  boolean $disabledTitle Is the link disabled
     * @param  boolean $menuFind      Find the url in the menu?
     * @return string   Options
     */
    protected function createMultiOption(array $requestData, $name, $email, $extra = null, $disabledTitle = false, $menuFind = false)
    {
        if (! $email) {
            $email = $this->_('no email adress');
        }

        $text = "\"$name\" <$email>";
        if (null !== $extra) {
            $text .= ": $extra";
        }

        if ($this->view) {
            if ($disabledTitle) {
                $el = MUtil_Html::create()->span($text, array('class' => 'disabled'));

                if ($menuFind && is_array($menuFind)) {
                    $menuFind['allowed'] = true;
                    $menuItem = $this->menu->find($menuFind);
                    if ($menuItem) {
                        $href = $menuItem->toHRefAttribute($requestData);

                        if ($href) {
                            $el = MUtil_Html::create()->a($href, $el);
                            $el->target = $menuItem->get('target', '_BLANK');
                        }
                    }
                }
                $el->title = $disabledTitle;
                $text = $el->render($this->view);
            } else {
                $text = $this->view->escape($text);
            }
        }

        return $text;
    }

    /**
     * Create the options for the Select
     */
    protected function createFromSelect($elementName='from', $label='')
    {
        $valid   = array();
        $invalid = array();

        $organization  = $this->mailer->getOrganization();
        // The organization
        $key  = 'organization';
        if ($name = $organization->getContactName()) {
            $extra = $organization->getName();
        } else {
            $name  = $organization->getName();
            $extra = null;
        }
        if ($email = $organization->getEmail()) {
            $title     = false;
            $valid[]   = $key;
        } else {
            $title     = $this->_('Organization does not have an e-mail address.');
            $invalid[] = $key;
        }
        $options[$key] = $this->createMultiOption(array(MUtil_Model::REQUEST_ID => $organization->getId()),
            $name, $email, $extra, $title,
            array('controller' => 'organization', 'action' => 'edit'));
        $this->fromOptions[$key] = $email;//$name . ' <' . $email . '>';

        // The user
        $key = 'user';
        $name  = $this->session->user_name;
        $extra = null;
        if ($email = $this->session->user_email) {
            $title     = false;
            $valid[]   = $key;
        } else {
            $title     = $this->_('You do not have an e-mail address.');
            $invalid[] = $key;
        }
        $options[$key] = $this->createMultiOption(array(),
            $name, $email, $extra, $title,
            array('controller' => 'option', 'action' => 'edit'));
        $this->fromOptions[$key] = $email;//$name . ' <' . $email . '>';

        // As "the" application
        $key = 'application';
        $user = $this->loader->getCurrentUser();
        if ($user->hasPrivilege('pr.plan.mail-as-application') &&
            isset($this->project->email['site'])) {

            if ($email = $this->project->email['site']) {
                $options['application'] = $this->createMultiOption(array(), $this->project->name, $email);
                $valid[]     = 'application';
                $this->fromOptions[$key] = $email;//$this->project->name . ' <' . $email . '>';
            }
        }

        $firstSelectable = reset($valid);
        $element = new Zend_Form_Element_Radio($elementName, array(
            'disable'      => $invalid,
            'escape'       => (!$this->view),
            'label'        => $label,
            'multiOptions' => $options,
            'required'     => true,
            'value'        => $firstSelectable,
            ));

        $element->addValidator('InArray', false, array('haystack' => $valid));
        return $element;
    }   

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {

        // If there is data, populate
        if (!empty($this->formData)) {
            $this->form->populate($this->formData);
        }

        if ($this->mailer->bounceCheck()) {
            $this->addMessage($this->_('On this test system all mail will be delivered to the from address.'));
        }

        $this->beforeDisplay();

        $htmlDiv = MUtil_Html::div();

        $htmlDiv->h3($this->getTitle());

        $htmlDiv[] = $this->form;

        return $htmlDiv;
    }

    /**
     * Returns the model, always use this function
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function getModel()
    {
        if (! $this->model) {
            $this->model = $this->createModel();

            $this->prepareModel($this->model);
        }

        return $this->model;
    }

    /**
     * When hasHtmlOutput() is false a snippet user should check
     * for a redirectRoute.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        return $this->afterSendRouteUrl;
    }

    public function getTitle()
    {
        if ($this->formTitle) {
             return $this->formTitle;
         } else {
            $title = $this->_('Email to: '). $this->_($this->mailTarget);
            return $title;
         }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (parent::hasHtmlOutput()) {
            if ($this->mailer->getDataLoaded()) {
                return $this->processForm();    
            } else {
                $this->addMessage($this->mailer->getMessages());
                return true;
            }
        }
    }

    /**
     * Makes sure there is a form.
     */
    protected function loadForm()
    {
        $form = $this->createForm();

        $bridge   = new MUtil_Model_FormBridge($this->model, $form);

        $this->addFormElements($bridge, $this->model);

        $this->form =  $bridge->getForm();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        $presetTargetData = $this->mailer->getPresetTargetData();
        if ($this->request->isPost()) {
            $this->formData = $this->request->getPost() + $this->formData;
        }

        if (empty($this->formData['preview']) && !isset($this->formData['send'])) {
            if (isset($this->formData['select_template']) && !empty($this->formData['select_template'])) {
                
                if ($template = $this->mailer->getTemplate($this->formData['select_template'])) {
                    $this->formData['subject'] = $template['gctt_subject'];
                    $this->formData['body'] = $template['gctt_body'];
                }
            }
        }

        $this->formData['available_fields'] = $this->mailElements->displayMailFields($this->mailer->getMailFields());

        if (!empty($this->formData['subject']) || !empty($this->formData['body'])) {
            $content = '[b]';
            $content .= $this->_('Subject:');
            $content .= '[/b] [i]';
            $content .= $this->mailer->applyFields($this->formData['subject']);
            $content .= "[/i]\n\n";
            $content .= $this->mailer->applyFields($this->formData['body']);
        } else {
            $content = ' ';
        }
        $htmlView = MUtil_Html::create()->div();
        $textView = MUtil_Html::create()->div();

        $htmlView->div(array('class' => 'mailpreview'))->raw(MUtil_Markup::render($content, 'Bbcode', 'Html'));
        $textView->pre(array('class' => 'mailpreview'))->raw(MUtil_Markup::render($content, 'Bbcode', 'Text'));

        $this->formData['preview_html'] = $htmlView;
        $this->formData['preview_text'] = $textView;
        
        

        $this->formData = array_merge($this->formData, $presetTargetData);

    }

    protected function processForm()
    {
        $this->loadForm();

        $this->loadFormData();
        
        if ($this->request->isPost()) {
            if (!empty($this->formData['preview'])) {
                $this->addMessage($this->_('Preview updated'));
            } elseif (!empty($this->formData['send'])) {
                $this->sendMail();
                $this->addMessage($this->_('Mail sent'));
                $this->setAfterSendRoute();
                return false;
            }
        }

        return true;
    }

    protected function sendMail() {
        $this->mailer->setFrom($this->fromOptions[$this->formData['from']]);
        $this->mailer->setSubject($this->formData['subject']);
        $this->mailer->setBody($this->formData['body']);
        $this->mailer->setTemplateId($this->formData['select_template']);
        $this->mailer->send();
    }

    /** 
     * Set the route destination after the mail is sent
     */
    protected function setAfterSendRoute()
    {
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSendRouteUrl = array('action' => $this->routeAction);

            $keys  = $this->model->getKeys();
            if (count($keys) == 1) {
                $key = reset($keys);
                if (isset($this->formData[$key])) {
                    $this->afterSendRouteUrl[MUtil_Model::REQUEST_ID] = $this->formData[$key];
                }
            } else {
                $i = 1;
                foreach ($keys as $key) {
                    if (isset($this->formData[$key])) {
                        $this->afterSendRouteUrl[MUtil_Model::REQUEST_ID . $i] = $this->formData[$key];
                    }
                    $i++;
                }
            }

            $this->afterSendRouteUrl['controller'] = $this->request->getControllerName();

            $find['action'] = $this->afterSendRouteUrl['action'];
            $find['controller'] = $this->afterSendRouteUrl['controller'];

            if (null == $this->menu->find($find)) {
                $this->afterSendRouteUrl['action'] = 'index';
                $this->resetRoute = true;
            }
        }
    }
}