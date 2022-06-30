<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\MenuNew\Menu;
use Gems\MenuNew\RouteLinkItem;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MenuMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly TemplateRendererInterface $template
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Mezzio\Router\RouteResult $routeResult */
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $menu = new Menu($this->router, $this->template);

        $menu->add($a = new RouteLinkItem('route-a', 'Route a'));
        $a->add(new RouteLinkItem('route-b', 'Route b'));
        $a->add($c = new RouteLinkItem('route-c', 'Route c'));
        $c->add(new RouteLinkItem('route-d', 'Route d'));
        $a->add(new RouteLinkItem('route-e', 'Route e'));
        $menu->add(new RouteLinkItem('route-f', 'Route f'));

        $request = $request->withAttribute(Menu::class, $menu);

        $menu->openRouteResult($routeResult);

        return $handler->handle($request);
    }
}
