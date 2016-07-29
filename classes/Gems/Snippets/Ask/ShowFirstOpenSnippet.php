<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ShowFirstOpenSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

namespace Gems\Snippets\Ask;

/**
 * Show a single button for an unanswered survey or nothing.
 *
 * Works using $project->getAskDelay()
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class ShowFirstOpenSnippet extends \Gems_Tracker_Snippets_ShowTokenLoopAbstract
{
    /**
     * Required
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Optional, calculated from $token
     *
     * @var \Gems_Tracker_Token
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
            return $this->project instanceof \Gems_Project_ProjectSettings;
        } else {
            return false;
        }
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
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
        if ($this->token->hasRelation()) {
            $p = $html->pInfo(sprintf($this->_('Welcome %s,'), $this->showToken->getRelation()->getName()));    
            
            $html->pInfo(sprintf($this->_('We kindly ask you to answer a survey about %s.'), $this->showToken->getRespondent()->getName()));
        } else {
            $p = $html->pInfo(sprintf($this->_('Welcome %s,'), $this->showToken->getRespondentName()));    
        }

        if ($this->wasAnswered) {
            $html->pInfo(sprintf($this->_('Thank you for answering the "%s" survey.'), $this->token->getSurveyName()));
            $html->pInfo($this->_('Please click the button below to answer the next survey.'));
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->pInfo()->raw(\MUtil_Markup::render($this->_($welcome), 'Bbcode', 'Html'));
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
            $html->pInfo()->raw(\MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
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
     * {@see \MUtil_Registry_TargetInterface}.
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

        return ($this->showToken instanceof \Gems_Tracker_Token) && $this->showToken->exists;
    }
}
