<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Ask;

use Gems\Tracker\Token;
use Gems\Tracker\Token\TokenHelpers;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Snippets\MessageableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Snippet called AFTER the token has been processed in AskHandler and determining where to go after the survey has been answered
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @since      Class available since version 1.0
 */
class AskReturnSnippet extends MessageableSnippetAbstract
{
    protected ?Token $token = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected readonly TokenHelpers $tokenHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);
    }

    public function getResponse(): ?ResponseInterface
    {
        $url = $this->token->getReturnUrl();

        if (! $url) {
            $url = $this->tokenHelper->getDefaultReturnUrl($this->token);
        }

        return new RedirectResponse($url);

    }


    public function hasHtmlOutput(): bool
    {
        return false;
    }
}