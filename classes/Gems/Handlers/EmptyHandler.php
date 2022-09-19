<?php

namespace Gems\Handlers;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class EmptyHandler implements RequestHandlerInterface
{
    public function __construct(
        protected TemplateRendererInterface $template
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'content' => null,
        ];
        return new HtmlResponse($this->template->render('gems::legacy-view', $data));
    }
}