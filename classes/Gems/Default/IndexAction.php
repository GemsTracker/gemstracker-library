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
     * For small numbers of organizations a multiline selectbox will be nice. This
     * setting handles how many lines will display at once. Use 1 for the normal
     * dropdown selectbox
     *
     * @var int
     */
    protected $organizationMaxLines = 6;

    /**
     * The default behaviour for showing a lost password button
     *
     * @var boolean
     */
    protected $showPasswordLostButton = true;

    /**
     * The default behaviour for showing an 'ask token' button
     *
     * @var boolean
     */
    protected $showTokenButton = true;

    /**
     * Returns a login form
     *
     * @param boolean $showToken Optional, show 'Ask token' button, $this->showTokenButton is used when not specified
     * @param boolean $showPasswordLost Optional, show 'Lost password' button, $this->showPasswordLostButton is used when not specified
     * @return Gems_User_Form_LoginForm
     */
    protected function createLoginForm($showToken = null, $showPasswordLost = null)
    {
        $args = MUtil_Ra::args(func_get_args(),
                array(
                    'showToken' => 'is_boolean',
                    'showPasswordLost' => 'is_boolean',
                    ),
                array(
                    'showToken' => $this->showTokenButton,
                    'showPasswordLost' => $this->showPasswordLostButton,
                    'labelWidthFactor' => $this->labelWidthFactor,
                    ));

        Gems_Html::init();

        return $this->loader->getUserLoader()->getLoginForm($args);
    }

    /**
     * Gets a reset password form.
     *
     * @return Gems_User_Form_ResetForm
     */
    protected function createResetForm()
    {
        $args = MUtil_Ra::args(func_get_args(),
                array(),
                array(
                    'labelWidthFactor' => $this->labelWidthFactor,
                    ));

        $this->initHtml();

        return $this->loader->getUserLoader()->getResetForm($args);
    }

    /**
     * Function for overruling the display of the login form.
     *
     * @param Gems_User_Form_LoginForm $form
     */
    protected function displayLoginForm(Gems_User_Form_LoginForm $form)
    {
        $this->view->form = $form;
    }

    /**
     * Function for overruling the display of the reset form.
     *
     * @param Gems_User_Form_ResetForm $form
     * @param mixed $errors
     */
    protected function displayResetForm(Gems_User_Form_ResetForm $form, $errors)
    {
        if ($form->hasResetKey()) {
            $this->html->h3($this->_('Execute password reset'));
            $p = $this->html->pInfo($this->_('We received your password reset request. '));

            if ($form->getOrganizationIsVisible()) {
                $p->append($this->_('Please enter the organization and username/e-mail address belonging to this request.'));
            } else {
                $p->append($this->_('Please enter the username or e-mail address belonging to this request.'));
            }
        } else {
            $this->html->h3($this->_('Request password reset'));

            $p = $this->html->pInfo();
            if ($form->getOrganizationIsVisible()) {
                $p->append($this->_('Please enter your organization and your username or e-mail address. '));
            } else {
                $p->append($this->_('Please enter your username or e-mail address. '));
            }
            $p->append($this->_('We will then send you an e-mail with a link you can use to reset your password.'));
        }

        if ($errors) {
            $this->addMessage($errors);
        }

        $this->html->append($form);
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
        $request = $this->getRequest();
        $form    = $this->createLoginForm();

        if ($request->isPost()) {
            if ($form->isValid($request->getPost(), false)) {
                $user = $form->getUser();

                // Retrieve these before the session is reset
                $previousRequestParameters = $this->session->previousRequestParameters;

                $user->setAsCurrentUser();

                if ($messages = $user->reportPasswordWeakness($request->getParam($form->passwordFieldName))) {
                    $user->setPasswordResetRequired(true);
                    $this->addMessage($this->_('Your password must be changed.'));
                    $this->addMessage($messages);
                }

                /**
                 * Fix current locale in cookies
                 */
                Gems_Cookies::setLocale($user->getLocale(), $this->basepath->getBasePath());

                /**
                 * Ready
                 */
                $this->addMessage(sprintf($this->_('Login successful, welcome %s.'), $user->getFullName()));

                /**
                 * Log the login
                 */
                Gems_AccessLog::getLog($this->db)->log("index.login", $this->getRequest(), null, $user->getUserId(), true);

                if ($previousRequestParameters) {
                    $this->_reroute(array('controller' => $previousRequestParameters['controller'], 'action' => $previousRequestParameters['action']), false);
                } else {
                    // This reroutes to the first available menu page after login.
                    //
                    // Do not user $user->gotoStartPage() as the menu is still set
                    // for no login.
                    $this->_reroute(array('controller' => null, 'action' => null), true);
                }
                return;
            } /*
            else {
                //Now present the user with an error message
                // $errors = MUtil_Ra::flatten($form->getMessages());
                // $this->addMessage($errors);
                MUtil_Echo::track($errors);

                //Also log the error to the log table
                //when the project has logging enabled
                $logErrors = join(' - ', $errors);
                $msg = sprintf('Failed login for : %s (%s) - %s', $request->getParam($form->usernameFieldName), $request->getParam($form->organizationFieldName), $logErrors);
                $log = Gems_AccessLog::getLog();
                $log->log('loginFail', $this->getRequest(), $msg, null, true);
            } // */
        }

        $this->displayLoginForm($form);
    }

    /**
     * Default logoff action
     */
    public function logoffAction()
    {
        $user = $this->loader->getCurrentUser();

        $this->addMessage(sprintf($this->_('Good bye: %s.'), $user->getFullName()));
        $user->unsetAsCurrentUser();
        Zend_Session::destroy();
        $this->_reroute(array('action' => 'index'), true);
    }

    /**
     * Reset password page.
     */
    public function resetpasswordAction()
    {
        $this->view->setScriptPath(GEMS_LIBRARY_DIR . '/views/scripts' );

        $request = $this->getRequest();
        $errors  = array();
        $form    = $this->createResetForm();
        if ($request->isPost() && $form->isValid($request->getParams())) {

            $user = $form->getUser();

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
                        $errors[] = $this->_('This key timed out or does not belong to this user.');
                    }
                } else {
                    $subjectTemplate = $this->_('Password reset requested');
                    $bbBodyTemplate  = $this->_("To set a new password for the [b]{organization}[/b] site [b]{project}[/b], please click on this link:\n{reset_url}");

                    $errors = $user->sendMail($subjectTemplate, $bbBodyTemplate, true);
                    if (! $errors) {
                        // Everything went OK!
                        $errors[] = $this->_('We sent you an e-mail with a reset link. Click on the link in the e-mail.');
                    }
                }
            } else {
                $errors[] = $this->_('No such user found or no e-mail address known or user cannot be reset.');
            }
        }

        $this->displayResetForm($form, $errors);
    }
}
