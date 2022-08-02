<?php

namespace Gems\MenuNew;

use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;

class RouteHelper
{
    private array $routes;

    public function __construct(
        private readonly Acl $acl,
        private readonly UrlHelper $urlHelper,
        private readonly ?string $userRole,
        array $config,
    ) {
        $this->routes = [];
        foreach ($config['routes'] as $route) {
            $this->routes[$route['name']] = $route;
        }
    }

    public function getRoute(string $name): ?array
    {
        if (!$this->hasAccessToRoute($name)) {
            return null;
        }

        return $this->routes[$name];
    }

    public function getRouteUrl(string $name, array $routeParams = []): ?string
    {
        $route = $this->getRoute($name);

        return $route === null ? null : $this->urlHelper->generate($name, $routeParams);
    }

    public function hasAccessToRoute(string $name): bool
    {
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException($name);
        }

        $route = $this->routes[$name];

        return empty($route['options']['permission']) || $this->hasPermission($route['options']['permission']);
    }

    public function hasPermission(string $resource): bool
    {
        return $this->userRole !== null && $this->acl->isAllowed($this->userRole, $resource);
    }
}
