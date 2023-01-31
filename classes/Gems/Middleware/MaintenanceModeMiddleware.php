<?php

namespace Gems\Middleware;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\User\User;
use Gems\Util\Lock\MaintenanceLock;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use MUtil\Translate\Translator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Message\StatusMessengerInterface;

class MaintenanceModeMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected MaintenanceLock $maintenanceLock,
        protected Translator $translator,
        protected UrlHelper $urlHelper,
    )
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (!$this->maintenanceLock->isLocked()) {
            return $response;
        }

        /**
         * @var $messenger StatusMessengerInterface
         */
        $messenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $messenger->addDanger($this->translator->_('System is in maintenance mode'));

        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($user instanceof User && $user->hasPrivilege('pr.maintenance.maintenance-mode', false)) {
            return $response;
        }

        return new RedirectResponse($this->urlHelper->generate('auth.logout'));
    }
}