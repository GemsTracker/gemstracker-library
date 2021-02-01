<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
class Gems_Default_IndexAction extends \Gems_Controller_Action
{
    /**
     * Gems only parameters used for the login action. Can be overruled
     * by setting $this->loginParameters
     *
     * @var array Mixed key => value array for snippet initializPdfation
     */
    private $_loginDefaultParameters = [
        'loginForm'          => 'createLoginForm',
        'loginStatusTracker' => 'getLoginStatusTracker',
        'resetParam'         => 'reset',
    ];

    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     *
     * @var \Gems_Util_BasePath
     */
    public $basepath;

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The width factor for the label elements.
     *
     * Width = (max(characters in labels) * labelWidthFactor) . 'em'
     *
     * @var float
     */
    protected $labelWidthFactor = null;

    /**
     * Use a flat login (false = default) or a layered login where you first
     * select a parent organization and then see a list of child organizations.
     *
     * @var boolean
     */
    protected $layeredLogin = false;

    /**
     * The parameters used for the index action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $loginParameters = [];

    /**
     * The snippets used for the login action
     *
     * @var mixed String or array of snippets name
     */
    protected $loginSnippets = [
        'Login\\UserLoginFormSnippet',
        'Login\\TwoFactorCheckSnippet',
        'Login\\CheckPasswordChangeRequiredSnippet',
        'Login\\SetAsCurrentUserSnippet',
        'Login\\RedirectToRequestSnippet',
        'Login\\GotoStartPageSnippet',
        ];

    /**
     * For small numbers of organizations a multiline selectbox will be nice. This
     * setting handles how many lines will display at once. Use 1 for the normal
     * dropdown selectbox
     *
     * @var int
     */
    protected $organizationMaxLines = 6;

    /**
     * When true, the rese4t form returns to the login page after sending a change request
     *
     * @var boolean
     */
    protected $returnToLoginAfterReset = true;

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
     * Set to true in child class for automatic creation of $this->html.
     *
     * To initiate the use of $this->html from the code call $this->initHtml()
     *
     * Overrules $useRawOutput.
     *
     * @see $useRawOutput
     * @var boolean $useHtmlView
     */
    public $useHtmlView = true;

    /**
     *
     * @var \Gems_Util
     */
    public $util;

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processParameters(array $input)
    {
        $output = array();

        foreach ($input as $key => $value) {
            if (is_string($value) && method_exists($this, $value)) {
                $value = $this->$value($key);

                if (is_integer($key) || ($value === null)) {
                    continue;
                }
            }
            $output[$key] = $value;
        }

        return $output;
    }

    /**
     *
     */
    public function checkMonitors()
    {
        // Check job monitors
        $this->util->getMonitor()->checkMonitors();
    }

    /**
     * Returns a login form
     *
     * @param boolean $showToken Optional, show 'Ask token' button, $this->showTokenButton is used when not specified
     * @param boolean $showPasswordLost Optional, show 'Lost password' button, $this->showPasswordLostButton is used when not specified
     * @return \Gems_User_Form_LoginForm
     */
    protected function createLoginForm($showToken = null, $showPasswordLost = null)
    {
        $args = \MUtil_Ra::args(func_get_args(),
                array(
                    'showToken' => 'is_boolean',
                    'showPasswordLost' => 'is_boolean',
                    ),
                array(
                    'showToken' => $this->showTokenButton,
                    'showPasswordLost' => $this->showPasswordLostButton,
                    'labelWidthFactor' => $this->labelWidthFactor,
                    'organizationMaxLines' => $this->organizationMaxLines,
                    ));

        \Gems_Html::init();

        if ($this->layeredLogin === true) {
            // Allow to set labels without modifying the form by overriding the below methods
            $args['topOrganizationDescription']   = $this->getTopOrganizationDescription();
            $args['childOrganizationDescription'] = $this->getChildOrganizationDescription();

            return $this->loader->getUserLoader()->getLayeredLoginForm($args);

        } else {
            return $this->loader->getUserLoader()->getLoginForm($args);
        }
    }

    /**
     * Gets a reset password form.
     *
     * @return \Gems_User_Form_ResetForm
     */
    protected function createResetRequestForm()
    {
        $args = \MUtil_Ra::args(func_get_args(),
                array(),
                array(
                    'labelWidthFactor' => $this->labelWidthFactor,
                    ));

        $this->initHtml();

        return $this->loader->getUserLoader()->getResetRequestForm($args);
    }

    /**
     * Function for overruling the display of the login form.
     *
     * @param \Gems_User_Form_LoginForm $form
     * @deprecated since version 1.8.4 no longer in use with 2FA login
     */
    protected function displayLoginForm(\Gems_User_Form_LoginForm $form)
    {
        $this->setCurrentOrganizationTo($form->getUser());

        $this->view->form = $form;
    }

