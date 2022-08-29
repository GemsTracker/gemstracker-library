<?php

namespace Gems\AuthNew;

use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotAuthenticatedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        if ($session === null) {
            throw new \LogicException('Session middleware should be executed before authentication middleware');
        }

        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

        if ($authenticationService->isLoggedIn()) {
            return AuthenticationMiddleware::redirectToIntended($session, $this->urlHelper);
        }

        return $handler->handle($request);
    }
}
