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
        \MUtil_Echo::track($this->loginStatusTracker->isPasswordResetActive());
        if (! $this->loginStatusTracker->isAuthenticated()) {
            return false;
        }

        $this->user = $this->loginStatusTracker->getUser();

        if (! $this->user->canSetPassword()) {
            return false;
        }

        \MUtil_Echo::track($this->loginStatusTracker->isPasswordResetActive());
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
        \MUtil_Echo::track($this->loginStatusTracker->isPasswordResetActive());
        $this->user->setPasswordResetRequired($this->loginStatusTracker->isPasswordResetActive());

        if ($this->user->isPasswordResetRequired()) {
            return parent::hasHtmlOutput();
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
