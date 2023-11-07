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

use Gems\Communication\CommunicationRepository;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Tracker\Snippets\ShowTokenLoopAbstract;
use Gems\Tracker\Token;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\AElement;
use Zalt\SnippetsLoader\SnippetOptions;

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
class ShowFirstOpenSnippet extends ShowTokenLoopAbstract
{
    /**
     * Show this snippet show a thank you screen when there are no more tokens to answer?
     *
     * @var boolean
     */
    public bool $showEndScreen = true;

    protected Token $showToken;

    /**
     * Switch for showing how long the token is valid.
     *
     * @var boolean
     */
    protected bool $showUntil = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translator,
        CommunicationRepository $communicationRepository,
        MenuSnippetHelper $menuSnippetHelper,
        protected array $config,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translator, $communicationRepository, $menuSnippetHelper);
        if ($this->wasAnswered) {
            $this->showToken = $this->token->getNextUnansweredToken();
        } else {
            $this->showToken = $this->token;
        }
    }

    protected function getAskDelay(): int
    {
        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams['delay_cancelled'])) {
            return -1;
        }
        if (isset($queryParams['delay'])) {
            return (int)$queryParams['delay'];
        }
        if ($this->wasAnswered) {
            if (isset($this->config['survey']['ask']['askNextDelay'])) {
                return (int)$this->config['survey']['ask']['askNextDelay'];
            }
        } elseif (isset($this->config['survey']['ask']['askDelay'])) {
            return (int)$this->config['survey']['ask']['askDelay'];
        }

        return -1;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        if ($this->checkContinueLinkClicked()) {
            // Continue later was clicked, handle the click
            return $this->continueClicked();
        }
        if (! $this->showToken) {
            // Last token was answered, return info
            return $this->lastCompleted();
        }

        $delay = $this->getAskDelay();

        $url = $this->menuSnippetHelper->getRouteUrl('ask.to-survey', ['id' => $this->showToken->getTokenId()]);

        switch ($delay) {
            case 0:
                // Redirect at once
                header('Location: ' . $url);
                exit();

            case -1:
                break;

            default:
                // Let the page load after stated interval
                //$view->headMeta()->appendHttpEquiv('Refresh', $delay . '; url=' . $url);
        }

        $count = $this->getOtherTokenCountUnanswered($this->showToken);
        $html  = $this->getHtmlSequence();
        $org   = $this->showToken->getOrganization();

        $html->h3($this->getHeaderLabel());

        $html->append($this->formatWelcome());

        if ($this->wasAnswered) {
            $html->p(sprintf(
                $this->_('Thank you for answering the "%s" survey.'),
                $this->getSurveyName($this->token)), ['class' => 'info']);
            $html->p($this->_('Please click the button below to answer the next survey.'), ['class' => 'info']);
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->p(['class' => 'info'])->raw($welcome);
            }
            $html->p(sprintf(
                $this->_('Please click the button below to answer the survey for token %s.'),
                strtoupper($this->showToken->getTokenId())), ['class' => 'info']);
        }
        if ($delay > 0) {
            $html->p(sprintf($this->plural(
                'Wait one second to open the survey automatically or click on Cancel to stop.',
                'Wait %d seconds to open the survey automatically or click on Cancel to stop.',
                $delay), $delay), ['class' => 'info']);
        }

        $buttonDiv = $html->buttonDiv(array('class' => 'centerAlign'));
        $button = new AElement($url, $this->getSurveyName($this->showToken), ['class' => 'actionlink btn']);
        $buttonDiv->append($button);

        $buttonDiv->append(' ');
        $buttonDiv->append($this->formatDuration($this->showToken->getSurvey()->getDuration()));
        $buttonDiv->append($this->formatUntil($this->showToken->getValidUntil()));

        if ($delay > 0) {
            $buttonDiv->a(array('delay_cancelled' => 1), $this->_('Cancel'), ['class' => 'actionlink btn']);
        }

        if ($this->wasAnswered) {
            // Provide continue later link only when the first survey was answered
            $this->addContinueLink($html, $this->showToken);
        }

        if ($count) {
            $html->p(sprintf($this->plural(
                'After this survey there is one other survey we would like you to answer.',
                'After this survey there are another %d surveys we would like you to answer.',
                $count), $count), ['class' => 'info']);
        } elseif ($this->wasAnswered) {
            $html->p($this->_('This survey is the last survey to answer.'), ['class' => 'info']);
        }
        if ($sig = $org->getSignature()) {
            $html->p($sig, ['class' => 'info']);
        }
        return $html;
    }

    /**
     * Count the number of other surveys not yet answered
     *
     * @param Token $token
     * @return int
     */
    protected function getOtherTokenCountUnanswered(Token $token)
    {
        $count = $token->getTokenCountUnanswered();

        // In case of null
        return $count ? $count : 0;
    }

    /**
     * Allow for overruling
     *
     * @param Token $token
     * @return string
     */
    public function getSurveyName(Token $token)
    {
        return $token->getSurvey()->getExternalName();
    }

    public function hasHtmlOutput(): bool
    {
        $validToken = ($this->showToken instanceof Token) && $this->showToken->exists;

        if (!$validToken && $this->wasAnswered) {
            // The token was answered, but there are no more tokens to show
            $validToken = $this->showEndScreen;
        }

        return $validToken;
    }
}
