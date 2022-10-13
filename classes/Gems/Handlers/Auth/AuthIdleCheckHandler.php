<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\Site\SiteUtil;
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
        private readonly SiteUtil $siteUtil,
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

            $serverParams = $request->getServerParams();
            if (isset($serverParams['HTTP_REFERER']) && $this->siteUtil->isAllowedUrl($serverParams['HTTP_REFERER'])) {
                AuthenticationMiddleware::registerIntended(
                    $authenticationService->getLoggedInUser(),
                    $session,
                    $serverParams['HTTP_REFERER']
                );
            }

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
