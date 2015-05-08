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
 * @version    $Id$
 * @package    Gems
 * @subpackage Email
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * @package    Gems
 * @subpackage Email
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Email_MultiMailForm extends \Gems_Email_EmailFormAbstract
{
    protected $tokensData;

    public function applyElements()
    {
        if ($this->mailer->bounceCheck()) {
            $this->addMessage($this->escort->_('On this test system all mail will be delivered to the from address.'));
        }

        $this->addElement($this->createToElement());
        $this->addElement($this->createMethodElement());
        $this->addElement($this->createFromSelect());
        $this->addElement($this->createTemplateSelectElement($this->templateOnly));
        $this->addElement($this->createSubjectElement($this->templateOnly));
        $this->addElement($this->createBodyElement($this->templateOnly));
        if (! $this->templateOnly) {
            $this->addElement($this->createPreviewButton());
        }
        $this->addElement($this->createSendButton());
        $this->addElement($this->createPreviewHtmlElement());
        if (! $this->templateOnly) {
            $this->addElement($this->createAvailableFieldsElement());
        }

        return $this;
    }

    protected function createMethodElement()
    {
        $options = $this->escort->getUtil()->getTranslated()->getBulkMailProcessOptions();

        return new \Zend_Form_Element_Radio('multi_method', array(
            'label'        => $this->escort->_('Method'),
            'multiOptions' => $options,
            'required'     => true,
            ));
    }

    protected function createToElement()
    {
        $valid   = array();
        $invalid = array();
        $options = array();

        foreach ($this->getTokensData() as $tokenData) {
            if ($tokenData['can_email']) {
                $disabled  = false;
                $valid[]   = $tokenData['gto_id_token'];
            } else {
                $disabled  = true;
                $invalid[] = $tokenData['gto_id_token'];
            }
            $options[$tokenData['gto_id_token']] = $this->createToText($tokenData, $disabled);
        }

        $element = new \Zend_Form_Element_MultiCheckbox('to', array(
            'disable'      => $invalid,
            'escape'       => (! $this->getView()),
            'label'        => $this->escort->_('To'),
            'multiOptions' => $options,
            'required'     => true,
            ));

        $element->addValidator('InArray', false, array('haystack' => $valid));

        return $element;
    }

    public function getTokensData()
    {
        return $this->tokensData;
    }

    private function createToText(array $tokenData, $disabled)
    {
        $menuFind = false;

        if ($disabled) {
            if ($tokenData['gto_completion_time']) {
                $title = $this->escort->_('Survey has been taken.');
                $menuFind = array('controller' => array('track', 'survey'), 'action' => 'answer');
            } elseif (! $tokenData['grs_email']) {
                $title = $this->escort->_('Respondent does not have an e-mail address.');
                $menuFind = array('controller' => 'respondent', 'action' => 'edit');
            } elseif ($tokenData['ggp_respondent_members'] == 0) {
                $title = $this->escort->_('Survey cannot be taken by a respondent.');
            } else {
                $title = $this->escort->_('Survey cannot be taken at this moment.');
                $menuFind = array('controller' => array('track', 'survey'), 'action' => 'edit');
            }
        } else {
            $title = null;
        }

        return $this->_createMultiOption($tokenData,
            $this->mailer->getTokenName($tokenData),
            $tokenData['grs_email'],
            $tokenData['survey_short'],
            $title,
            $menuFind);
    }

    /**
     * Loads the tokens for which we are going to email
     *
     * Filters out tokens that can not be emailed or that have been sent today
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return array
     */
    protected function loadData(\Zend_Controller_Request_Abstract $request)
    {
        $data = $this->model->loadNew();

        foreach ($this->getTokensData() as $tokenData) {
            if ($tokenData['can_email'] && ($this->mailDate !== $tokenData['gto_mail_sent_date'])) {
                $data['to'][] = $tokenData['gto_id_token'];
                $emails[$tokenData['grs_email']] = $tokenData['gto_id_token'];
            }
        }
        $data['multi_method'] = 'O';
        $data['from'] = array($this->defaultFrom);

        return $data;
    }

    protected function processPost(array &$data)
    {
        if (isset($data['select_subject']) && $data['select_subject']) {
            $newvalues = $this->model->loadFirst(array('gmt_id_message' => $data['select_subject']));

            $newdata = false;

            foreach ($data as $key => $value) {
                if (isset($newvalues[$key])) {
                    if ($data[$key] != $newvalues[$key]) {
                        $data[$key] = $newvalues[$key];
                        $newdata = true;
                    }
                }
            }

            if ($this->getTemplateOnly()) {
                return $newdata;
            } else {
                $data['select_subject'] = null;
                return true;
            }
        }

        return false;
    }

    public function setTokensData(array $tokensData)
    {
        $this->tokensData = $tokensData;

        $this->setTokenData(reset($tokensData));

        return $this;
    }
}
