<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
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
     * Show this snippet show a thank you screen when there are no more tokens to answer?
     *
     * @var boolean
     */
    public $showEndScreen = true;

    /**
     * Switch for showing how long the token is valid.
     *
     * @var boolean
     */
    protected $showUntil = false;

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
        if ($this->checkContinueLinkClicked()) {
            // Continue later was clicked, handle the click
            return $this->continueClicked();
        }
        if (! $this->showToken) {
            // Last token was answered, return info
            return $this->lastCompleted();
        }

        $delay = $this->project->getAskDelay($this->request, $this->wasAnswered);
        $href  = $this->getTokenHref($this->showToken);
        $url   = $href->render($view);
        $delay = 2;
        switch ($delay) {
            case 0:
                // Redirect at once
                header('Location: ' . $url);
                exit();

            case -1:
                break;

            default:
                // Let the page load after stated interval
                $view->headMeta()->appendHttpEquiv('Refresh', $delay . '; url=' . $url);
        }

        $count = $this->getOtherTokenCountUnanswered($this->showToken);
        $html  = $this->getHtmlSequence();
        $org   = $this->showToken->getOrganization();

        $html->h3($this->getHeaderLabel());

        $html->append($this->formatWelcome());

        if ($this->wasAnswered) {
            $html->pInfo(sprintf(
                             $this->_('Thank you for answering the "%s" survey.'),
                             $this->getSurveyName($this->token)));
            $html->pInfo($this->_('Please click the button below to answer the next survey.'));
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->pInfo()->bbcode($welcome);
            }
            $html->pInfo(sprintf(
                             $this->_('Please click the button below to answer the survey for token %s.'),
                             strtoupper($this->showToken->getTokenId())));
        }
        if ($delay > 0) {
            $html->pInfo(sprintf($this->plural(
                'Wait one second to open the survey automatically or click on Cancel to stop.',
                'Wait %d seconds to open the survey automatically or click on Cancel to stop.',
                $delay), $delay));
        }

        $buttonDiv = $html->buttonDiv(array('class' => 'centerAlign'));
        $buttonDiv->actionLink($href, $this->getSurveyName($this->showToken));

        $buttonDiv->append(' ');
        $buttonDiv->append($this->formatDuration($this->showToken->getSurvey()->getDuration()));
        $buttonDiv->append($this->formatUntil($this->showToken->getValidUntil()));

        if ($delay > 0) {
            $buttonDiv->actionLink(array('delay_cancelled' => 1), $this->_('Cancel'));
        }

        if ($this->wasAnswered) {
            // Provide continue later link only when the first survey was answered
            $this->addContinueLink($html, $this->showToken);
        }

        if ($count) {
            $html->pInfo(sprintf($this->plural(
                'After this survey there is one other survey we would like you to answer.',
                'After this survey there are another %d surveys we would like you to answer.',
                $count), $count));
        } elseif ($this->wasAnswered) {
            $html->pInfo($this->_('This survey is the last survey to answer.'));
        }
        if ($sig = $org->getSignature()) {
            $html->pInfo()->bbcode($sig);
        }
        return $html;
    }

    /**
     * Count the number of other surveys not yet answered
     * 
     * @param \Gems_Tracker_Token $token
     * @return int
     */
    protected function getOtherTokenCountUnanswered(\Gems_Tracker_Token $token)
    {
        $count = $token->getTokenCountUnanswered();
        
        // In case of null
        return $count ? $count : 0;
    }

    /**
     * Allow for overruling
     * 
     * @param \Gems_Tracker_Token $token
     * @return string
     */
    public function getSurveyName(\Gems_Tracker_Token $token)
    {
        return $token->getSurvey()->getExternalName();
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

        $validToken = ($this->showToken instanceof \Gems_Tracker_Token) && $this->showToken->exists;

        if (!$validToken && $this->wasAnswered) {
            // The token was answered, but there are no more tokens to show
            $validToken = $this->showEndScreen;
        }

        return $validToken;
    }
}
