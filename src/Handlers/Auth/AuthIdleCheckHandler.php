<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\Site\SiteUtil;
use Gems\Middleware\FlashMessageMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

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

        /** @var StatusMessengerInterface|null $statusMessenger */
        $statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $statusMessenger?->prolong();

        if (!$authenticationService->isLoggedIn() || !$authenticationService->checkValid($request->getMethod() === 'POST')) {
            $statusMessenger?->addErrors([
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
