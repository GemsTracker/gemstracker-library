<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_AskAction extends \Gems_Controller_Action
{
    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * Usually a child of \Gems_Tracker_Snippets_ShowTokenLoopAbstract,
     * Ask_ShowAllOpenSnippet or Ask_ShowFirstOpenSnippet or
     * a project specific one.
     *
     * @var array Or string of snippet names, presumably \Gems_Tracker_Snippets_ShowTokenLoopAbstract snippets
     */
    // protected $forwardSnippets = 'Ask\\ShowAllOpenSnippet';
    // protected $forwardSnippets = 'Ask\\RedirectUntilGoodbyeSnippet';
    protected $forwardSnippets = 'Ask\\ShowFirstOpenSnippet';

    /**
     * The width factor for the label elements.
     *
     * Width = (max(characters in labels) * labelWidthFactor) . 'em'
     *
     * @var float
     */
    protected $labelWidthFactor = 0.8;

    /**
     *
     * @var \Zend_Locale
     */
    public $locale;

    /**
     * The parameters used for the lost action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $lostParameters = [];

    /**
     * The snippets used for the lost action.
     *
     * @var mixed String or array of snippets name
     */
    protected $lostSnippets = 'Token\\TokenForgottenSnippet';

    /**
     * Snippets displayed when maintenance mode is on
     *
     * @var array
     */
    protected $maintenanceModeSnippets = ['Ask\\MaintenanceModeAskSnippet'];

    /**
     * The current token ID
     *
     * set by _initToken()
     *
     * @var \Gems_Tracker
     */
    protected $tokenId;

    /**
     * The current token
     *
     * set by _initToken()
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * The tracker
     *
     * set by _initToken()
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

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
     * Leave on top, so we won't miss this
     */
    public function init()
    {
        parent::init();
        
        /**
         * If not in the index action, add the following to the head section
         *      <meta name="robots" content="noindex">
         */
        $action = $this->getRequest()->getActionName();
        if ($action !== 'index') {
            $view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
            $this->view->getHelper('headMeta')->appendName('robots', 'noindex, nofollow');
        }
    }

    /**
     * Common handler utility to initialize tokens from parameters
     *
     * @return boolean True if there is a real token specified in the request
     */
    protected function _initToken()
    {
        if ($this->tracker) {
            return $this->token && $this->token->exists;
        }

        $this->tracker = $this->loader->getTracker();
        $this->tokenId = $this->tracker->filterToken($this->_getParam(\MUtil_Model::REQUEST_ID));
        // Now check if the token is valid
        $validator = $this->tracker->getTokenValidator();

        if (! $this->tokenId || $validator->isValid($this->tokenId) === false) {
            return false;
        }

        $this->token = $this->tracker->getToken($this->tokenId);

        if (! $this->token->exists) {
            return false;
        }

        if (! ($this->currentUser->isActive() || $this->token->getSurvey()->isTakenByStaff())) {
            $tokenLang = strtolower($this->token->getRespondentLanguage());
            // \MUtil_Echo::track($tokenLang, $this->locale->getLanguage());
            if ($tokenLang != $this->locale->getLanguage()) {
                $this->currentUser->switchLocale($tokenLang);
            }

            $currentOrg = $this->loader->getOrganization();
            $tokenOrgId = $this->token->getOrganizationId();

            if ($tokenOrgId != $currentOrg->getId()) {
                $this->loader->getOrganization($tokenOrgId)
                        ->setAsCurrentOrganization();
            }
        }

        return true;
    }

    /**
     * Function for overruling the display of the login form.
     *
     * @param \Gems_Tracker_Form_AskTokenForm $form
     */
    protected function displayTokenForm(\Gems_Tracker_Form_AskTokenForm $form)
    {
        $form->setDescription(sprintf($this->_('Enter your %s token'), $this->project->name));
        $this->html->h3($form->getDescription());
        $this->html[] = $form;
        $this->html->pInfo($this->_('Tokens identify a survey that was assigned to you personally.') . ' ' . $this->_('Entering the token and pressing OK will open that survey.'));

        if ($this->currentUser->isActive()) {
            if ($this->currentUser->isLogoutOnSurvey()) {
                $this->html->pInfo($this->_('After answering the survey you will be logged off automatically.'));
            }
        }

        $this->html->pInfo(
            $this->_('A token consists of two groups of four letters and numbers, separated by an optional hyphen. Tokens are case insensitive.'), ' ',
            $this->_('The number zero and the letter O are treated as the same; the same goes for the number one and the letter L.')
            );

        $p = $this->html->p();
        $lostItem = $this->menu->findAllowedController('ask', 'lost');
        if ($lostItem) {
            $p->append($lostItem->toActionLink($this->request), ' ');
        }
    }

    /**
     * Show the user a screen with token information and a button to take at least one survey
     *
     * @return void
     */
    public function forwardAction()
    {
        /**************
         * Find token *
         **************/

        if (! $this->_initToken()) {
            if ($this->tokenId) {
                // There is a token but is incorrect
                $this->addMessage(sprintf(
                        $this->_('The token %s does not exist (any more).'),
                        strtoupper($this->tokenId)
                        ));
            }
            $this->_forward('index');
            return;
        }

        if ($this->util->getMaintenanceLock()->isLocked()) {
            $this->addSnippet($this->maintenanceModeSnippets, ['token' => $this->token]);
            return;
        }

        /****************************
         * Update open tokens first *
         ****************************/
        $this->tracker->processCompletedTokens(
                $this->token->getRespondentId(),
                $this->token->getChangedBy(),
                $this->token->getOrganizationId()
                );

        $screen = $this->token->getOrganization()->getTokenAskScreen();
        if ($screen) {
            $params   = $screen->getParameters($this->token);
            $snippets = $screen->getSnippets($this->token);
            if (false !== $snippets) {
                $this->forwardSnippets = $snippets;
            }
        }
        $params['token'] = $this->token;

        // Display token when possible
        if ($this->html->snippet($this->forwardSnippets, $params)) {
            return;
        }

        // Snippet had nothing to display, because of an answer
        if ($this->getRequest()->getActionName() == 'return') {
            $this->addMessage(sprintf(
                    $this->_('Thank you for answering. At the moment we have no further surveys for you to take.'),
                    strtoupper($this->tokenId)
                    ));
        } else {
            $this->addMessage(sprintf(
                    $this->_('The survey for token %s has been answered and no further surveys are open.'),
                    strtoupper($this->tokenId)
                    ));
        }

        // Do not enter a loop!! Reroute!
        $this->_reroute(array('controller' => 'ask', 'action' => 'index'), true);
    }

    /**
     * Ask the user for a token
     *
     * @return void
     */
    public function indexAction()
    {
        if ($this->util->getMaintenanceLock()->isLocked()) {
            $this->addSnippet($this->maintenanceModeSnippets);
            return;
        }

        // Make sure to return to the forward screen
        $this->currentUser->setSurveyReturn();

        $request = $this->getRequest();
        $tracker = $this->loader->getTracker();
        $form    = $tracker->getAskTokenForm(array(
            'displayOrder' => array('element', 'description', 'errors'),
            'labelWidthFactor' => 0.8
            ));

        if ($request->isPost() && $form->isValid($request->getParams())) {
            $this->_forward('forward');
            return;
        }

        $form->populate($request->getParams());
        $this->displayTokenForm($form);
    }

    /**
     * Show lost token screen for respondents
     */
    public function lostAction()
    {
        $this->addSnippet($this->lostSnippets, $this->lostParameters);
    }

    /**
     * The action where survey sources should return to after survey completion
     */
    public function returnAction()
    {
        if (! $this->_initToken()) {
            // In all other cases: the action that generates meaningfull warnings and is reachable for everyone
            $this->_forward('forward');
            return;
        }

        if ($url = $this->token->getReturnUrl()) {
            // Check for completed tokens
            $this->tracker->processCompletedTokens(
                    $this->token->getRespondentId(),
                    $this->token->getChangedBy(),
                    $this->token->getOrganizationId()
                    );

            // Redirect at once, might be another site url
            header('Location: ' . $url);
            exit();
        }

        // No return? Check for old style user based return
        if (! $this->currentUser->isActive()) {
            $this->forward('forward');
            return;
        }

        // Check for completed tokens
        $this->tracker->processCompletedTokens($this->token->getRespondentId(), $this->currentUser->getUserId());

        // Get return route parameters
        $parameters = $this->currentUser->getSurveyReturn();
        if (! $parameters) {
            // Default fallback for the fallback
            $request = $this->getRequest();
            $parameters[$request->getControllerKey()] = 'respondent';
            $parameters[$request->getActionKey()]     = 'show';
            $parameters[\MUtil_Model::REQUEST_ID]      = $this->token->getPatientNumber();
        }

        $this->_reroute($parameters, true);
    }

    /**
     * Duplicate of to-survey to enable separate rights
     */
    public function takeAction()
    {
        $this->forward('to-survey');
    }

    /**
     * Old action mentioned on some documentation
     */
    public function tokenAction()
    {
        $this->forward('index');
    }

    /**
     * Go directly to url
     */
    public function toSurveyAction()
    {
        if (! $this->_initToken()) {
            // Default option
            $this->forward('index');
            return;
        }

        $language = $this->locale->getLanguage();

        try {
            $url  = $this->token->getUrl(
                    $language,
                    $this->currentUser->getUserId() ? $this->currentUser->getUserId() : $this->token->getRespondentId()
                    );

            /************************
             * Optional user logout *
             ************************/
            if ($this->currentUser->isLogoutOnSurvey()) {
                $this->currentUser->unsetAsCurrentUser();
            }

            // Redirect at once
            header('Location: ' . $url);
            exit();

        } catch (\Gems_Tracker_Source_SurveyNotFoundException $e) {
            $this->addMessage(sprintf(
                    $this->_('The survey for token %s is no longer active.'),
                    strtoupper($this->tokenId)
                    ));

            // Default option
            $this->forward('index');
        }
    }
}