    /**
     * Function for overruling the display of the reset form.
     *
     * @param \Gems_Form_AutoLoadFormAbstract $form Rset password or reset request form
     * @param mixed $errors
     */
    protected function displayResetForm(\Gems_Form_AutoLoadFormAbstract $form, $errors)
    {
        if ($form instanceof \Gems_User_Validate_GetUserInterface) {
            $user = $form->getUser();
        }

        if ($form instanceof \Gems_User_Form_ResetRequestForm) {
            $this->html->h3($this->_('Request password reset'));

            $p = $this->html->pInfo();
            if ($form->getOrganizationIsVisible()) {
                $p->append($this->_('Please enter your organization and your username or e-mail address. '));
            } else {
                $p->append($this->_('Please enter your username or e-mail address. '));
            }
            $this->html->p($this->_('We will then send you an e-mail with a link. The link will bring you to a page where you can set a new password of your choice.'));

        } elseif ($form instanceof \Gems_User_Form_ChangePasswordForm) {

            $this->setCurrentOrganizationTo($user);
            if ($user->hasPassword()) {
                $this->html->h3($this->_('Execute password reset'));
                $p = $this->html->pInfo($this->_('We received your password reset request.'));
            } else {
                // New user
                $this->html->h3(sprintf($this->_('Welcome to %s'), $this->project->getName()));
                $p = $this->html->pInfo($this->_('Welcome to this website.'));
            }
            $p->append(' ');
            $p->append($this->_('Please enter your password of choice twice.'));
        }

        if ($errors) {
            $this->addMessage($errors);
        }

        if (isset($user)) {
            $this->setCurrentOrganizationTo($user);
        }

        $formContainer = \MUtil_Html::create('div', array('class' => 'resetPassword'), $form);
        $this->html->append($formContainer);
    }

    /**
     * Modify this to set a new title for the child organization element
     * if you use layered login
     *
     * @return string
     */
    public function getChildOrganizationDescription()
    {
        return $this->translate->_('Department');
    }

    /**
     *
     * @return \Gems\User\LoginStatusTracker
     */
    public function getLoginStatusTracker()
    {
        return $this->loader->getUserLoader()->getLoginStatusTracker();
    }

    /**
     * Modify this to set a new title for the top organization element
     * if you use layered login
     *
     * @return string
     */
    public function getTopOrganizationDescription()
    {
        return $this->translate->_('Organization');
    }

    /**
     * Dummy: always rerouted by \GemsEscort
     */
    public function indexAction() { }

    /**
     * Default login page
     */
    public function loginAction()
    {
        if ($this->loginSnippets && $this->useHtmlView) {
            $params = $this->_processParameters($this->loginParameters + $this->_loginDefaultParameters);

            $sparams['request']           = $this->getRequest();
            $sparams['resetParam']        = $params['resetParam'];
            $sparams['snippetList']       = $this->loginSnippets;
            $sparams['snippetLoader']     = $this->getSnippetLoader();
            $sparams['snippetParameters'] = $params;

            $this->addSnippets('SequenceSnippet', $sparams);

            return;
        }

        $request = $this->getRequest();
        $form    = $this->createLoginForm();

        // Retrieve these before the session is reset
        $staticSession = \GemsEscort::getInstance()->getStaticSession();
        $previousRequestParameters = $staticSession->previousRequestParameters;
        $previousRequestMode = $staticSession->previousRequestMode;

        if ($form->wasSubmitted()) {
            if ($form->isValid($request->getPost(), false)) {
                $user = $form->getUser();
                $user->setAsCurrentUser();

                if ($messages = $user->reportPasswordWeakness($request->getParam($form->passwordFieldName))) {
                    $user->setPasswordResetRequired(true);
                    $this->addMessage($this->_('Your password must be changed.'));
                    foreach ($messages as &$message) {
                        $message = ucfirst($message) . '.';
                    }
                    $this->addMessage($messages);
                }

                /**
                 * Fix current locale in cookies
                 */
                \Gems_Cookies::setLocale($user->getLocale(), $this->basepath->getBasePath());

                /**
                 * Ready
                 */
                $this->addMessage(sprintf($this->_('Login successful, welcome %s.'), $user->getFullName()), 'success');

                /**
                 * Log the login
                 */
                $this->accesslog->logChange($request);

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
            } else {
                $errors = \MUtil_Ra::flatten($form->getMessages());
                // \MUtil_Echo::track($errors);

                //Also log the error to the log table
                //when the project has logging enabled
                $logErrors = join(' - ', $errors);
                $msg = sprintf('Failed login for : %s (%s) - %s', $request->getParam($form->usernameFieldName), $request->getParam($form->organizationFieldName), $logErrors);
                $this->accesslog->logChange($request, $msg);
            } // */
        } else {
            if ($request->isPost()) {
                $form->populate($request->getPost());
            }
        }

        // Check job monitors
        $this->util->getMonitor()->checkMonitors();
        $this->displayLoginForm($form);
    }

    /**
     * Default logoff action
     */
    public function logoffAction()
    {
        $this->addMessage(sprintf($this->_('Good bye: %s.'), $this->currentUser->getFullName()));
        $this->accesslog->logChange($this->getRequest());
        $this->currentUser->unsetAsCurrentUser();
        \Zend_Session::destroy();
        $this->_reroute(array('action' => 'index'), true);
    }

