<?php

namespace Gems\Handlers;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use MUtil\Legacy\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RedirectHandler implements RequestHandlerInterface
{

    public function __construct(
        private readonly UrlHelper $urlHelper,
    )
    {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestHelper = new RequestHelper($request);
        $options = $requestHelper->getRouteOptions();
        if (!isset($options['redirect'])) {
            return new EmptyResponse();
        }

        $url = $this->urlHelper->generate($options['redirect']);
        return new RedirectResponse($url);
    }
}