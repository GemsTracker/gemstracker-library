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
 * @version    $id TokenMailFormSnippet.php
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
class Gems_Snippets_Mail_TokenBulkMailFormSnippet extends Gems_Snippets_Mail_MailFormSnippet
{
    protected $identifier;

    protected $loader;

    protected $multipleTokenData;

    protected $otherTokenData;

    public function afterRegistry()
    {

        //MUtil_Echo::track($this->multipleTokenData);
        $this->identifier = $this->getSingleTokenData();

        parent::afterRegistry();    
    }

    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->addElement($this->createToElement());
        $bridge->addElement($this->mailElements->createMethodElement());

        parent::addFormElements($bridge,$model);
        
        $bridge->addHidden('to');
    }

    protected function createToElement()
    {
        $valid   = array();
        $invalid = array();
        $options = array();

        foreach ($this->multipleTokenData as $tokenData) {
            if ($tokenData['can_email']) {
                $disabled  = false;
                $valid[]   = $tokenData['gto_id_token'];
            } else {
                $disabled  = true;
                $invalid[] = $tokenData['gto_id_token'];
            }
            $options[$tokenData['gto_id_token']] = $this->createToText($tokenData, $disabled);
        }

        $element = new Zend_Form_Element_MultiCheckbox('token_select', array(
            'disable'      => $invalid,
            'escape'       => (! $this->view),
            'label'        => $this->_('To'),
            'multiOptions' => $options,
            'required'     => true,
            ));

        $element->addValidator('InArray', false, array('haystack' => $valid));

        return $element;
    }



    private function createToText(array $tokenData, $disabled)
    {
        $menuFind = false;

        if ($disabled) {
            if ($tokenData['gto_completion_time']) {
                $title = $this->_('Survey has been taken.');
                $menuFind = array('controller' => array('track', 'survey'), 'action' => 'answer');
            } elseif (! $tokenData['grs_email']) {
                $title = $this->_('Respondent does not have an e-mail address.');
                $menuFind = array('controller' => 'respondent', 'action' => 'edit');
            } elseif ($tokenData['ggp_respondent_members'] == 0) {
                $title = $this->_('Survey cannot be taken by a respondent.');
            } else {
                $title = $this->_('Survey cannot be taken at this moment.');
                $menuFind = array('controller' => array('track', 'survey'), 'action' => 'edit');
            }
        } else {
            $title = null;
        }

        return $this->createMultiOption($tokenData,
            $this->getTokenName($tokenData),
            $tokenData['grs_email'],
            $tokenData['survey_short'],
            $title,
            $menuFind);
    }


    /**
     * Get the default token Id, from an array of tokenids. Usually the first token.
     */
    protected function getSingleTokenData()
    {
        $this->otherTokenData = $this->multipleTokenData;
        $singleTokenData = array_shift($this->otherTokenData);
        return $singleTokenData;
    }

    /**
     * Returns the name of the user mentioned in this token
     * in human-readable format
     *
     * @param  array $tokenData
     * @return string
     */
    public function getTokenName(array $tokenData = null)
    {
        $data[] = $tokenData['grs_first_name'];
        $data[] = $tokenData['grs_surname_prefix'];
        $data[] = $tokenData['grs_last_name'];

        $data = array_filter(array_map('trim', $data)); // Remove empties

        return implode(' ', $data);
    }

    protected function loadFormData()
    {
        parent::loadFormData();
        //if (!isset($this->formData['to']) || !is_array($this->formData['to'])) {
        if (!$this->request->isPost()) {
            $this->formData['token_select'] = array();
            foreach ($this->multipleTokenData as $tokenData) {
                if ($tokenData['can_email']) {
                    $this->formData['token_select'][] = $tokenData['gto_id_token'];
                }
            }
        
        
            $this->formData['multi_method'] = 'O';
        }
    }

    protected function sendMail() {
        $mails = 0;
        $updates = 0;

        if ($this->formData['multi_method'] == 'M') {
            foreach($this->multipleTokenData as $tokenData) {
                if (in_array($tokenData['gto_id_token'], $this->formData['token_select'])) {
                    $mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget, $tokenData);
                    $mailer->setFrom($this->fromOptions[$this->formData['from']]);
                    $mailer->setSubject($this->formData['subject']);
                    $mailer->setBody($this->formData['body']);
                    $mailer->setTemplateId($this->formData['select_template']);
                    $mailer->send();

                    $mails++;
                    $updates++;
                }
            }
        } else {
            $mailSent = false;
            foreach($this->multipleTokenData as $tokenData) {
                if (in_array($tokenData['gto_id_token'], $this->formData['token_select'])) {
                    if (!$mailSent) {
                        $mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget, $tokenData);
                        $mailer->setFrom($this->fromOptions[$this->formData['from']]);
                        $mailer->setSubject($this->formData['subject']);
                        $mailer->setBody($this->formData['body']);
                        $mailer->setTemplateId($this->formData['select_template']);
                        $mailer->send();
                        $mailSent == false;
                        $mails++;
                        $updates++;

                        if ($this->formData['multi_method'] == 'A') {
                            break;
                        }
                    } else {
                        if ($this->formData['multi_method'] == 'O') {
                            $this->mailer->updateToken($tokenData['gto_id_token']);
                            $updates++;
                        }
                    }
                }
            }
            $this->addMessage(sprintf($this->_('Sent %d e-mails, updated %d tokens.'), $mails, $updates));
        }
        
    }
}