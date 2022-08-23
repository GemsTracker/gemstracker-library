<?php

namespace Gems\AuthNew;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly TfaService $tfaService,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authenticationService->isLoggedIn()) {
            throw new \Exception('Redirect to login');
            return new RedirectResponse(); // to login page
        }

        $user = $this->authenticationService->getLoggedInUser();

        if ($this->tfaService->requiresAuthentication($user)) {
            throw new \Exception('Redirect to tfa');
            return new RedirectResponse(); // to tfa page
        }

        $request = $request->withAttribute('current_user', $user);

        return $handler->handle($request);
    }
}
