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
                $routeParams[$paramName] = $lateName; 
            }
        }
        // file_put_contents('data/logs/echo.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . "$name -> " . print_r($routeParams, true) . "\n", FILE_APPEND);

        return Late::method($this->urlHelper, 'generate', $name, Late::getRa($routeParams));
    }

    /**
     * @param string $current  The route to compare to
     * @param string $relative Routes related to current, e.g. a different action in the same route.
     * @return string|null
     */
    public function getRelatedRoute(string $current, string $relative): ?string
    {
        if (isset($this->routes[$relative])) {
            return $relative;
        }
        if (str_contains($relative, '.')) {
            $cParts = explode('.', $current);
            $rParts = explode('.', $relative);

            $output = implode('.', array_splice($cParts, 0, -count($rParts), $rParts));
        } else {
            $output = substr($current, 0, strrpos($current, '.') + 1) . $relative;
        }
        if (isset($this->routes[$output])) {
            return $output;
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function getAllRoutePrivileges(): array
    {
        $privileges = [];

        foreach ($this->routes as $route) {
            if (isset($route['options']['privilege'])) {
                $privileges[$route['options']['privilege']] = true;
            }
        }

        return array_keys($privileges);
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

    public function getRouteUrlOnMatch(string $name, array $routeParams = []): ?string
    {
        $route = $this->getRoute($name);

        if (null !== $route) {
            if (! isset($route['params']) || $this->hasMatchingParameters($route['params'], array_keys($routeParams))) {
                return $this->urlHelper->generate($name, $routeParams);
            }
        }
        return null;
    }

    public function hasAccessToRoute(string $name): bool
    {
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException($name);
        }

        $route = $this->routes[$name];

        return empty($route['options']['permission']) || $this->hasPermission($route['options']['permission']);
    }

    protected function hasMatchingParameters($requiredParams, $availableParamKeys): bool
    {
        return ! array_diff($requiredParams, $availableParamKeys);
    }    
    
    public function hasPermission(string $resource): bool
    {
        return $this->userRole !== null && $this->acl->isAllowed($this->userRole, $resource);
    }
}
