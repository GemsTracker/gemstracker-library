<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Login;

use Gems\Snippets\FormSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.3 Jun 28, 2018 11:07:37 AM
 */
class UserLoginFormSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    protected $accesslog;

    /**
     *
     * @var \Gems_Util_BasePath
     */
    protected $basepath;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_User_Form_LoginForm
     */
    protected $loginForm;

    /**
     *
     * @var \Gems\User\LoginStatusTracker
     */
    protected $loginStatusTracker;

    /**
     * The form Id used for the save button
     *
     * If empty save button is not added
     *
     * @var string
     */
    protected $saveButtonId = false;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        // Done at form creation
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        return $this->loginForm;
    }

    /**
     * Step by step form processing
     *
     * Returns false when $this->afterSaveRouteUrl is set during the
     * processing, which happens by default when the data is saved.
     *
     * @return boolean True when the form should be displayed
     */
    protected function processForm()
    {
        // Check job monitors as long as the login form is being processed
        $this->util->getMonitor()->checkMonitors();

        // Start the real work
        $this->loadForm();

        $orgId = null;
        if ($this->request->isPost()) {
            $orgId = $this->loginForm->getActiveOrganizationId();

            if ($orgId && ($this->currentUser->getCurrentOrganizationId() != $orgId)) {
                $this->currentUser->setCurrentOrganization($orgId);
            }
        }

        if ($this->loginForm->wasSubmitted()) {
            $user = $this->loginForm->getUser();

            if ($this->loginForm->isValid($this->request->getPost(), false)) {

                /**
                 * Set current locale in cookies
                 */
                \Gems_Cookies::setLocale($user->getLocale(), $this->basepath->getBasePath());

                $this->loginStatusTracker
                        ->setPasswordText($this->loginForm->getPasswordText())
                        ->setUser($user);

                return false;
            }

            $errors = \MUtil_Ra::flatten($this->loginForm->getMessages());
            // \MUtil_Echo::track($errors);

            // Also log the error to the log table  when the project has logging enabled
            $logErrors = join(' - ', $errors);
            $msg = sprintf(
                    'Failed login for : %s (%s) - %s',
                    $user->getLoginName(),
                    $user->getCurrentOrganizationId(),
                    $logErrors
                    );
            $this->accesslog->logChange($this->request, $msg);
        }

        return true;
    }
}
