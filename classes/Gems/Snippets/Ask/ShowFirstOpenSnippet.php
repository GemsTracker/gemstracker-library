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
use Gems\Tracker\Snippets\ShowTokenLoopAbstract;
use Gems\Tracker\Token;
use Gems\MenuNew\RouteHelper;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
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
        RouteHelper $routeHelper,
        CommunicationRepository $communicationRepository,
        Translator $translator,
        protected array $config,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $routeHelper, $communicationRepository, $translator);
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

        $url = $this->routeHelper->getRouteUrl('ask.to-survey', ['id' => $this->showToken->getTokenId()]);

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
            $html->pInfo(sprintf(
                $this->translator->_('Thank you for answering the "%s" survey.'),
                $this->getSurveyName($this->token)));
            $html->pInfo($this->translator->_('Please click the button below to answer the next survey.'));
        } else {
            if ($welcome = $org->getWelcome()) {
                $html->pInfo()->raw($welcome);
            }
            $html->pInfo(sprintf(
                $this->translator->_('Please click the button below to answer the survey for token %s.'),
                strtoupper($this->showToken->getTokenId())));
        }
        if ($delay > 0) {
            $html->pInfo(sprintf($this->translator->plural(
                'Wait one second to open the survey automatically or click on Cancel to stop.',
                'Wait %d seconds to open the survey automatically or click on Cancel to stop.',
                $delay), $delay));
        }

        $buttonDiv = $html->buttonDiv(array('class' => 'centerAlign'));
        $button = Html::actionLink($url, $this->getSurveyName($this->showToken));
        $buttonDiv->append($button);

        $buttonDiv->append(' ');
        $buttonDiv->append($this->formatDuration($this->showToken->getSurvey()->getDuration()));
        $buttonDiv->append($this->formatUntil($this->showToken->getValidUntil()));

        if ($delay > 0) {
            $buttonDiv->actionLink(array('delay_cancelled' => 1), $this->translator->_('Cancel'));
        }

        if ($this->wasAnswered) {
            // Provide continue later link only when the first survey was answered
            $this->addContinueLink($html, $this->showToken);
        }

        if ($count) {
            $html->pInfo(sprintf($this->translator->plural(
                'After this survey there is one other survey we would like you to answer.',
                'After this survey there are another %d surveys we would like you to answer.',
                $count), $count));
        } elseif ($this->wasAnswered) {
            $html->pInfo($this->translator->_('This survey is the last survey to answer.'));
        }
        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw($sig);
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