    /**
     * Reset password page.
     */
    public function resetpasswordAction()
    {
        $errors  = array();
        $form    = $this->createResetRequestForm();
        $request = $this->getRequest();

        if ($key = $this->_getParam('key')) {
            $user = $this->loader->getUserLoader()->getUserByResetKey($key);

            if ($user->hasValidResetKey()) {
                // Signal the reqset required so the maxage check is disabled while validating
                $user->setPasswordResetRequired(true);

                $form = $user->getChangePasswordForm(array('askOld' => false, 'askCheck' => true, 'labelWidthFactor' => $this->labelWidthFactor));

                $result = $user->authenticate(null, false);
                if (! $result->isValid()) {
                    $this->addMessage($result->getMessages());
                    $this->addMessage($this->_('For that reason you cannot reset your password.'));
                    return;
                }

                if (! $request->isPost()) {
                    $this->accesslog->logChange($request, sprintf("User %s opened valid reset link.", $user->getLoginName()));
                }
            } else {
                if (! $request->isPost()) {
                    if ($user->getLoginName()) {
                        $message = sprintf("User %s used old reset key.", $user->getLoginName());
                    } else {
                        $message = sprintf("Someone used a non existent reset key.", $user->getLoginName());
                    }
                    $this->accesslog->logChange($request, $message);

                    if ($user->hasPassword() || (! $user->isActive())) {
                        $errors[] = $this->_('Your password reset request is no longer valid, please request a new link.');
                    } else {
                        $errors[] = $this->_('Your password input request is no longer valid, please request a new link.');
                    }
                }

                if ($user->isActive()) {
                    $form->getUserNameElement()->setValue($user->getLoginName());
                    $form->getOrganizationElement()->setValue($user->getBaseOrganizationId());
                }
            }
        }

        if ($request->isPost() && $form->isValid($request->getPost(), false)) {

            if ($form instanceof \Gems_User_Form_ResetRequestForm) {
                $user = $form->getUser();

                $validator = new \Gems_User_Validate_ResetRequestValidator($form, $this->translate);
                $validUser = $validator->isValid(null, $request->getPost());
                $errors = null;

                if ($validUser) {
                    $result = $user->authenticate(null, false);

                    if (!$result->isValid()) {
                        $this->addMessage($result->getMessages());
                        $this->addMessage($this->_('For that reason you cannot request a password reset.'));
                        return;
                    }

                    $errors = $this->sendUserResetEMail($user);
                    if ($errors) {
                        $this->accesslog->logChange(
                            $request,
                            sprintf(
                                "User %s requested reset password but got %d error(s). %s",
                                $form->getUserNameElement()->getValue(),
                                count($errors),
                                implode(' ', $errors)
                            )
                        );
                    }
                }

                if (!$errors || $validUser === false) {
                    // Everything went OK! Or the user isn't valid but we do not want you to know;
                    $this->addMessage($this->_(
                        'If the entered username or e-mail is valid, we have sent you an e-mail with a reset link. Click on the link in the e-mail.'
                    ));

                    if ($validUser) {
                        $this->accesslog->logChange($request);
                    }

                    if ($this->returnToLoginAfterReset) {
                        $this->setCurrentOrganizationTo($user);
                        $this->currentUser->gotoStartPage($this->menu, $request);
                    }
                }

            } elseif ($form instanceof \Gems_User_Form_ChangePasswordForm) {
                $this->addMessage($this->_('New password is active.'));

                // User set before this form was initiated
                $user->setAsCurrentUser();

                /**
                 * Log the login
                 */
                $this->accesslog->logChange($request, $this->_("User logged in through reset password."));
                $user->gotoStartPage($this->menu, $this->getRequest());
                return;
            }

        }
        $form->populate($request->getParams());

        $this->displayResetForm($form, $errors);
    }

    /**
     * Send the user an e-mail with a link for password reset
     *
     * @param \Gems_User_User $user
     * @return mixed string or array of Errors or null when successful.
     */
    public function sendUserResetEMail(\Gems_User_User $user)
    {
        $subjectTemplate = $this->_('Password reset requested');

        // Multi line strings did not come through correctly in poEdit
        $bbBodyTemplate = $this->_("Dear {greeting},\n\n\nA new password was requested for your [b]{organization}[/b] account on the [b]{project}[/b] site, please click within {reset_in_hours} hours on [url={reset_url}]this link[/url] to enter the password of your choice.\n\n\n{organization_signature}\n\n[url={reset_url}]{reset_url}[/url]\n"); // */
        //$bbBodyTemplate  = $this->_("To set a new password for the [b]{organization}[/b] site [b]{project}[/b], please click on this link:\n{reset_url}");

        return $user->sendMail($subjectTemplate, $bbBodyTemplate, true);
    }

    /**
     * Helper function to safely switch org during login
     *
     * @param \Gems_User_User $user
     */
    protected function setCurrentOrganizationTo(\Gems_User_User $user)
    {
        if ($this->currentUser !== $user) {
            $this->currentUser->setCurrentOrganization($user->getCurrentOrganization());
        }
    }
}
