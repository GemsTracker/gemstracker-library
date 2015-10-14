<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AdminPasswordResetSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
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
            $createElement = new \MUtil_Form_Element_FakeSubmit('create_account');
            $createElement->setLabel($this->_('Create account mail'))
                        ->setAttrib('class', 'button')
                        ->setOrder(0);

            $form->addElement($createElement);

            $resetElement = new \MUtil_Form_Element_FakeSubmit('reset_password');
            $resetElement->setLabel($this->_('Reset password mail'))
                        ->setAttrib('class', 'button')
                        ->setOrder(1);
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
            $mail->setOrganizationFrom();
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
            $mail->setOrganizationFrom();
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
