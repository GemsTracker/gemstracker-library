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
 * @subpackage Email
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Email
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class Gems_Email_EmailFormAbstract extends Gems_Form
{
    protected $addInputError = true;

    protected $defaultFrom;

    /**
     * @var GemsEscort $escort
     */
    protected $escort;

    protected $messages = array();

    protected $model;

    protected $preview;

    protected $templateOnly = true;

    /**
     * @var Gems_Email_TemplateMailer
     */
    protected $mailer;

    protected $_tokenData;

    /**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param string $name
     * @param mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof GemsEscort) {
            $this->setEscort($options);
            $options = null;
        }

        parent::__construct($options);

        if (! $this->escort) {
            $this->setEscort();
        }

        $this->model = $this->createModel();
        $this->mailer = new Gems_Email_TemplateMailer($this->escort);
    }

    protected function _createMultiOption(array $requestData, $name, $email, $extra = null, $disabledTitle = false, $menuFind = false)
    {
        if (! $email) {
            $email = $this->escort->_('no email adress');
        }

        $text = "\"$name\" <$email>";
        if (null !== $extra) {
            $text .= ": $extra";
        }

        if ($view = $this->getView()) {
            if ($disabledTitle) {
                $el = MUtil_Html::create()->span($text, array('class' => 'disabled'));

                if ($menuFind && is_array($menuFind)) {
                    $menuFind['allowed'] = true;
                    $menuItem = $this->escort->menu->find($menuFind);
                    if ($menuItem) {
                        $href = $menuItem->toHRefAttribute($requestData);

                        if ($href) {
                            $el = MUtil_Html::create()->a($href, $el);
                            $el->target = $menuItem->get('target', '_BLANK');
                        }
                    }
                }
                $el->title = $disabledTitle;
                $text = $el->render($view);
            } else {
                $text = $view->escape($text);
            }
        }

        return $text;
    }

    abstract protected function applyElements();

    protected function addMessage($message)
    {
        $this->messages[] = $message;
        return $this;
    }

    protected function createAvailableFieldsElement()
    {
        $options['label']    = $this->escort->_('Available fields');
        $options['nohidden'] = true;

        return new MUtil_Form_Element_Exhibitor('available_fields', $options);
    }

    protected function createBBCodeLink()
    {
        $options['label']    = $this->escort->_('BBCode');
        $options['nohidden'] = true;

        return new MUtil_Form_Element_Exhibitor('bbcode_link', $options);
    }

    protected function createBodyElement($hidden = false)
    {
        $name = 'gmt_body';

        if ($hidden) {
            return new Zend_Form_Element_Hidden($name);
        }

        $options['required'] = $this->model->get($name, 'required');
        $options['label']    = $this->escort->_('Message');

        return new Gems_Form_Element_CKEditor($name, $options);
    }

    protected function createEmailElement($name, $label, $required = false, $multi = false)
    {
        $options['label']     = $label;
        $options['maxlength'] = 250;
        $options['required']  = $required;
        $options['size']      = 50;

        $element = new Zend_Form_Element_Text($name, $options);

        if ($multi) {
            $element->addValidator('SimpleEmails');
        } else {
            $element->addValidator('SimpleEmail');
        }

        return $element;
    }

    protected function createFromElement()
    {
        return $this->createEmailElement('from', $this->escort->_('From'), true);
    }

    protected function createFromSelect()
    {
        $valid   = array();
        $invalid = array();

        $tokenData = $this->getTokenData();

        // The organization
        $key  = 'O';
        if ($tokenData['gor_contact_name']) {
            $name  = $tokenData['gor_contact_name'];
            $extra = $tokenData['gor_name'];
        } else {
            $name  = $tokenData['gor_name'];
            $extra = null;
        }
        if ($email = $tokenData['gor_contact_email']) {
            $title     = false;
            $valid[]   = $key;
        } else {
            $title     = $this->escort->_('Organization does not have an e-mail address.');
            $invalid[] = $key;
        }
        $options[$key] = $this->_createMultiOption(array(MUtil_Model::REQUEST_ID => $tokenData['gor_id_organization']),
            $name, $email, $extra, $title,
            array('controller' => 'organization', 'action' => 'edit'));

        // The user
        $key = 'U';
        $name  = $this->escort->session->user_name;
        $extra = null;
        if ($email = $this->escort->session->user_email) {
            $title     = false;
            $valid[]   = $key;
        } else {
            $title     = $this->escort->_('You do not have an e-mail address.');
            $invalid[] = $key;
        }
        $options[$key] = $this->_createMultiOption($tokenData,
            $name, $email, $extra, $title,
            array('controller' => 'option', 'action' => 'edit'));

        // As "the" application
        if ($this->escort->hasPrivilege('pr.plan.mail-as-application') &&
            isset($this->escort->project->email['site'])) {

            if ($email = $this->escort->project->email['site']) {
                $options['S'] = $this->_createMultiOption(array(), $this->escort->project->name, $email);
                $valid[]     = 'S';
            }
        }

        $element = new Zend_Form_Element_Radio('from', array(
            'disable'      => $invalid,
            'escape'       => (! $this->getView()),
            'label'        => $this->escort->_('From'),
            'multiOptions' => $options,
            'required'     => true,
            ));


        $element->addValidator('InArray', false, array('haystack' => $valid));

        $this->defaultFrom = reset($valid);

        return $element;
    }

    protected function createMessageIdElement()
    {
        return new Zend_Form_Element_Hidden('gmt_id_message');
    }

    public function createModel()
    {
        $model = new MUtil_Model_TableModel('gems__mail_templates');

        Gems_Model::setChangeFieldsByPrefix($model, 'gmt', $this->escort->session->user_id);

        return $model;
    }

    protected function createPreviewButton()
    {
        $element = new Zend_Form_Element_Submit('preview_button', $this->escort->_('Preview'));
        $element->setAttrib('class', 'button');

        $this->preview = $element;

        return $element;
    }

    protected function createPreviewHtmlElement($noText = true)
    {
        $options['itemDisplay'] = array(__CLASS__, 'displayMailHtml');
        $options['label']       = $noText ? $this->escort->_('Preview') : $this->escort->_('Preview HTML');
        $options['nohidden']    = true;

        return new MUtil_Form_Element_Exhibitor('preview_html', $options);
    }

    protected function createPreviewTextElement()
    {
        $options['itemDisplay'] = array(__CLASS__, 'displayMailText');
        $options['label']       = $this->escort->_('Preview text');
        $options['nohidden']    = true;

        return new MUtil_Form_Element_Exhibitor('preview_text', $options);
    }

    protected function createTemplateSelectElement($list)
    {
        $options['label']        = $this->escort->_('Template');
        if ($this->escort instanceof Gems_Project_Organization_MultiOrganizationInterface) {
            $organizationId = intval($this->escort->getCurrentOrganization());
            $sql = "SELECT gmt_id_message, gmt_subject FROM gems__mail_templates WHERE LOCATE('|$organizationId|', gmt_organizations) > 0 ORDER BY gmt_subject";
        } else {
            $sql = 'SELECT gmt_id_message, gmt_subject FROM gems__mail_templates ORDER BY gmt_subject';
        }
        $options['multiOptions'] = $this->escort->db->fetchPairs($sql);
        if (! $list) {
            $options['multiOptions'] = array('' => '') + $options['multiOptions'];
        }
        $options['onchange']     = 'this.form.submit()';

        if ($list) {
            $options['required'] = true;
            $options['size'] = min(count($options['multiOptions']) + 1, 7);
        }

        return new Zend_Form_Element_Select('select_subject', $options);
    }

    protected function createSendButton($label = null)
    {
        if (null === $label) {
            $label = $this->escort->_('Send');
        }
        $element = new Zend_Form_Element_Submit('send_button', $label);
        $element->setAttrib('class', 'button');

        return $element;
    }

    protected function createSubjectElement($hidden = false)
    {
        $name = 'gmt_subject';

        if ($hidden) {
            return new Zend_Form_Element_Hidden($name);
        }

        $options = $this->model->get($name, 'maxlength', 'required');
        $options['label'] = $this->escort->_('Subject');
        $options['size']  = min(array($options['maxlength'], 80));

        return new Zend_Form_Element_Text($name, $options);
    }

    public static function displayMailHtml($text)
    {
        $div = MUtil_Html::create()->div(array('class' => 'mailpreview'));
        $div->raw($text);

        return $div;
    }

    public static function displayMailText($text)
    {
        return MUtil_Html::create()->pre($text, array('class' => 'mailpreview'));
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getTemplateOnly()
    {
        return $this->templateOnly;
    }

    public function getTokenData()
    {
        if (! $this->_tokenData) {
            $model = $this->escort->getLoader()->getTracker()->getTokenModel();

            $this->_tokenData = $model->loadFirst(array('grs_email IS NOT NULL', 'gto_valid_from IS NOT NULL'), array('gto_valid_from' => SORT_DESC));
        }
        return $this->_tokenData;
    }

    public function setTokenData($tokenData)
    {
        $this->_tokenData = $tokenData;

        $this->mailer->setTokenData($tokenData);
    }

    public function hasMessages()
    {
        return (boolean) $this->messages;
    }

    abstract protected function loadData(Zend_Controller_Request_Abstract $request);

    protected function processPost(array &$data)
    {
        return false;
    }

    public function processRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->applyElements();

        if ($request->isPost()) {
            $data = $request->getPost();

            if ($this->processPost($data)) {
                $valid   = false;
                $message = false;
            } else {
                $valid   = $this->isValid($data);
                $message = true;
            }

            if ($this->preview && $this->preview->isChecked()) {
                $this->addMessage('Preview clicked');
            } elseif ($valid) {
                if ($this->processValid()) {
                    return true;
                }
            } elseif ($message && $this->addInputError) {
                $this->addMessage($this->escort->_('Input error! No changes made!'));
            }

        } else {
            $data = $this->loadData($request);
        }

        /* if ($bbtext) {
            $bbmark = MUtil_Markup::factory('Bbcode', 'Text');
            $data['bbcode_text'] = '<pre>' . $bbmark->render(str_replace(array_keys($mailFields), $mailFields, $bbtext)) . '</pre>';
        } */

        if ($this->getElement('preview_html') || $this->getElement('preview_text')) {
            $this->getTokenData();

            if ($data['gmt_subject'] || $data['gmt_body']) {
                $content = '[b]';
                $content .= $this->escort->_('Subject:');
                $content .= '[/b] [i]';
                $content .= $this->mailer->applyFields($data['gmt_subject']);
                $content .= "[/i]\n\n";
                $content .= $this->mailer->applyFields($data['gmt_body']);
            } else {
                $content = false;
            }

            if ($this->getElement('preview_html')) {
                if ($content) {
                    $data['preview_html'] = MUtil_Markup::render($content, 'Bbcode', 'Html');
                } else {
                    $this->removeElement('preview_html');
                }
            }

            if ($this->getElement('preview_text')) {
                if ($content) {
                    $data['preview_text'] = MUtil_Markup::render($content, 'Bbcode', 'Text');
                } else {
                    $this->removeElement('preview_text');
                }
            }
        }

        $mailRepeater = new MUtil_Lazy_RepeatableByKeyValue($this->mailer->getTokenMailFields());
        $mailHtml     = new MUtil_Html_TableElement($mailRepeater);
        $mailHtml->addColumn($mailRepeater->key, $this->escort->_('Field'));
        $mailHtml->addColumn($mailRepeater->value, $this->escort->_('Value'));
        $data['available_fields'] = $mailHtml;

        $data['bbcode_link'] = MUtil_Html::create()->a('http://en.wikipedia.org/wiki/BBCode', $this->escort->_('BBCode info page'), array('target' => 'BLANK'));
        $this->populate($data);

        return false;
    }

    protected function processValid()
    {
        $this->mailer->setFrom($this->getValue('from'));
        $this->mailer->setTokens($this->getValue('to'));
        $this->mailer->setMethod($this->getValue('multi_method'));
        $this->mailer->setSubject($this->getValue('gmt_subject'));
        $this->mailer->setBody($this->getValue('gmt_body'));
        $this->mailer->setTemplateId($this->getValue('select_subject'));

        $result = $this->mailer->process($this->getTokensData());

        $this->messages = array_merge($this->messages, $this->mailer->getMessages());

        return $result;
    }

    public function setEscort(GemsEscort $escort = null)
    {
        if (null === $escort) {
            $escort = GemsEscort::getInstance();
        }

        $this->escort = $escort;

        return $this;
    }

    public function setTemplateOnly($templateOnly = true)
    {
        $this->templateOnly = $templateOnly;
        return $this;
    }
}

