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
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Tracker;
use Gems\Tracker\Snippets\ShowTokenLoopAbstract;
use Gems\Tracker\Token;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Loops through all open surveys and then shows an endmessage
 *
 * Works using $project->getAskDelay()
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class RedirectUntilGoodbyeSnippet extends ShowTokenLoopAbstract
{
    protected string $clientIp;

    /**
     * Optional, calculated from $token
     */
    protected Token $currentToken;

    protected ?int $userId = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translator,
        CommunicationRepository $communicationRepository,
        MenuSnippetHelper $menuSnippetHelper,
        CurrentUserRepository $currentUserRepository,
        protected Tracker $tracker,
        protected MessengerInterface $messenger,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translator, $communicationRepository, $menuSnippetHelper);

        $this->userId = $currentUserRepository->getCurrentUserId();
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @return HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        if ($this->checkContinueLinkClicked()) {
            // Continue later was clicked, handle the click
            return $this->continueClicked();
        }

        // After the header() so that the patient does not see the messages after answering surveys
        if ($this->token->isCompleted()) {
            $this->messenger->addMessages([$this->_('Thank you for completing the survey')]);
        }

        $org  = $this->token->getOrganization();
        $html = $this->getHtmlSequence();

        $html->h3($this->getHeaderLabel());
        $html->append($this->formatThanks());
        if ($welcome = $org->getWelcome()) {
            $html->p(nl2br($welcome), ['class' => 'info']);
        }

        $p = $html->p(['class' => 'info'])->spaced();
        if ($this->wasAnswered) {
            $p->append($this->_('Thanks for answering our questions.'));
        } elseif (! $this->token->isCurrentlyValid()) {
            if ($this->token->isExpired()) {
                $this->messenger->addMessage($this->_('This survey has expired. You can no longer answer it.'));
            } else {
                $this->messenger->addMessage($this->_('This survey is no longer valid.'));
            }
        }
        $p->append($this->_('We have no further questions for you at the moment.'));
        $p->append($this->_('We appreciate your cooperation very much.'));

        if ($sig = $org->getSignature()) {
            $html->p(nl2br($sig), ['class' => 'info']);
        }

        return $html;
    }

    public function hasHtmlOutput(): bool
    {
        if ($this->wasAnswered || $this->token->checkTokenCompletion($this->userId)) {
            $next = $this->token->getNextUnansweredToken();
            if ($next) {
                $this->redirectUrl = $this->getTokenUrl($next);
                return false;
            }
        } else {
            $this->redirectUrl = $this->getTokenUrl($this->token);
            return false;
        }

        return true;
    }
}
