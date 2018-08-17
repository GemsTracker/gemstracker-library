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
     *
     * @var \Gems_Loader
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
            $createElement = new \MUtil_Form_Element_FakeSubmit('create_account');
            $createElement->setLabel($this->_('Create account mail'))
                        ->setAttrib('class', 'button')
                        ->setOrder($order++);

            $form->addElement($createElement);

            $resetElement = new \MUtil_Form_Element_FakeSubmit('reset_password');
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
        if (isset($this->formData['create_account']) && $this->formData['create_account']) {
            $mail = $this->loader->getMailLoader()->getMailer('staffPassword', $this->user->getUserId());
            if ($mail->setCreateAccountTemplate()) {
                $mail->send();
                $this->addMessage($this->_('Create account mail sent'));
                $this->setAfterSaveRoute();
            } else {
                $this->addMessage($this->_('No default Create Account mail template set in organization or project'));
            }
            return;
        }

        if (isset($this->formData['reset_password']) && $this->formData['reset_password']) {
            $mail = $this->loader->getMailLoader()->getMailer('staffPassword', $this->user->getUserId());
            if ($mail->setResetPasswordTemplate()) {
                $mail->send();
                $this->addMessage($this->_('Reset password mail sent'));
                $this->setAfterSaveRoute();
            } else {
                $this->addMessage($this->_('No default Reset Password mail template set in organization or project'));
            }
        }
    }
}
