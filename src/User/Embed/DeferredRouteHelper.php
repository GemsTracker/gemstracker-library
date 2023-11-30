<?php

namespace Gems\User\Embed;

use Gems\Menu\RouteNotFoundException;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;

class DeferredRouteHelper
{
    private array $routes = [];
    public function __construct(
        private readonly Acl $acl,
        private readonly UrlHelper $urlHelper,
        array $config,
    )
    {
        foreach ($config['routes'] as $route) {
            $this->routes[$route['name']] = $route;
        }
    }

    public function getRoute(string $name, string|null $userRole = null): ?array
    {
        if (!$this->hasAccessToRoute($name, $userRole)) {
            return null;
        }

        return $this->routes[$name];
    }

    public function getRouteUrl(string $name, array $routeParams = [], array $queryParams = [], string|null $userRole = null): ?string
    {
        $route = $this->getRoute($name, $userRole);

        return $route === null ? null : $this->urlHelper->generate($name, $routeParams, $queryParams);
    }

    public function hasAccessToRoute(string $name, string|null $userRole = null): bool
    {
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException($name);
        }

        $route = $this->routes[$name];

        return empty($route['options']['privilege']) || $this->hasPrivilege($route['options']['privilege'], $userRole);
    }

    public function hasPrivilege(string $resource, string|null $userRole = null): bool
    {
        $disablePrivileges = isset($this->config['temp_config']['disable_privileges']) && $this->config['temp_config']['disable_privileges'] === true;
        return $userRole !== null && $this->acl->isAllowed($userRole, $resource) || $disablePrivileges;
    }
}