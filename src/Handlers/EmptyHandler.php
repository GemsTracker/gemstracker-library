<?php

namespace Gems\Handlers;

use Gems\Layout\LayoutRenderer;
use Gems\Snippets\Generic\CurrentButtonColumnSnippet;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class EmptyHandler implements RequestHandlerInterface
{
    public function __construct(
        protected readonly LayoutRenderer $layoutRenderer,
        protected readonly SnippetResponderInterface $responder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->responder->processRequest($request);

        $params = [
            'addCurrentChildren' => true,
            'addCurrentParent' => false,
            'addCurrentSiblings' => false,
        ];

        return $this->responder->getSnippetsResponse([CurrentButtonColumnSnippet::class], $params);
    }
}