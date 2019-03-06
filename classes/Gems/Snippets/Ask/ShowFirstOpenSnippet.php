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
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

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
     * Switch for showing how long the token is valid.
     *
     * @var boolean
     */
    protected $showUntil = false;

    /**
     * Show this snippet show a thank you screen when there are no more tokens to answer?
     *
     * @var boolean
     */
    public $showEndScreen = true;

    public function addContinueLink(\MUtil_Html_HtmlInterface $html)
    {
        if (!$this->checkContinueLinkClicked()) {
            $mailLoader = $this->loader->getMailLoader();
            /** @var \Gems_Mail_TokenMailer $mail */
            $mail       = $mailLoader->getMailer('token', $this->showToken->getTokenId());

            // If there is no template, we show no link
            if ($mail->setTemplateByCode('continue')) {
                $html->pInfo($this->_('or'));
                $menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'forward'));
                $href = $menuItem->toHRefAttribute($this->request);
                $href->add(['continue_later' => 1, 'id' => $this->token->getTokenId()]);
                $html->actionLink($href, $this->_('Send me an email to continue later'));
            }
        }
    }

    public function addWelcome(\MUtil_Html_HtmlInterface $html)
    {
        if ($this->showToken) {
            if ($this->showToken->hasRelation()) {
                $name = $this->showToken->getRelation()->getName();
            } else {
                $name = $this->showToken->getRespondentName();
            }
        }
        $html->pInfo(sprintf($this->_('Welcome %s,'), $name));
    }

    public function checkContinueLinkClicked()
    {
        return $this->request->getParam('continue_later', false);
    }

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
     * Handle the situation when the continue later link was clicked
     *
     * @return \MUtil_Html_HtmlInterface
     */
    public function continueClicked()
    {
        $html = $this->getHtmlSequence();
        $org  = $this->token->getOrganization();

        $html->h3($this->_('Token'));

        $mailLoader = $this->loader->getMailLoader();
        /** @var \Gems_Mail_TokenMailer $mail */
        $mail       = $mailLoader->getMailer('token', $this->showToken->getTokenId());
        $mail->setFrom($this->showToken->getOrganization()->getFrom());
        if ($mail->setTemplateByCode('continue')) {
            $lastMailedDate = \MUtil_Date::ifDate($this->showToken->getMailSentDate(), 'yyyy-MM-dd');
            // Do not send multiple mails a day
            if (! is_null($lastMailedDate) && $lastMailedDate->isToday()) {
                $html->pInfo($this->_('An email with information to continue later was already sent to your registered email address today.'));
            } else {
                $mail->send();
                $html->pInfo($this->_('An email with information to continue later was sent to your registered email address.'));
            }

            $html->pInfo($this->_('Delivery can take a while. If you do not receive an email please check your spam-box.'));
        }

        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw(\MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
        }

        return $html;
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
        if ($this->wasAnswered) {
            if (!($this->showToken instanceof \Gems_Tracker_Token)) {
                // Last token was answered, return info
                return $this->lastCompleted();

            } elseif ($this->checkContinueLinkClicked()) {
                // Continue later was clicked, handle the click
                return $this->continueClicked();
            }
        }

        $delay = $this->project->getAskDelay($this->request, $this->wasAnswered);
        $href  = $this->getTokenHref($this->showToken);
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

        $html  = $this->getHtmlSequence();
        $org   = $this->showToken->getOrganization();

        $html->h3($this->_('Token'));

        $this->addWelcome($html);
        if ($this->showToken && $this->showToken->hasRelation()) {
            $html->pInfo(sprintf($this->_('We kindly ask you to answer a survey about %s.'), $this->showToken->getRespondent()->getName()));
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

        $buttonDiv->append(' ');
        $buttonDiv->append($this->formatDuration($this->showToken->getSurvey()->getDuration()));
        $buttonDiv->append($this->formatUntil($this->showToken->getValidUntil()));

        if ($delay > 0) {
            $buttonDiv->actionLink(array('delay_cancelled' => 1), $this->_('Cancel'));
        }

        if ($this->wasAnswered) {
            // Provide continue later link only when the first survey was answered
            $this->addContinueLink($html);
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

        $validToken = ($this->showToken instanceof \Gems_Tracker_Token) && $this->showToken->exists;

        if (!$validToken && $this->wasAnswered) {
            // The token was answered, but there are no more tokens to show
            $validToken = $this->showEndScreen;
        }

        return $validToken;
    }

    /**
     * The last token was answered, there are no more tokens to answer
     *
     * @return \MUtil_Html_HtmlInterface
     */
    public function lastCompleted()
    {
        // We use $this->token since there is no showToken anymore
        $html = $this->getHtmlSequence();
        $org  = $this->token->getOrganization();

        $html->h3($this->_('Token'));

        $this->addWelcome($html);

        $html->pInfo($this->_('Thank you for answering. At the moment we have no further surveys for you to take.'));

        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw(\MUtil_Markup::render($this->_($sig), 'Bbcode', 'Html'));
        }

        return $html;
    }
}
