<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\AuthTfa\OtpMethodBuilder;
use Gems\AuthTfa\TfaService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly OtpMethodBuilder $otpMethodBuilder,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionInterface::class);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $tfaService = new TfaService($session, $authenticationService, $this->otpMethodBuilder);

        $tfaService->logout();
        $authenticationService->logout();

        $session->clear();

        return new RedirectResponse($this->urlHelper->generate('auth.login'));
    }
}
