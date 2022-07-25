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

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.3 Jun 28, 2018 12:18:30 PM
 */
class SetAsCurrentUserSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Gems\AccessLog
     */
    protected $accesslog;

    /**
     *
     * @var \Gems\User\LoginStatusTracker
     */
    protected $loginStatusTracker;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        $user = $this->loginStatusTracker->getUser();
        if ($user) {
            $user->setAsCurrentUser();

            /**
             * Tell the user
             */
            $this->addMessage(sprintf($this->_('Login successful, welcome %s.'), $user->getFullName()));

            /**
             * Log the login
             */
            $this->accesslog->logChange($this->request);
        }

        return false;
    }
}
