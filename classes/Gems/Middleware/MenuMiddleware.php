<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\MenuNew\Menu;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MenuMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $config,
        private readonly RouterInterface $router,
        private readonly TemplateRendererInterface $template,
        private readonly \Gems\Config\Menu $menuConfig,
        private readonly Acl $acl,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Mezzio\Router\RouteResult $routeResult */
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $userRole = $request->getAttribute('userRole');

        $menu = new Menu($this->router, $this->template, $this->menuConfig, $this->acl, $userRole, $this->config);

        $this->template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'mainMenu', $menu);

        $menu->openRouteResult($routeResult);

        return $handler->handle($request);
    }
}
