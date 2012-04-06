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
     *
     * @var array Or string of snippet names, presumably Gems_Tracker_Snippets_ShowTokenLoopAbstract snippets
     */
    protected $forwardSnippets = 'Track_Token_ShowFirstOpenSnippet';

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
     * Show the user a screen with token information and a button to take at least one survey
     *
     * @return void
     */
    public function forwardAction()
    {
        $tracker  = $this->loader->getTracker();

        /**************
         * Find token *
         **************/

        if ($tokenId = $this->_getParam(MUtil_Model::REQUEST_ID)) {
            $tokenId = $tracker->filterToken($tokenId);

            if ($token = $tracker->getToken($tokenId)) {

                /****************************
                 * Update open tokens first *
                 ****************************/
                $respId = $token->getRespondentId();
                $tracker->processCompletedTokens($respId, $respId);

                // Display token when possible
                if ($this->html->snippet($this->forwardSnippets, 'token', $token)) {
                    return;
                }

                // Snippet had nothing to display, because of an answer
                if ($this->getRequest()->getActionName() == 'return') {
                    $this->addMessage(sprintf($this->_('Thank you for answering. At the moment we have no further surveys for you to take.'), $tokenId));
                } else {
                    $this->addMessage(sprintf($this->_('The survey for token %s has been answered and no further surveys are open.'), $tokenId));
                }

                // Do not enter a loop!! Reroute!
                $this->_reroute(array('controller' => 'ask', 'action' => 'index'), true);

            } else {
                $this->addMessage(sprintf($this->_('The token %s does not exist (any more).'), $tokenId));
            }
        }

        $this->_forward('token');
    }

    /**
     * Ask the user for a token
     *
     * @return void
     */
    public function indexAction()
    {
        // Make sure to return to ask screen
        $this->loader->getCurrentUser()->setSurveyReturn($this->getRequest());

        $tracker    = $this->loader->getTracker();
        $max_length = $tracker->getTokenLibrary()->getLength();

        $form = new Gems_Form(array('displayOrder' => array('element', 'description', 'errors'), 'labelWidthFactor' => 0.8));
        $form->setMethod('post');
        $form->setDescription(sprintf($this->_('Enter your %s token'), $this->project->name));

        // Veld token
        $element = new Zend_Form_Element_Text(MUtil_Model::REQUEST_ID);
        $element->setLabel($this->_('Token'));
        $element->setDescription(sprintf($this->_('Enter tokens as %s.'), $tracker->getTokenLibrary()->getFormat()));
        $element->setAttrib('size', $max_length);
        $element->setAttrib('maxlength', $max_length);
        $element->setRequired(true);
        $element->addFilter($tracker->getTokenFilter());
        $element->addValidator($tracker->getTokenValidator());
        $form->addElement($element);

        // Submit knop
        $element = new Zend_Form_Element_Submit('button');
        $element->setLabel($this->_('OK'));
        $element->setAttrib('class', 'button');
        $form->addElement($element);

        if ($this->_request->isPost()) {
            $throttleSettings = $this->project->getAskThrottleSettings();

            // Prune the database for (very) old attempts
            $this->db->query("DELETE FROM gems__token_attempts WHERE gta_datetime < DATE_SUB(NOW(), INTERVAL ? second)",
                $throttleSettings['period'] * 20);

            // Retrieve the number of failed attempts that occurred within the specified window
            $attemptData = $this->db->fetchRow("SELECT COUNT(1) AS attempts, UNIX_TIMESTAMP(MAX(gta_datetime)) AS last " .
                "FROM gems__token_attempts WHERE gta_datetime > DATE_SUB(NOW(), INTERVAL ? second)", $throttleSettings['period']);

            $remainingDelay = ($attemptData['last'] + $throttleSettings['delay']) - time();

            if ($attemptData['attempts'] > $throttleSettings['threshold'] && $remainingDelay > 0) {
                $this->escort->logger->log("Possible token brute force attack, throttling for $remainingDelay seconds", Zend_Log::ERR);

                $this->addMessage($this->_('The server is currently busy, please wait a while and try again.'));
            } else if ($form->isValid($_POST)) {
                $this->_forward('forward');
                return;
            } else {
                if (isset($_POST[MUtil_Model::REQUEST_ID])) {
                    $this->db->insert(
                    	'gems__token_attempts',
                        array(
                        	'gta_id_token' => substr($_POST[MUtil_Model::REQUEST_ID], 0, $max_length),
                        	'gta_ip_address' => $this->getRequest()->getClientIp()
                        )
                    );
                }
            }
        } elseif ($id = $this->_getParam(MUtil_Model::REQUEST_ID)) {
            $form->populate(array(MUtil_Model::REQUEST_ID => $id));
        }

        $this->html->h3($form->getDescription());
        $this->html[] = $form;
        $this->html->pInfo($this->_('Tokens identify a survey that was assigned to you personally.') . ' ' . $this->_('Entering the token and pressing OK will open that survey.'));

        if (isset($this->session->user_id)) {
            if ($this->session->user_logout) {
                $this->html->pInfo($this->_('After answering the survey you will be logged off automatically.'));
            } else {
                $this->html->pInfo($this->_('After answering the survey you will return to the respondent overview screen.'));
            }
        // } else {
        //    $this->html->pInfo($this->_('After answering the survey you will return here.'));
        }

        $this->html->pInfo(
            $this->_('A token consists of two groups of four letters and numbers, separated by an optional hyphen. Tokens are case insensitive.'), ' ',
            $this->_('The number zero and the letter O are treated as the same; the same goes for the number one and the letter L.')
            );
    }

    public function returnAction()
    {
        $user = $this->loader->getCurrentUser();

        if ($user->isActive()) {
            $tracker = $this->loader->getTracker();
            $token   = $tracker->getToken($tracker->filterToken($this->_getParam(MUtil_Model::REQUEST_ID)));

            // Check for completed tokens
            $this->loader->getTracker()->processCompletedTokens($token->getRespondentId(), $user->getUserId());

            $parameters = $user->getSurveyReturn();
            if (! $parameters) {
                // Default
                $request = $this->getRequest();
                $parameters[$request->getControllerKey()] = 'respondent';
                $parameters[$request->getActionKey()]     = 'show';
                $parameters[MUtil_Model::REQUEST_ID]      = $token->getPatientNumber();
            }

            $this->_reroute($parameters, true);
        } else {
            $this->_forward('forward');
        }
    }

    public function takeAction()
    {
        // Dummy to enable separate rights
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
        $tracker = $this->loader->getTracker();
        if ($tokenId = $this->_getParam(MUtil_Model::REQUEST_ID)) {
            $tokenId = $tracker->filterToken($tokenId);

            if ($token = $tracker->getToken($tokenId)) {
                $language = $this->locale->getLanguage();
                $user     = $this->loader->getCurrentUser();

                try {
                    $url  = $token->getUrl($language, $user->getUserId() ? $user->getUserId() : $token->getRespondentId());

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
                    $this->addMessage(sprintf($this->_('The survey for token %s is no longer active.'), $tokenId));
                }
            }
        }

        // Default option
        $this->_forward('index');
    }
    public function routeError($message)
    {
        // TODO make nice
        throw new exception($message);
    }
}

