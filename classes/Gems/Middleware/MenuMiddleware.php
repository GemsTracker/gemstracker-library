<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\MenuNew\Menu;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MenuMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $config,
        private readonly ContainerInterface $container
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Mezzio\Router\RouteResult $routeResult */
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $menu = new Menu($this->container, $this->config);

        $template = $this->container->get(TemplateRendererInterface::class);
        $template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'mainMenu', $menu);

        $menu->openRouteResult($routeResult);

        return $handler->handle($request);
    }
}
