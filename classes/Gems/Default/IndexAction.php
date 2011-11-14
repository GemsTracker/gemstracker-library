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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Index controller, this one handles the default login / logout actions
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_IndexAction extends Gems_Controller_Action
{
    /**
     * The width factor for the label elements.
     *
     * Width = (max(characters in labels) * labelWidthFactor) . 'em'
     *
     * @var float
     */
    protected $labelWidthFactor = null;

    /**
     * @var GemsEscort
     */
    public $escort;

    /**
     * @var Gems_Menu
     */
    public $menu;

    /**
     * @var Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Returns a link for the token input page.
     *
     * @return MUtil_Form_Element_Html
     */
    protected function _getAskTokenLinkElement()
    {
        // Veld token
        $element = new MUtil_Form_Element_Html('askToken');
        $element->br();
        $element->actionLink(array('controller' => 'ask', 'action' => 'token'), $this->_('Enter your token...'));

        return $element;
    }

    /**
     * Returns a basic form for this action.
     *
     * @param $description Optional description, %s is filled with project name.
     * @return Gems_Form
     */
    protected function _getBasicForm($description = null)
    {
        Gems_Html::init();

        $form = new Gems_Form(array('labelWidthFactor' => $this->labelWidthFactor));
        $form->setMethod('post');
        if ($description) {
            $form->setDescription(sprintf($description, $this->project->getName()));
        }

        return $form;
    }

    /**
     * Returns an element for keeping a reset key.
     *
     * @return Zend_Form_Element_Hidden
     */
    protected function _getKeyElement()
    {
        return new Zend_Form_Element_Hidden('key');
    }

    /**
     * Returns a login form
     *
     * @return Gems_Form
     */
    protected function _getLoginForm()
    {
        $form = $this->_getBasicForm($this->_('Login to %s application'));
        $form->addElement($this->_getOrganizationElement());
        $form->addElement($this->_getUserLoginElement());
        $form->addElement($this->_getPasswordElement());
        $form->addElement($this->_getSubmitButton($this->_('Login')));
        $form->addElement($this->_getAskTokenLinkElement());
        $form->addElement($this->_getResetLinkElement());

        return $form;
    }

    /**
     * Returns a link to the login page
     *
     * @return MUtil_Form_Element_Html
     */
    protected function _getLoginLinkElement()
    {
        // Reset password
        $element = new MUtil_Form_Element_Html('resetPassword');
        $element->br();
        $element->actionLink(array('controller' => 'index', 'action' => 'login'), $this->_('Back to login'));

        return $element;
    }

    /**
     * Returns an element for determining / selecting the organization.
     *
     * @return Zend_Form_Element_Xhtml
     */
    protected function _getOrganizationElement()
    {
        if ($this->escort instanceof Gems_Project_Organization_SingleOrganizationInterface) {
            $element = new Zend_Form_Element_Hidden('organization');
            $element->setValue($this->escort->getRespondentOrganization());
        } else {
            $element = new Zend_Form_Element_Select('organization');
            $element->setLabel($this->_('Organization'));
            $element->setMultiOptions($this->util->getDbLookup()->getOrganizations());
            $element->setRequired(true);

            if (! $this->_request->isPost()) {
                $element->setValue($this->escort->getCurrentOrganization());
            }
        }

        return $element;
    }

    /**
     * Returns a password element.
     *
     * @return Zend_Form_Element_Password
     */
    protected function _getPasswordElement()
    {
        // Veld password
        $element = new Zend_Form_Element_Password('password');
        $element->setLabel($this->_('Password'));
        $element->setAttrib('size', 10);
        $element->setAttrib('maxlength', 20);
        $element->setRequired(true);
        $element->addValidator(new Gems_User_LoginPasswordValidator($this->loader->getUserLoader(), 'userlogin', 'organization', $this->translate));

        return $element;
    }

    /**
     * Gets a reset password form.
     *
     * @return Gems_Form
     */
    protected function _getResetForm()
    {
        $form = $this->_getBasicForm($this->_('Reset password for %s application'));
        $form->addElement($this->_getKeyElement());
        $form->addElement($this->_getOrganizationElement());
        $form->addElement($this->_getUserLoginElement());
        $form->addElement($this->_getSubmitButton($this->_('Reset password')));
        $form->addElement($this->_getLoginLinkElement());

        return $form;
    }

    /**
     * Returns a link to the reset password page
     *
     * @return MUtil_Form_Element_Html
     */
    protected function _getResetLinkElement()
    {
        // Reset password
        $element = new MUtil_Form_Element_Html('resetPassword');
        $element->br();
        $element->actionLink(array('controller' => 'index', 'action' => 'resetpassword'), $this->_('Lost password'));

        return $element;
    }

    /**
     * Returns a submit button.
     *
     * @param string $label
     * @return Zend_Form_Element_Submit
     */
    protected function _getSubmitButton($label)
    {
        // Submit knop
        $element = new Zend_Form_Element_Submit('button');
        $element->setLabel($label);
        $element->setAttrib('class', 'button');

        return $element;
    }

    /**
     * Returns a login name element.
     *
     * @return Zend_Form_Element_Text
     */
    protected function _getUserLoginElement()
    {
        // Veld inlognaam
        $element = new Zend_Form_Element_Text('userlogin');
        $element->setLabel($this->_('Username'));
        $element->setAttrib('size', 10);
        $element->setAttrib('maxlength', 20);
        $element->setRequired(true);

        return $element;
    }

    /**
     * Dummy: always rerouted by GemsEscort
     */
    public function indexAction() { }

    /**
     * Default login page
     */
    public function loginAction()
    {
        // If already logged in, try to redirect to the first allowed and visible menu item
        // if that fails, try to reroute to respondent/index
        if ($this->loader->getCurrentUser()->isActive()) {
            if ($menuItem = $this->menu->findFirst(array('allowed' => true, 'visible' => true))) {
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoRoute($menuItem->toRouteUrl($this->getRequest()));
            } else {
                $this->_reroute(array('controller' => 'respondent', 'action'=>'index'));
            }
        }

        $form = $this->_getLoginForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost(), false)) {

                $user = $this->loader->getUser($request->getParam('userlogin'), $request->getParam('organization'));

                if ($user->isActive()) {
                    $user->setAsCurrentUser();

                    /**
                     * Fix current locale / organization in cookies
                     */
                    Gems_Cookies::setLocale($user->getLocale(), $this->basepath->getBasePath());
                    Gems_Cookies::setOrganization($user->getOrganizationId(), $this->basepath->getBasePath());

                    /**
                     * Ready
                     */
                    $this->addMessage(sprintf($this->_('Login successful, welcome %s.'), $user->getFullName()));

                    /**
                     * Log the login
                     */
                    Gems_AccessLog::getLog($this->db)->log("index.login", $this->getRequest(), null, $user->getUserId(), true);

                    if ($previousRequestParameters = $this->session->previousRequestParameters) {
                        $this->_reroute(array('controller' => $previousRequestParameters['controller'], 'action' => $previousRequestParameters['action']), false);
                    } else {
                        // This reroutes to the first available menu page after login
                        $this->_reroute(array('controller' => null, 'action' => null), true);
                    }
                    return;
                }
            } else {
                $errors = $form->getErrors();
            }
        }
        $this->view->form = $form;
    }

    /**
     * Default logoff action
     */
    public function logoffAction()
    {
        $user = $this->loader->getCurrentUser();

        $this->addMessage(sprintf($this->_('Good bye: %s.'), $user->getFullName()));
        $user->unsetAsCurrentUser();
        $this->_reroute(array('action' => 'index'), true);
    }

    /**
     * Reset password page.
     */
    public function resetpasswordAction()
    {
        $this->view->setScriptPath(GEMS_LIBRARY_DIR . '/views/scripts' );

        $request = $this->getRequest();
        $form = $this->_getResetForm();
        if ($request->isPost() && $form->isValid($request->getPost())) {

            $user = $this->loader->getUser($request->getParam('userlogin'), $request->getParam('organization'));

            If ($user->canResetPassword()) {
                if ($key = $request->getParam('key')) {
                    // Key has been passed by mail
                    if ($user->checkPasswordResetKey($key)) {
                        $user->setPasswordResetRequired(true);
                        $user->setAsCurrentUser();
                        $this->addMessage($this->_('Reset accepted, enter your new password.'));
                        $user->gotoStartPage($this->menu, $request);
                        return;
                    } else {
                        $this->addMessage($this->_('This key timed out or does not belong to this user.'));
                    }
                } else {
                    // P{ass mail by key
                    $mail = new MUtil_Mail();
                    $mail->setFrom('mjong@magnafacta.nl');
                    $mail->addTo($user->getEmailAddress(), $user->getFullName());

                    if (isset($this->escort->project->email) && isset($this->escort->project->email['bcc'])) {
                        $mail->addBcc($this->escort->project->email['bcc']);
                    }


                    $key = $user->getPasswordResetKey();

                    $url = $this->util->getCurrentURI('index/resetpassword/key/' . $key);

                    $mail->setSubject($this->_('Password reset requested'));
                    $mail->setBodyText(sprintf($this->_('To reset your password for %s, please click this link: %s'), GEMS_PROJECT_NAME_UC, $url));


                    try {
                        $mail->send();
                        $this->addMessage($this->_('We sent you an e-mail with a reset link. Click on the link in the e-mail.'));
                    } catch (Exception $e) {
                        $this->addMessage($this->_('Unable to send e-mail.'));
                        throw $e;
                    }
                }
            } else {
                $this->addMessage($this->_('No such user found or no e-mail address known or user cannot be reset.'));
            }
        }
        if ($request->getParam('key')) {
            $this->addMessage($this->_('We received your password reset key.'));
            $this->addMessage($this->_('Please enter the organization and username belonging to this key.'));
        }
        $this->view->form = $form;
    }
}
