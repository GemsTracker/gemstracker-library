<?php

namespace Gems\AuthNew;

use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MaybeAuthenticatedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
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
            $user = $authenticationService->getLoggedInUser();
            $request = $request->withAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE, $user);
            $request = $request->withAttribute(AuthenticationMiddleware::CURRENT_IDENTITY_ATTRIBUTE, $authenticationService->getIdentity());
        }

        $response = $handler->handle($request);
        return $response;
    }
}
