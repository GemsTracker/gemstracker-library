<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\User;

use Gems\Communication\CommunicationRepository;
use Symfony\Component\Mime\Address;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 11:47:11
 */
class AdminPasswordResetSnippet extends PasswordResetSnippet
{
    /**
     * @var bool Normally we check if the user is active ON THIS SITE, but not in the admin panel
     */
    protected $checkCurrentOrganization = true;

    /**
     * @var CommunicationRepository
     */
    protected $communicationRepository;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        if ($this->user->hasEmailAddress()) {
            $order = count($form->getElements())-1;
            $createElement = new \MUtil\Form\Element\FakeSubmit('create_account');
            $createElement->setLabel($this->_('Create account mail'))
                        ->setAttrib('class', 'button')
                        ->setOrder($order++);

            $form->addElement($createElement);

            $resetElement = new \MUtil\Form\Element\FakeSubmit('reset_password');
            $resetElement->setLabel($this->_('Reset password mail'))
                        ->setAttrib('class', 'button')
                        ->setOrder($order++);
            $form->addElement($resetElement);
        }

        parent::addFormElements($form);
    }

    /**
     * The message to display when the change is not allowed
     *
     * @return string
     */
    protected function getNotAllowedMessage()
    {
        return $this->_('You are not allowed to change this password.');
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return sprintf($this->_('Reset password for: %s'), $this->user->getFullName());
    }

    /**
     * Hook that allows actions when the form is submitted, but it was not the submit button that was checked
     *
     * When not rerouted, the form will be populated afterwards
     */
    protected function onFakeSubmit()
    {
        $organization = $this->user->getBaseOrganization();
        $email = $this->communicationRepository->getNewEmail();
        $email->addTo(new Address($this->user->getEmailAddress(), $this->user->getFullName()));
        $email->addFrom(new Address($organization->getEmail()));

        $template = $this->communicationRepository->getTemplate($organization);
        $language = $this->communicationRepository->getCommunicationLanguage($this->user->getLocale());
        $mailFields = $this->communicationRepository->getUserPasswordMailFields($this->user, $language);
        $mailer = $this->communicationRepository->getMailer($organization->getEmail());

        if (isset($this->formData['create_account']) && $this->formData['create_account']) {
            $templateId = $this->communicationRepository->getCreateAccountTemplate($organization);
            if ($templateId) {
                $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
                $email->subject($mailTexts['subject'], $mailFields);
                $email->htmlTemplate($template, $mailTexts['body'], $mailFields);

                $mailer->send($email);

                $this->addMessage($this->_('Create account mail sent'));
                $this->setAfterSaveRoute();
            } else {
                $this->addMessage($this->_('No default Create Account mail template set in organization or project'));
            }
            return;
        }

        if (isset($this->formData['reset_password']) && $this->formData['reset_password']) {
            $templateId = $this->communicationRepository->getResetPasswordTemplate($organization);
            if ($templateId) {
                $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
                $email->subject($mailTexts['subject'], $mailFields);
                $email->htmlTemplate($template, $mailTexts['body'], $mailFields);

                $mailer->send($email);

                $this->addMessage($this->_('Reset password mail sent'));
                $this->setAfterSaveRoute();
            } else {
                $this->addMessage($this->_('No default Reset Password mail template set in organization or project'));
            }
        }
    }
}
