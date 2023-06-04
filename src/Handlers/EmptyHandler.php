<?php

namespace Gems\Handlers;

use Gems\Layout\LayoutRenderer;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class EmptyHandler implements RequestHandlerInterface
{
    public function __construct(
        protected LayoutRenderer $layoutRenderer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'content' => null,
        ];
        return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::legacy-view', $request, $data));
    }
}