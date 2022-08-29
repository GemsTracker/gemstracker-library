<?php

namespace Gems\AuthNew;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly RouterInterface $router,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        if ($session === null) {
            throw new \LogicException('Session middleware should be executed before authentication middleware');
        }

        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

        if (!$authenticationService->isLoggedIn()) {
            return new RedirectResponse($this->router->generateUri('auth.login'));
        }

        $user = $authenticationService->getLoggedInUser();

        $tfaService = new TfaService($session, $authenticationService, $request);
        if ($tfaService->requiresAuthentication($user)) {
            return new RedirectResponse($this->router->generateUri('tfa.login'));
        }

        $request = $request->withAttribute('current_user', $user);

        return $handler->handle($request);
    }
}
