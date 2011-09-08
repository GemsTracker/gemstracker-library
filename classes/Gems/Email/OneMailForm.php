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
 * @version    $Id: OneMailForm.php 463 2011-08-31 17:11:08Z mjong $
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
class Gems_Email_OneMailForm extends Gems_Email_EmailFormAbstract
{
    public function applyElements()
    {
        if ($this->mailer->bounceCheck()) {
            $this->addMessage($this->escort->_('On this test system all mail will be delivered to the from address.'));
        }

        $tokenData = $this->getTokenData();

        $this->addElement($this->createToElement());
        if (isset($tokenData['gto_round_description']) && $tokenData['gto_round_description']) {
            $this->addElement($this->createShowElement($tokenData['gtr_track_name'], $this->escort->_('Track')));
            $this->addElement($this->createShowElement($tokenData['gto_round_description'], $this->escort->_('Round')));
        }
        $this->addElement($this->createShowElement($tokenData['gsu_survey_name'], $this->escort->_('Survey')));
        $this->addElement($this->createShowElement($tokenData['gto_mail_sent_date'], $this->escort->_('Last contact'),
                array('formatFunction' => $this->escort->getUtil()->getTranslated()->formatDateNever)));
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

    protected function createShowElement($value, $label, $options = array())
    {
        static $count = 0;

        $name = 'exhibit_' . $count++;

        return new MUtil_Form_Element_Exhibitor($name, array('label' => $label, 'value' => $value) + $options);
    }

    protected function createToElement()
    {
        return new MUtil_Form_Element_Exhibitor('to', array('label' => $this->escort->_('To')));
    }

    protected function loadData(Zend_Controller_Request_Abstract $request)
    {
        $tokenData = $this->getTokenData();

        $data = $this->model->loadNew();
        $data['to']   = $tokenData['grs_email'];
        $data['from'] = $this->defaultFrom;

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

    protected function processValid()
    {
        if (isset($this->escort->project->email['block']) && $this->escort->project->email['block']) {
            $this->addMessage($this->escort->_('The sending of emails was blocked for this installation.'));
        } else {
            $tokenData = $this->getTokenData();

            $this->mailer->setSubject($this->getValue('gmt_subject'));
            $this->mailer->setBody($this->getValue('gmt_body'));

            if ($message = $this->mailer->processMail($tokenData)) {
                $this->addMessage($this->escort->_('Mail failed to send.'));
                $this->addMessage($message);

            } else {
                $this->addMessage(sprintf($this->escort->_('Sent email to %s.'), $tokenData['grs_email']));
                return true;
            }
        }
        return false;
    }
}
