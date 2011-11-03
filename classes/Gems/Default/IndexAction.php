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
     * Extension point, use different auth adapter if needed depending on the provided formValues
     *
     * This could be an organization passed in the login-form or something else.
     *
     * @param array $formValues
     * @return Zend_Auth_Adapter_Interface
     */
    protected function _getAuthAdapter($formValues) {
        $adapter = new Zend_Auth_Adapter_DbTable($this->db, 'gems__users', 'gsu_login', 'gsu_password');
        $adapter->setIdentity($formValues['userlogin']);
        $adapter->setCredential($this->escort->passwordHash(null, $formValues['password'], false));
        return $adapter;
    }

    /**
     * New version of login form
     *
     * @return Gems_Form
     */
    protected function _getLoginForm()
    {
        Gems_Html::init();

        $this->track[] = 'Get login form.';

        $delayFactor = (isset($this->project->account) && isset($this->project->account['delayFactor']) ? $this->project->account['delayFactor'] : null);

        $form = new Gems_Form(array('labelWidthFactor' => $this->labelWidthFactor));
        $form->setMethod('post');
        $form->setDescription(sprintf($this->_('Login to %s application'), $this->project->name));

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
        $form->addElement($element);

        // Veld inlognaam
        $element = new Zend_Form_Element_Text('userlogin');
        $element->setLabel($this->_('Username'));
        $element->setAttrib('size', 10);
        $element->setAttrib('maxlength', 20);
        $element->setRequired(true);
        $form->addElement($element);

        // Veld password
        $element = new Zend_Form_Element_Password('password');
        $element->setLabel($this->_('Password'));
        $element->setAttrib('size', 10);
        $element->setAttrib('maxlength', 20);
        $element->setRequired(true);
        //$element->addValidator(new Gems_Validate_GemsPasswordUsername('userlogin', 'password', $this->db, $delayFactor));
        $form->addElement($element);

        // Submit knop
        $element = new Zend_Form_Element_Submit('button');
        $element->setLabel($this->_('Login'));
        $element->setAttrib('class', 'button');
        $form->addElement($element);

        // Veld token
        $element = new MUtil_Form_Element_Html('askToken');
        $element->br();
        $element->actionLink(array('controller' => 'ask', 'action' => 'token'), $this->_('Enter your token...'));
        $form->addElement($element);

        // Reset password
        $element = new MUtil_Form_Element_Html('resetPassword');
        $element->br();
        $element->actionLink(array('controller' => 'index', 'action' => 'resetpassword'), $this->_('Lost password'));
        $form->addElement($element);

        return $form;
    }

    // Dummy: always rerouted by GemsEscort
    public function indexAction() { }

    public function loginAction()
    {
        /**
         * If already logged in, try to redirect to the first allowed and visible menu item
         * if that fails, try to reroute to respondent/index
         */
        if (isset($this->session->user_id)) {
            if ($menuItem = $this->menu->findFirst(array('allowed' => true, 'visible' => true))) {
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoRoute($menuItem->toRouteUrl($this->getRequest()));
            } else {
                $this->_reroute(array('controller' => 'respondent', 'action'=>'index'));
            }
        }
        // MUtil_Echo::track(get_class($this->loader->getUser('super', null)));

        $form = $this->_getLoginForm();

        if ($this->_request->isPost()) {
            if ($form->isValid($_POST, false)) {
                /*
                if ($user = $this->loader->getUser($_POST['userlogin'], $_POST['organization'])) {

                } // */

                if (isset($this->project->admin) && $this->project->admin['user'] == $_POST['userlogin'] && $this->project->admin['pwd'] == $_POST['password']) {
                    $this->session->user_id    = 2000;
                    $this->session->user_name  = $_POST['userlogin'];
                    $this->session->user_group = 800;
                    $this->session->user_role  = 'master';
                    $this->session->user_organization_id   = 70;
                    $this->session->user_organization_name = 'SUPER ADMIN';
                    $this->session->user_style = 'gems';
                    //Als er nog geen tabellen zijn, moet dit ingesteld worden
                    //@@TODO Nog kijken hoe beter op te lossen (met try op tabel ofzo)
                    $this->session->allowedOrgs = array($this->session->user_organization_id=>$this->session->user_organization_name);

                    /**
                     * Ready
                     */
                    $this->addMessage(sprintf($this->_('Login successful, welcome %s.'), $this->session->user_name));
                    $this->_reroute(array('controller' => 'database', 'action' => 'index'), true);
                    return;
                }
                //Now check authentication
                $adapter = $this->_getAuthAdapter($form->getValues());
                $auth    = Gems_Auth::getInstance();
                $result  = $auth->authenticate($adapter, $_POST['userlogin']);

                // Allow login using old password.
                if ((! $result->isValid()) && ($userid = $this->db->fetchOne("SELECT gsu_id_user FROM gems__users WHERE gsu_active = 1 AND gsu_password IS NULL AND gsu_login = ?", $_POST['userlogin']))) {

                    $adapter = new Zend_Auth_Adapter_DbTable($this->db, 'gems__staff', 'gsf_id_user', 'gsf_password');
                    $adapter->setIdentity($userid);
                    $adapter->setCredential(md5($_POST['password'], false));
                    $result  = $auth->authenticate($adapter, $_POST['userlogin']);
                    // MUtil_Echo::track('old autho');
                } else {
                    // MUtil_Echo::track('new autho');
                }

                if (!$result->isValid()) {
                    // Invalid credentials
                    $errors = $result->getMessages();
                    $this->addMessage($errors);
                    $code   = $result->getCode();
                    if ($code != Gems_Auth::ERROR_PASSWORD_DELAY) {
                        $this->escort->afterFailedLogin();
                    }

                    $this->view->form = $form;
                } else {
                    // Load login data
                    $this->escort->loadLoginInfo($_POST['userlogin']);

                    /**
                     * Perform any project specific post login activities
                     */
                    $this->escort->afterLogin($_POST['userlogin']);

                    /**
                     * Fix current locale
                     */
                    Gems_Cookies::setLocale($this->session->user_locale, $this->basepath->getBasePath());

                    /**
                     * Ready
                     */
                    $this->addMessage(sprintf($this->_('Login successful, welcome %s.'), $this->session->user_name));

                    /**
                     * Log the login
                     */
                    Gems_AccessLog::getLog($this->db)->log("index.login", $this->getRequest(), null, $this->session->user_id, true);

                    if ($previousRequestParameters = $this->session->previousRequestParameters) {
                        $this->_reroute(array('controller' => $previousRequestParameters['controller'], 'action' => $previousRequestParameters['action']), false);
                    } else {
                        // This reroutes to the first available menu page after login
                        $this->_reroute(array('controller' => null, 'action' => null), true);
                    }
                }
            } else {
                $errors = $form->getErrors();

                $this->view->form = $form;
            }
        } else {
            $this->view->form = $form;
        }
    }

    public function logoffAction()
    {
        $this->addMessage($this->_('Good bye: ') . $this->session->user_name);
        Gems_Auth::getInstance()->clearIdentity();
        $this->escort->afterLogout();
        $this->_reroute(array('action' => 'index'), true);
    }

    protected function _getRandomPassword()
    {
        $salt = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ0123456789";
        $pass = "";

        srand((double)microtime()*1000000);

        $i = 0;

        while ($i <= 7)
        {
            $num = rand() % strlen($salt);
            $tmp = substr($salt, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }

        return $pass;
    }

    protected function _getResetForm()
    {
        $form = new Gems_Form(array('labelWidthFactor' => $this->labelWidthFactor));
        $form->setMethod('post');
        $form->setDescription(sprintf($this->_('Reset password for %s application'), $this->project->name));

        // Veld inlognaam
        $element = new Zend_Form_Element_Text('userlogin');
        $element->setLabel($this->_('Username'));
        $element->setAttrib('size', 10);
        $element->setAttrib('maxlength', 20);
        $element->setRequired(true);
        $form->addElement($element);

        // Submit knop
        $element = new Zend_Form_Element_Submit('button');
        $element->setLabel($this->_('Reset password'));
        $element->setAttrib('class', 'button');
        $form->addElement($element);

        return $form;
    }

    public function resetpasswordAction()
    {
        $this->view->setScriptPath(GEMS_LIBRARY_DIR . '/views/scripts' );

        $form = $this->_getResetForm();
        $mail = new MUtil_Mail();
        $mail->setFrom('noreply@erasmusmc.nl');

        if (isset($this->escort->project->email) && isset($this->escort->project->email['bcc'])) {
            $mail->addBcc($this->escort->project->email['bcc']);
        }

        if ($this->_request->isPost() && $form->isValid($_POST)) {
            $sql = $this->db->quoteInto("SELECT gsu_id_user, gsf_email, gsu_reset_key, DATEDIFF(NOW(), gsu_reset_requested) AS gsf_days FROM gems__users INNER JOIN gems__staff ON gsu_id_user = gsf_id_user WHERE gsu_login = ?", $_POST['userlogin']);
            $result = $this->db->fetchRow($sql);

            if (empty($result) || empty($result['gsf_email'])) {
                $this->addMessage($this->_('No such user found or no e-mail address known'));
            } else if (!empty($result['gsu_reset_key']) && $result['gsf_days'] < 1) {
                $this->addMessage($this->_('Reset e-mail already sent, please try again after 24 hours'));
            } else {
                $email = $result['gsf_email'];
                $key = md5(time() . $email);
                $url = $this->util->getCurrentURI('index/resetpassword/key/' . $key);

                $this->db->update('gems__users', array('gsu_reset_key' => $key, 'gsu_reset_requested' => new Zend_Db_Expr('NOW()')), 'gsu_id_user = ' . $result['gsu_id_user']);

                $mail->setSubject('Password reset requested');
                $mail->setBodyText('To reset your password, please click this link: ' . $url);

                $mail->addTo($email);

                try {
                    $mail->send();
                    $this->addMessage($this->_('Follow the instructions in the e-mail'));
                } catch (Exception $e) {
                    $this->addMessage($this->_('Unable to send e-mail'));
                    throw $e;
                }
            }
        } else if ($key = $this->_request->getParam('key')) {
            $sql = $this->db->quoteInto("SELECT gsu_id_user, gsf_email FROM gems__users INNER JOIN gems__staff ON gsu_id_user = gsf_id_user WHERE gsu_reset_key = ?", $key);
            $result = $this->db->fetchRow($sql);

            if (!empty($result)) {
                // generate new password
                $password = $this->_getRandomPassword();
                $passwordHash = $this->escort->passwordHash(null, $password, false);

                $mail->setSubject('New password');
                $mail->setBodyText('Your new password has been generated. Your new password is: ' . $password);

                $mail->addTo($result['gsf_email']);

                try {
                    $mail->send();
                    $this->addMessage($this->_('An e-mail was sent containing your new password'));
                    $this->db->update('gems__users', array('gsu_reset_key' => new Zend_Db_Expr('NULL'), 'gsu_reset_requested' => new Zend_Db_Expr('NULL'), 'gsu_password' => $passwordHash), 'gsu_id_user = ' . $result['gsu_id_user']);
                    $this->_reroute(array('action' => 'index'), true);
                } catch (Exception $e) {
                    $this->addMessage($this->_('Unable to send e-mail'));
                    throw $e;
                }
            } else {
                $this->addMessage($this->_('Unknown request'));
            }
        }

        $this->view->form = $form;
    }
}
