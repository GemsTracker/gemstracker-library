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
    public $useHtmlView = true;

    public function forwardAction()
    {
        $tracker  = $this->loader->getTracker();
        $language = $this->locale->getLanguage();

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

                /***********************
                 * Look for next token *
                 ***********************/
                $wasAnswered = $token->isCompleted();
                if ($wasAnswered) {
                    $token = $token->getNextUnansweredToken();
                }

                if ($token && $token->exists) {
                    $tokenId = $token->getTokenId();

                    try {
                        /***************
                         * Get the url *
                         ***************/
                        $user = $this->loader->getCurrentUser();
                        $url = $token->getUrl($language, $user->getUserId() ? $user->getUserId() : $respId);

                        /************************
                         * Optional user logout *
                         ************************/
                        if ($user->isLogoutOnSurvey()) {
                            $user->unsetAsCurrentUser();
                        }

                        /***********************************
                         * Should we stay or should we go? *
                         ***********************************/
                        if (! $this->_getParam('delay_cancelled')) {
                            $_delay = $this->_getParam('delay');
                            if (null !== $_delay) {
                                $delay = $_delay;

                            } elseif ($wasAnswered) {
                                if (isset($this->project->askNextDelay)) {
                                    $delay = $this->project->askNextDelay;
                                }
                            } else {
                                if (isset($this->project->askDelay)) {
                                    $delay = $this->project->askDelay;
                                }
                            }
                        }
                        if (isset($delay)) {
                            if ($delay == 0) {
                                // Redirect at once
                                header('Location: ' . $url);
                                exit();
                            }

                            // Let the page load after stated interval
                            $this->view->headMeta()->appendHttpEquiv('Refresh', $delay . '; url=' . $url);
                        }

                        $organization = $this->loader->getOrganization($token->getOrganizationId());

                        Gems_Html::init(); // Turn on Gems specific html like pInfo
                        $this->html->h3($this->_('Token'));
                        $this->html->pInfo(sprintf($this->_('Welcome %s,'), $token->getRespondentName()));

                        if ($wasAnswered) {
                            $this->html->pInfo(sprintf($this->_('Thank you for answering the survey for token %s.'), strtoupper($this->_getParam(MUtil_Model::REQUEST_ID))));
                            $this->html->pInfo($this->_('Please click the button below to answer the next survey.'));
                        } else {
                            if ($welcome = $organization->getWelcome()) {
                                $this->html->pInfo()->raw(MUtil_Markup::render($this->_($welcome), 'Bbcode', 'Html'));
                            }
                            $this->html->pInfo(sprintf($this->_('Please click the button below to answer the survey for token %s.'), strtoupper($tokenId)));
                        }
                        if (isset($delay)) {
                            $this->html->pInfo(sprintf($this->plural(
                                'Wait one second to open the survey automatically or click on Cancel to stop.',
                                'Wait %d seconds to open the survey automatically or click on Cancel to stop.',
                                $delay), $delay));
                        }

                        $buttonDiv = $this->html->buttonDiv(array('class' => 'centerAlign'));
                        $buttonDiv->actionLink(MUtil_Html::raw($url), $token->getSurveyName());

                        if (isset($delay)) {
                            $buttonDiv->actionLink(array('delay_cancelled' => 1), $this->_('Cancel'));
                        }

                        if ($next = $token->getTokenCountUnanswered()) {
                            $this->html->pInfo(sprintf(
                            $this->plural(
                                'After this survey there is one other survey we would like you to answer.',
                                'After this survey there are another %d surveys we would like you to answer.',
                                $next), $next));
                        }
                        if ($sig = $organization->getSignature()) {
                            $this->html->pInfo()->raw(MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
                        }
                        return;

                    } catch (Gems_Tracker_Source_SurveyNotFoundException $e) {
                        $this->addMessage(sprintf($this->_('The survey for token %s is no longer active.'), $tokenId));
                    }
                } else {
                    if ($token) {
                        $this->addMessage(sprintf($this->_('The token %s does not exist.'), $this->_getParam(MUtil_Model::REQUEST_ID)));
                    } elseif ($this->_getParam('action') == 'return') {
                        $this->addMessage(sprintf($this->_('Thank you for answering. At the moment we have no further surveys for you to take.'), $tokenId));
                    } else {
                        $this->addMessage(sprintf($this->_('The survey for token %s has been answered and no further surveys are open.'), $tokenId));
                    }
                    // Do not enter a loop!! Reroute!
                    $this->_reroute(array('controller' => 'ask', 'action' => 'token'), true);
                }

            } else {
                $this->addMessage(sprintf($this->_('The token %s does not exist (any more).'), $tokenId));
            }
        }

        $this->_forward('token');
    }

    public function indexAction()
    {
        // Make sure to return to ask screen
        $this->session->return_controller = $this->getRequest()->getControllerName();

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

        Gems_Html::init(); // Turn on Gems specific html like pInfo
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
        if (isset($this->session->user_id) && $this->session->user_id) {
            $tracker = $this->loader->getTracker();
            $token   = $tracker->getToken($tracker->filterToken($this->_getParam(MUtil_Model::REQUEST_ID)));

            // Check for completed tokens
            $this->loader->getTracker()->processCompletedTokens($token->getRespondentId(), $this->session->user_id);

            if (isset($this->session->return_controller) && $this->session->return_controller) {
                $return = $this->session->return_controller;
            } else {
                $return = 'respondent';
            }

            $parameters['controller'] = $return;
            $parameters['action']     = 'show';
            $parameters[MUtil_Model::REQUEST_ID] = $token->getPatientNumber();
            switch ($return) {
                case 'track':
                    $parameters['action'] = 'show-track';
                    $parameters[Gems_Model::RESPONDENT_TRACK] = $token->getRespondentTrackId();
                    break;

                case 'survey':
                    $parameters[MUtil_Model::REQUEST_ID] = $token->getTokenId();
                    break;

                case 'ask':
                    $this->_forward('forward');
                    return;

                default:
                    // Allow open specification of return
                    if (strpos($return, '/') !== false) {
                        $parameters = MUtil_Ra::pairs(explode('/', $return));
                        // MUtil_Echo::track($parameters);
                    } else {
                        $parameters['controller'] = 'respondent';
                    }
            }
                $this->_reroute($parameters, true);
        } else {
            $this->_forward('forward');
        }
    }

    public function takeAction()
    {
        // Dummy to enable separate rights
        $this->_forward('forward');
    }

    public function tokenAction()
    {
        // Staat om sommige documentatie
        $this->_forward('index');
    }

    public function routeError($message)
    {
        // TODO make nice
        throw new exception($message);
    }
}

