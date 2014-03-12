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
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_AskAction extends Gems_Controller_Action
{
    /**
     * Usually a child of Gems_Tracker_Snippets_ShowTokenLoopAbstract,
     * Track_Token_ShowAllOpenSnippet or Track_Token_ShowFirstOpenSnippet or
     * a project specific one.
     *
     * @var array Or string of snippet names, presumably Gems_Tracker_Snippets_ShowTokenLoopAbstract snippets
     */
    // protected $forwardSnippets = 'Track_Token_ShowAllOpenSnippet';
    protected $forwardSnippets = 'Track_Token_ShowFirstOpenSnippet';

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
     * @var Zend_Locale
     */
    public $locale;

    /**
     * The current token ID
     *
     * set by _initToken()
     *
     * @var Gems_Tracker
     */
    protected $tokenId;

    /**
     * The current token
     *
     * set by _initToken()
     *
     * @var Gems_Tracker_Token
     */
    protected $token;

    /**
     * The tracker
     *
     * set by _initToken()
     *
     * @var Gems_Tracker
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
        $this->tokenId = $this->tracker->filterToken($this->_getParam(MUtil_Model::REQUEST_ID));

        if (! $this->tokenId) {
            return false;
        }

        $this->token = $this->tracker->getToken($this->tokenId);

        if (! $this->token->exists) {
            return false;
        }

        if (! ($this->loader->getCurrentUser()->isActive() || $this->token->getSurvey()->isTakenByStaff())) {
            $tokenLang = strtolower($this->token->getRespondentLanguage());
            // MUtil_Echo::track($tokenLang, $this->locale->getLanguage());
            if ($tokenLang != $this->locale->getLanguage()) {
                $this->locale->setLocale($tokenLang);
                $this->translate->getAdapter()->setLocale($this->locale);
                $this->session->user_locale = $tokenLang;
                Gems_Cookies::setLocale($tokenLang, $this->basepath->getBasePath());
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
     * @param Gems_Tracker_Form_AskTokenForm $form
     */
    protected function displayTokenForm(Gems_Tracker_Form_AskTokenForm $form)
    {
        $user = $this->loader->getCurrentUser();

        $form->setDescription(sprintf($this->_('Enter your %s token'), $this->project->name));
        $this->html->h3($form->getDescription());
        $this->html[] = $form;
        $this->html->pInfo($this->_('Tokens identify a survey that was assigned to you personally.') . ' ' . $this->_('Entering the token and pressing OK will open that survey.'));

        if ($user->isActive()) {
            if ($user->isLogoutOnSurvey()) {
                $this->html->pInfo($this->_('After answering the survey you will be logged off automatically.'));
            }
        }

        $this->html->pInfo(
            $this->_('A token consists of two groups of four letters and numbers, separated by an optional hyphen. Tokens are case insensitive.'), ' ',
            $this->_('The number zero and the letter O are treated as the same; the same goes for the number one and the letter L.')
            );
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

        /****************************
         * Update open tokens first *
         ****************************/
        $this->tracker->processCompletedTokens(
                $this->token->getRespondentId(),
                $this->token->getChangedBy(),
                $this->token->getOrganizationId()
                );

        // Display token when possible
        if ($this->html->snippet($this->forwardSnippets, 'token', $this->token)) {
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
        // Make sure to return to the forward screen
        $this->loader->getCurrentUser()->setSurveyReturn();

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
        $user = $this->loader->getCurrentUser();

        if (! $user->isActive()) {
            $this->_forward('forward');
            return;
        }

        // Check for completed tokens
        $this->tracker->processCompletedTokens($this->token->getRespondentId(), $user->getUserId());

        // Get return route parameters
        $parameters = $user->getSurveyReturn();
        if (! $parameters) {
            // Default fallback for the fallback
            $request = $this->getRequest();
            $parameters[$request->getControllerKey()] = 'respondent';
            $parameters[$request->getActionKey()]     = 'show';
            $parameters[MUtil_Model::REQUEST_ID]      = $this->token->getPatientNumber();
        }

        $this->_reroute($parameters, true);
    }

    /**
     * Duplicate of to-survey to enable separate rights
     */
    public function takeAction()
    {
        $this->_forward('to-survey');
    }

    /**
     * Old action mentioned on some documentation
     */
    public function tokenAction()
    {
        $this->_forward('index');
    }

    /**
     * Go directly to url
     */
    public function toSurveyAction()
    {
        if (! $this->_initToken()) {
            // Default option
            $this->_forward('index');
            return;
        }

        $language = $this->locale->getLanguage();
        $user     = $this->loader->getCurrentUser();

        try {
            $url  = $this->token->getUrl(
                    $language,
                    $user->getUserId() ? $user->getUserId() : $this->token->getRespondentId()
                    );

            /************************
             * Optional user logout *
             ************************/
            if ($user->isLogoutOnSurvey()) {
                $user->unsetAsCurrentUser();
            }

            // Redirect at once
            header('Location: ' . $url);
            exit();

        } catch (Gems_Tracker_Source_SurveyNotFoundException $e) {
            $this->addMessage(sprintf(
                    $this->_('The survey for token %s is no longer active.'),
                    strtoupper($this->tokenId)
                    ));

            // Default option
            $this->_forward('index');
        }
    }
}
