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

    public function getRouteParent(string $name, string $parentName = 'index'): ?array
    {
        return $this->getRouteSibling($name, $parentName);
    }

    public function getRouteSibling(string $name, string $siblingName = 'index'): ?array
    {
        $routeParts = explode('.', $name);
        $routeParts[count($routeParts)-1] = $siblingName;
        $parentRouteName = join('.', $routeParts);
        return $this->getRoute($parentRouteName);
    }

    public function getRouteSiblings(string $name): ?array
    {
        $routeParts = explode('.', $name);
        $partsCount = count($routeParts);
        array_pop($routeParts);

        $baseRouteName = join('.', $routeParts);

        $sibblingRoutes = array_filter($this->routes, function($routeName) use ($baseRouteName, $partsCount) {
            return (str_starts_with($routeName, $baseRouteName) && count(explode('.', $routeName)) === $partsCount);
        }, ARRAY_FILTER_USE_KEY );

        // TODO: Add filter for parameters

        return $sibblingRoutes;
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
