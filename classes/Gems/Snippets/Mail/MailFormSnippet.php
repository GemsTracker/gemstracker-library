<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class MailFormSnippet extends \MUtil\Snippets\ModelSnippetAbstract
{
    protected $afterSendRouteUrl;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Form
     */
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
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Mail\MailElements
     */
    protected $mailElements;

    /**
     * The mailtarget
     * @var string
     */
    protected $mailTarget;

    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'index';

    /**
     * Is it only allowed to select a template, or can you edit the text after.
     * @var boolean
     */
    protected $templateOnly;

    /**
     *
     * @var \Zend_View
     */
    protected $view;


    public function afterRegistry()
    {
        $this->mailElements = $this->loader->getMailLoader()->getMailElements();
        $this->mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget, $this->identifier);

        parent::afterRegistry();
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addFormElements(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $bridge->addHtml('to', 'label', $this->_('To'));
        $bridge->addHtml('prefered_language', 'label', $this->_('Preferred Language'));

        $bridge->addElement($this->mailElements->createTemplateSelectElement('select_template', $this->_('Template'),$this->mailTarget, $this->templateOnly, true));

        if ($this->templateOnly) {
            $bridge->addHidden('subject');
        } else {
            $bridge->addText('subject', 'label', $this->_('Subject'), 'size', 100);
        }

        $mailBody = $bridge->addElement($this->mailElements->createBodyElement('mailBody', $this->_('Message'), $model->get('gctt_body', 'required'), $this->templateOnly));
        if ($mailBody instanceof \Gems\Form\Element\CKEditor) {
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

    /**
     *
     * Create a new form instance
     */
    protected function createForm()
    {
        if (!$this->form) {
            $this->form = new \Gems\Form(array('class' => 'form-horizontal', 'role' => 'form'));
            $this->mailElements->setForm($this->form);
        }
        return $this->form;
    }

    /**
     * Get the comm Model
     */
    public function createModel()
    {
        return $this->loader->getModels()->getCommTemplateModel();
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
        return $this->mailElements->getEmailOption($requestData, $name, $email, $extra, $disabledTitle, $menuFind);
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
        $options[$key] = $this->createMultiOption(array(\MUtil\Model::REQUEST_ID => $organization->getId()),
            $name, $email, $extra, $title,
            array('controller' => 'organization', 'action' => 'edit'));
        $this->fromOptions[$key] = $email;//$name . ' <' . $email . '>';

        // The user
        $key = 'user';
        $name  = $this->currentUser->getFullName();
        $extra = null;
        if ($email = $this->currentUser->getEmailAddress()) {
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
        $element = $this->form->createElement('radio', $elementName, array(
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
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {

        // If there is data, populate
        if (!empty($this->formData)) {
            $this->form->populate($this->formData);
        }

        if ($this->mailer->bounceCheck()) {
            $this->addMessage($this->_('On this test system all mail will be delivered to the from address.'));
        }

        $this->beforeDisplay();

        $htmlDiv = \MUtil\Html::div();

        $htmlDiv->h3($this->getTitle());

        $htmlDiv[] = $this->form;

        return $htmlDiv;
    }

    /**
     * Returns the model, always use this function
     *
     * @return \MUtil\Model\ModelAbstract
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
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute(): ?string
    {
        return $this->afterSendRouteUrl;
    }

    public function getTitle()
    {
        if ($this->formTitle) {
             return $this->formTitle;
         } else {
            $target = $this->_($this->mailTarget);
            if (is_array($target)) {
                $target = reset($target);
            }
            return sprintf($this->_('Email %s '), $target);
         }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
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

        $bridge = $this->model->getBridgeFor('form', $form);

        $this->addFormElements($bridge, $this->model);

        $this->form = $bridge->getForm();
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
            $requestData = $this->request->getPost();
            foreach($requestData as $key=>$value) {
                if (!is_array($value)) {
                    $this->formData[$key] = htmlspecialchars($value);
                } else {
                    $this->formData[$key] = array_map('htmlspecialchars', $value);
                }
            }
        }

        if (empty($this->formData['preview']) && !isset($this->formData['send'])) {
            if (isset($this->formData['select_template']) && !empty($this->formData['select_template'])) {

                if ($template = $this->mailer->getTemplate($this->formData['select_template'])) {
                    $this->formData['subject'] = $template['gctt_subject'];
                    $this->formData['mailBody'] = $template['gctt_body'];
                }
            }
        }

        $this->formData['available_fields'] = $this->mailElements->displayMailFields($this->mailer->getMailFields());

        if (!empty($this->formData['subject']) || !empty($this->formData['mailBody'])) {
            $content = '[b]';
            $content .= $this->_('Subject:');
            $content .= '[/b] [i]';
            $content .= $this->mailer->applyFields($this->formData['subject']);
            $content .= "[/i]\n\n";
            $content .= $this->mailer->applyFields($this->formData['mailBody']);
        } else {
            $content = ' ';
        }
        $htmlView = \MUtil\Html::create()->div();
        $textView = \MUtil\Html::create()->div();

        $htmlView->div(array('class' => 'mailpreview'))->raw(\MUtil\Markup::render($content, 'Bbcode', 'Html'));
        $textView->pre(array('class' => 'mailpreview'))->raw(\MUtil\Markup::render($content, 'Bbcode', 'Text'));

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
            } elseif (!empty($this->formData['send']) && $this->form->isValid($this->formData, false)) {
                $this->sendMail();
                $this->addMessage($this->_('Mail sent'));
                $this->setAfterSendRoute();
                return false;
            }
        }

        return true;
    }

    protected function sendMail()
    {
        $this->mailer->setFrom($this->fromOptions[$this->formData['from']]);
        $this->mailer->setSubject($this->formData['subject']);
        $this->mailer->setBody(htmlspecialchars_decode($this->formData['mailBody']), 'Bbcode');
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
                    $this->afterSendRouteUrl[\MUtil\Model::REQUEST_ID] = $this->formData[$key];
                }
            } else {
                $i = 1;
                foreach ($keys as $key) {
                    if (isset($this->formData[$key])) {
                        $this->afterSendRouteUrl[\MUtil\Model::REQUEST_ID . $i] = $this->formData[$key];
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
