<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Ask;

use Gems\Screens\AskScreenInterface;
use Gems\Tracker;
use Gems\Tracker\Token;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Sequence;
use Zalt\Snippets\SnippetInterface;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @since      Class available since version 1.0
 */
class ListTokensAskSnippet extends TranslatableSnippetAbstract
{
    protected array $defaultLoopParameters = [];

    protected array $defaultLoopSnippets;

    protected Sequence $html;

    protected string $redirectUrl = '';

    protected ?ResponseInterface $response = null;

    protected Token $token;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly SnippetLoader $snippetLoader,
        protected readonly Tracker $tracker,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        $this->html = $this->getHtmlSequence();
    }

    public function getHtmlOutput()
    {
        return $this->html;
    }

    public function getRedirectRoute(): ?string
    {
        return $this->redirectUrl;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function hasHtmlOutput(): bool
    {
        $params          = $this->defaultLoopParameters;
        $params['token'] = $this->token;
        $snippets        = $this->defaultLoopSnippets;

        $screen = $this->token->getOrganization()->getTokenAskScreen();
        if ($screen instanceof AskScreenInterface) {
            $params += $screen->getParameters($this->token);

            $screenSnippets = $screen->getSnippets($this->token);
            if ($screenSnippets) {
                $snippets = $screen->getSnippets($this->token);
            }
        }
        foreach ($snippets as $filename) {
            $snippet = $this->snippetLoader->getSnippet($filename, $params);
            if ($snippet instanceof SnippetInterface) {
                if ($snippet->hasHtmlOutput()) {
                    $this->html->append($snippet);

                } else {
                    $this->response = $snippet->getResponse();
                    if ($this->response instanceof ResponseInterface) {
                        return true;
                    }
                    $this->redirectUrl = $snippet->getRedirectRoute();
                    if ($this->redirectUrl) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}