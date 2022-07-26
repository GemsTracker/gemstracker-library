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
class TokenBulkMailFormSnippet extends \Gems\Snippets\Mail\MailFormSnippet
{
    protected $identifier;

    protected $loader;

    protected $multipleTokenData;

    protected $otherTokenData;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        //\MUtil\EchoOut\EchoOut::track($this->multipleTokenData);
        $this->identifier = $this->getSingleTokenData();

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

        $element = new \Zend_Form_Element_MultiCheckbox('token_select', array(
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
                $menuFind = array('controller' => 'track', 'action' => 'answer');
            } elseif (! $tokenData['gr2o_email']) {
                $title = $this->_('Respondent does not have an e-mail address.');
                $menuFind = array('controller' => 'respondent', 'action' => 'edit');
            } elseif ($tokenData['ggp_respondent_members'] == 0) {
                $title = $this->_('Survey cannot be taken by a respondent.');
            } else {
                $title = $this->_('Survey cannot be taken at this moment.');
                $menuFind = array('controller' => 'track', 'action' => 'edit');
            }
        } else {
            $title = null;
        }

        $mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget, $tokenData);
        /* @var $mailer \Gems\Mail\TokenMailer */
        $token = $mailer->getToken();

        return $this->createMultiOption($tokenData,
            $token->getRespondentName(),
            $token->getEmail(),
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

    protected function sendMail()
    {
        $mails = 0;
        $updates = 0;
        $sentMailAddresses = array();

        foreach($this->multipleTokenData as $tokenData) {
            if (in_array($tokenData['gto_id_token'], $this->formData['token_select'])) {
                $mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget, $tokenData);
                /* @var $mailer \Gems\Mail\TokenMailer */
                $token = $mailer->getToken();
                $email = $token->getEmail();

                if (!empty($email)) {
                    if ($this->formData['multi_method'] == 'M') {
                        $mailer->setFrom($this->fromOptions[$this->formData['from']]);
                        $mailer->setSubject($this->formData['subject']);
                        $mailer->setBody(htmlspecialchars_decode($this->formData['mailBody']));
                        $mailer->setTemplateId($this->formData['select_template']);

                        try {
                            $mailer->send();

                            $mails++;
                            $updates++;

                        } catch (\Zend_Mail_Transport_Exception $exc) {
                            // Sending failed
                            $this->addMessage(sprintf($this->_('Sending failed for token %s with reason: %s.'), $token->getTokenId(), $exc->getMessage()));
                        }

                    } elseif (!isset($sentMailAddresses[$email])) {
                        $mailer->setFrom($this->fromOptions[$this->formData['from']]);
                        $mailer->setSubject($this->formData['subject']);
                        $mailer->setBody(htmlspecialchars_decode($this->formData['mailBody']));
                        $mailer->setTemplateId($this->formData['select_template']);

                        try {
                            $mailer->send();
                            $mails++;
                            $updates++;

                            $sentMailAddresses[$email] = true;

                        } catch (\Zend_Mail_Transport_Exception $exc) {
                            // Sending failed
                            $this->addMessage(sprintf($this->_('Sending failed for token %s with reason: %s.'), $token->getTokenId(), $exc->getMessage()));
                        }

                    } elseif ($this->formData['multi_method'] == 'O') {
                        $this->mailer->updateToken($tokenData['gto_id_token']);
                        $updates++;
                    }
                }
            }
        }

        $this->addMessage(sprintf($this->_('Sent %d e-mails, updated %d tokens.'), $mails, $updates));

    }
}