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
 * @since      Class available since version 1.8.3 Jun 28, 2018 1:55:19 PM
 */
class RedirectToRequestSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var array
     */
    private $_redirectUrl = null;

    /**
     *
     * @var \Gems\User\LoginStatusTracker
     */
    protected $loginStatusTracker;

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Variable to either keep or throw away the request data
     * not specified in the route.
     *
     * @var boolean True then the route is reset
     */
    public $resetRoute = true;

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
        return false;
    }

    /**
     * When hasHtmlOutput() is false a snippet code user should check
     * for a redirectRoute. Otherwise the redirect calling render() will
     * execute the redirect.
     *
     * This function should never return a value when the snippet does
     * not redirect.
     *
     * Also when hasHtmlOutput() is true this function should not be
     * called.
     *
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        if (null !== $this->_redirectUrl) {
            return $this->_redirectUrl;
        }

        $this->_redirectUrl = false;

        // Retrieve these before the session is reset
        $staticSession = \GemsEscort::getInstance()->getStaticSession();

        if ($staticSession && is_array($staticSession->previousRequestParameters)) {
            $url = $staticSession->previousRequestParameters;

            $this->loginStatusTracker->destroySession();

            $this->_redirectUrl = $url;
        }

        return $this->_redirectUrl;
    }
}
