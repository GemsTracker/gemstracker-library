<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Menu\MenuRepository;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MenuMiddleware implements MiddlewareInterface
{
    public const MENU_ATTRIBUTE = 'mainMenu';

    public function __construct(
        private readonly MenuRepository $menuRepository,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

        $menu = $this->menuRepository->getMenu($user);

        // TODO: Disable default param

        $request = $request->withAttribute(self::MENU_ATTRIBUTE, $menu);

        /** @var \Mezzio\Router\RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $menu->openRouteResult($routeResult);

        return $handler->handle($request);
    }
}
