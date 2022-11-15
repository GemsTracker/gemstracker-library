<?php

namespace Gems\MenuNew;

use Gems\Html;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;
use Zalt\Late\Late;
use Zalt\Late\LateCall;

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

    /**
     * @param array $items
     * @return []ElementInterface
     */
    public function getActionLinksFromRouteItems(array $items, array $knownParameters = []): array
    {
        $links = [];
        foreach($items as $item) {
            if (isset($item['disabled']) && $item['disabled'] === true) {
                $links[] = Html::actionDisabled($item['label']);
                continue;
            }
            if (isset($item['parameters'])) {
                $knownParameters = $item['parameters'] + $knownParameters;
            }
            $route = $this->getRoute($item['route']);
            if ($route) {
                $url = $this->getRouteUrl($item['route'], $this->getRouteParamsFromKnownParams($route, $knownParameters));

                $links[] = Html::actionLink($url, $item['label']);
            }
        }

        return $links;
    }

    public function getLateRouteUrl(string $name, array $paramLateMappings = []): ?LateCall
    {
        $route = $this->getRoute($name);

        if (null === $route) {
            return null;
        }
        $routeParams = [];
        if (isset($route['params'])) {
            foreach ($route['params'] as $paramName) {
                if (isset($paramLateMappings[$paramName])) {
                    $lateName = $paramLateMappings[$paramName];
                    // file_put_contents('data/logs/echo.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . "$paramName -> $lateName\n", FILE_APPEND);
                } else {
                    $lateName = $paramName;
                }
                $routeParams[$paramName] = Late::get($lateName); 
            }
        }

        return Late::method($this->urlHelper, 'generate', $name, $routeParams);
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

        return $sibblingRoutes;
    }


    public function getRouteParamsFromKnownParams(array $newRoute, array $knownParams): array
    {
        $params = [];
        if (isset($newRoute['params'])) {

            foreach($newRoute['params'] as $param) {
                if (isset($knownParams[$param])) {
                    $params[$param] = $knownParams[$param];
                }
            }
        }
        return $params;
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
