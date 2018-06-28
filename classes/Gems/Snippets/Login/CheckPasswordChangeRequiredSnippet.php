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

use Gems\Snippets\User\PasswordResetSnippet;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.3 Jun 28, 2018 2:08:53 PM
 */
class CheckPasswordChangeRequiredSnippet extends PasswordResetSnippet
{
    /**
     * Should the old password be requested.
     *
     * @var boolean Not set when null
     */
    protected $askOld = true;

    /**
     * Should the password rules be enforced.
     *
     * @var boolean Not set when null
     */
    protected $forceRules = true;

    /**
     *
     * @var \Gems\User\LoginStatusTracker
     */
    protected $loginStatusTracker;


    /**
     * Should the password rules be reported.
     *
     * @var boolean Not set when null
     */
    protected $reportRules = true;

    /**
     * Use the default form table layout
     *
     * @var boolean
     */
    protected $useTableLayout = false;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->routeController = $this->request->getControllerName();
        $this->routeAction     = $this->request->getActionName();
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! $this->loginStatusTracker->isAuthenticated()) {
            return false;
        }

        $this->user = $this->loginStatusTracker->getUser();
        if (! $this->user->canSetPassword()) {
            return false;
        }

        if (! $this->loginStatusTracker->isPasswordResetActive()) {
            $messages = $this->user->reportPasswordWeakness($this->loginStatusTracker->getPasswordText());
            if ($messages) {
                $this->addMessage($this->_('Your password must be changed.'));
                foreach ($messages as &$message) {
                    $message = ucfirst($message) . '.';
                }
                $this->addMessage($messages);

                $this->loginStatusTracker->setPasswordResetActive(true);
            }
        }

        if ($this->loginStatusTracker->isPasswordResetActive()) {
            // Skip parent::hasHtmlOutput()
            // will trigger an error loop
            return $this->processForm();
        }

        return false;
    }
    /**
     * When there is a redirectRoute this function will execute it.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see \Zend_Controller_Action_Helper_Redirector
     */
    public function redirectRoute()
    {
        // Not used
        return null;
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData()
    {
        parent::saveData();

        $this->user->setPasswordResetRequired(false);
        $this->loginStatusTracker->setPasswordResetActive(false);
    }
}
