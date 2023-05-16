<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Menu\Menu;
use Gems\Menu\RouteHelper;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MenuMiddleware implements MiddlewareInterface
{
    public const MENU_ATTRIBUTE = 'mainMenu';

    public function __construct(
        private readonly array $config,
        private readonly TemplateRendererInterface $template,
        private readonly UrlHelper $urlHelper,
        private readonly \Gems\Config\Menu $menuConfig,
        private readonly Acl $acl,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Mezzio\Router\RouteResult $routeResult */
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $userRole = null;
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($user) {
            $userRole = $user->getRole();
        }

        $routeHelper = new RouteHelper($this->acl, $this->urlHelper, $userRole, $this->config);

        $menu = new Menu($this->template, $routeHelper, $this->menuConfig);

        // TODO: Disable default param

        $request = $request->withAttribute(self::MENU_ATTRIBUTE, $menu);

        $menu->openRouteResult($routeResult);

        return $handler->handle($request);
    }
}