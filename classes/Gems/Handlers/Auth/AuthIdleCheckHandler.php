<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AuthNew\AuthenticationServiceBuilder;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Router\RouterInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthIdleCheckHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

        if (!$authenticationService->isLoggedIn() || !$authenticationService->checkValid($request->getMethod() === 'POST')) {
            /** @var FlashMessagesInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
            $flash?->flash('login_errors', [
                $this->translator->trans('You have been automatically logged out from the application'),
            ]);

            return new JsonResponse([
                'redirect' => $this->router->generateUri('auth.login'),
            ]);
        }

        if ($authenticationService->getIdleAllowedUntil() - time() < $this->config['session']['idle_warning_before_logout']) {
            return new JsonResponse([
                'show_idle_logout_warning' => true,
            ]);
        }

        return new JsonResponse([]);
    }
}
