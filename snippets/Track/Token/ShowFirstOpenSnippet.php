<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage Tracker\Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ShowFirstOpenSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Show a single button for an unanswered survey or nothing.
 *
 * Works using $project->getAskDelay()
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class Track_Token_ShowFirstOpenSnippet extends Gems_Tracker_Snippets_ShowTokenLoopAbstract
{
    /**
     * Required
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Optional, calculated from $token
     *
     * @var Gems_Tracker_Token
     */
    protected $showToken;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if (parent::checkRegistryRequestsAnswers()) {
            return $this->project instanceof Gems_Project_ProjectSettings;
        } else {
            return false;
        }
    }
    
    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $delay = $this->project->getAskDelay($this->request, $this->wasAnswered);
        $href  = $this->getTokenHref($this->showToken);
        $html  = $this->getHtmlSequence();
        $org   = $this->showToken->getOrganization();
        $url   = $href->render($this->view);

        switch ($delay) {
            case 0:
                // Redirect at once
                header('Location: ' . $url);
                exit();

            case -1:
                break;

            default:
                // Let the page load after stated interval
                $this->view->headMeta()->appendHttpEquiv('Refresh', $delay . '; url=' . $url);
        }

        $html->h3($this->_('Token'));
        $html->pInfo(sprintf($this->_('Welcome %s,'), $this->showToken->getRespondentName()));

        if ($this->wasAnswered) {
            $html->pInfo(sprintf($this->_('Thank you for answering the "%s" survey.'), $this->token->getSurveyName()));
            $html->pInfo($this->_('Please click the button below to answer the next survey.'));
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->pInfo()->raw(MUtil_Markup::render($this->_($welcome), 'Bbcode', 'Html'));
            }
            $html->pInfo(sprintf($this->_('Please click the button below to answer the survey for token %s.'), strtoupper($this->showToken->getTokenId())));
        }
        if ($delay > 0) {
            $html->pInfo(sprintf($this->plural(
                'Wait one second to open the survey automatically or click on Cancel to stop.',
                'Wait %d seconds to open the survey automatically or click on Cancel to stop.',
                $delay), $delay));
        }

        $buttonDiv = $html->buttonDiv(array('class' => 'centerAlign'));
        $buttonDiv->actionLink($href, $this->showToken->getSurveyName());

        if ($delay > 0) {
            $buttonDiv->actionLink(array('delay_cancelled' => 1), $this->_('Cancel'));
        }

        if ($next = $this->showToken->getTokenCountUnanswered()) {
            $html->pInfo(sprintf(
            $this->plural(
                'After this survey there is one other survey we would like you to answer.',
                'After this survey there are another %d surveys we would like you to answer.',
                $next), $next));
        }
        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw(MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
        }
        return $html;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->wasAnswered) {
            $this->showToken = $this->token->getNextUnansweredToken();
        } else {
            $this->showToken = $this->token;
        }

        return ($this->showToken instanceof Gems_Tracker_Token) && $this->showToken->exists;
    }
}
