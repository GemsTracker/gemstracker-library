<?php

namespace Gems\Middleware;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\User\User;
use Gems\Util\Lock\MaintenanceLock;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class MaintenanceModeMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected readonly MaintenanceLock $maintenanceLock,
        protected readonly TranslatorInterface $translator,
        protected readonly UrlHelper $urlHelper,
    )
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->maintenanceLock->isLocked()) {
            return $handler->handle($request);
        }

        /**
         * @var StatusMessengerInterface|null $messenger
         */
        $messenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $messenger->addDanger($this->translator->_('System is in maintenance mode'));

        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($user instanceof User) {
            if ($user->hasPrivilege('pr.maintenance.maintenance-mode', false)) {
                return $handler->handle($request);
            }
            return new RedirectResponse($this->urlHelper->generate('auth.logout'));
        }

        $routeResult = $request->getAttribute(RouteResult::class);
        if ($routeResult instanceof RouteResult && $routeResult->getMatchedRouteName() === 'auth.login') {
            return $handler->handle($request);
        }

        $handler->handle($request);
        return new RedirectResponse($this->urlHelper->generate('auth.login'));
    }
}