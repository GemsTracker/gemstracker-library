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
class Gems_Email_MailTemplateForm extends Gems_Email_EmailFormAbstract
{
    protected $fromTest;
    protected $sendTest;
    protected $toTest;

    public function applyElements()
    {
        $this->fromTest = $this->createFromTestElement();
        $this->toTest   = $this->createToTestElement();
        $this->sendTest = $this->createSendButton($this->escort->_('Send (test)'));

        $this->addElement($this->createMessageIdElement());
        if ($this->escort instanceof Gems_Project_Organization_MultiOrganizationInterface) {
            $this->addElement($this->createOrganizationSet());
        }
        $this->addElement($this->createSubjectElement());
        $this->addElement($this->createBodyElement());
        $this->addElement($this->createPreviewButton());
        $this->addElement($this->toTest);
        $this->addElement($this->fromTest);
        if ($this->escort instanceof Gems_Project_Organization_MultiOrganizationInterface) {
            $this->addElement($this->createOrganizationSelect());
        }
        $this->addElement($this->sendTest);
        $this->addElement($this->createSaveButton());
        $this->addElement($this->createPreviewHtmlElement(false));
        $this->addElement($this->createPreviewTextElement());
        $this->addElement($this->createAvailableFieldsElement());

        return $this;
    }

    protected function createFromTestElement()
    {
        return $this->createEmailElement('from', $this->escort->_('From'), true);
    }

    protected function createOrganizationSelect()
    {
        // Select organization
        $sql = 'SELECT gor_id_organization, gor_name
            FROM gems__organizations
            WHERE gor_active=1 AND gor_id_organization IN (SELECT gto_id_organization FROM gems__tokens)
            ORDER BY gor_name';

        $options = $this->escort->db->fetchPairs($sql);
        natsort($options);

        return new Zend_Form_Element_Select('gto_id_organization', array('multiOptions' => $options, 'label' => $this->escort->_('Test using')));
    }

    protected function createOrganizationSet()
    {
        $options['multiOptions'] = $this->escort->getUtil()->getDbLookup()->getOrganizations();
        $options['label'] = $this->escort->_('Organizations');

        return new Zend_Form_Element_MultiCheckbox('gmt_organizations', $options);
    }

    protected function createSaveButton()
    {
        $element = new Zend_Form_Element_Submit('save_button', $this->escort->_('Save'));
        $element->setAttrib('class', 'button');

        return $element;
    }

    protected function createSubjectElement($hidden = false)
    {
        $element = parent::createSubjectElement();
        $element->addValidator($this->model->createUniqueValidator('gmt_subject'));

        return $element;
    }

    protected function createToTestElement()
    {
        return $this->createEmailElement('to', $this->escort->_('To (test)'), true, true);
    }

    public function getTokenData()
    {
        if (! $this->_tokenData) {
            $model = $this->escort->getLoader()->getTracker()->getTokenModel();

            $org_id = $this->getValue('gto_id_organization');
            $filter['gto_id_organization'] = $org_id ? $org_id : $this->escort->getCurrentOrganization();
            $filter[] = 'grs_email IS NOT NULL';

            // Without sorting we get the fastest load times
            $sort = false;

            $tokenData = $model->loadFirst($filter, $sort);

            if (! $tokenData) {
                // Well then just try to get any token
                $tokenData = $model->loadFirst(false, $sort);
                if (! $tokenData) {
                    //No tokens, just add an empty array and hope we get no notices later
                    $tokenData = array();
                }
            }

            $this->setTokenData($tokenData);
        }
        return parent::getTokenData();
    }

    /**
     * Validate the form
     *
     * @param  array $data
     * @return boolean
     */
    public function isValid($data)
    {
        if (parent::isValid($data)) {
            if ($this->sendTest->isChecked()) {
                if ($msg = $this->processTestMail()) {
                    $this->addMessage($msg);
                } else {
                    $this->addMessage($this->escort->_('Test mail send, changes not saved!'));
                }
                $this->addInputError = false;
            } else {
                return true;
            }
        }

        return false;
    }

    protected function loadData(Zend_Controller_Request_Abstract $request)
    {
        $this->model->applyRequest($request);
        if ($this->model->hasFilter()) {
            $data = $this->model->loadFirst();

            if (isset($data['gmt_organizations']) && (! is_array($data['gmt_organizations']))) {
                $data['gmt_organizations'] = explode('|', trim($data['gmt_organizations'], '|'));
            }
        } else {
            $data = $this->model->loadNew();
            $data['gmt_organizations'] = array_keys($this->escort->getUtil()->getDbLookup()->getOrganizations());
        }

        if (isset($this->escort->session->user_email)) {
            $email = $this->escort->session->user_email;
        } else {
            $tokenData = $this->getTokenData();
            if ($tokenData['gor_contact_email']) {
                $email = $tokenData['gor_contact_email'];
            } elseif (isset($this->escort->project->email['site'])) {
                $email = $this->escort->project->email['site'];
            } else {
                $email = null;
            }
        }
        $data['from'] = $email;
        $data['to']   = $email;
        $data['gto_id_organization'] = $this->escort->getCurrentOrganization();

        return $data;
    }

    public function processTestMail()
    {
        $tos  = preg_split('/[\s,;]+/', $this->toTest->getValue());
        $from = $this->fromTest->getValue();

        $this->mailer->setSubject($this->getValue('gmt_subject'));
        $this->mailer->setBody($this->getValue('gmt_body'));

        $tokenData = $this->getTokenData();

        foreach ($tos as $to) {
            if ($message = $this->mailer->sendMail($to, null, $from, null, $tokenData)) {
                return $message;
            }
        }
        return false;
    }

    protected function processValid()
    {
        $data = $this->getValues();
        if (isset($data['gmt_organizations']) && is_array($data['gmt_organizations'])) {
            $data['gmt_organizations'] = '|' . implode('|', $data['gmt_organizations']) . '|';
        }

        $this->model->save($data);

        return 1 + $this->model->getChanged();
    }
}
