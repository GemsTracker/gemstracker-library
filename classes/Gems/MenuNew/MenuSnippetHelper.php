<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage MenuNew
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\MenuNew;

use Zalt\Base\RequestInfo;
use Zalt\Late\Late;
use Zalt\Late\LateCall;
use Zalt\Model\Bridge\BridgeInterface;

/**
 *
 * @package    Gems
 * @subpackage MenuNew
 * @since      Class available since version 1.9.2
 */
class MenuSnippetHelper
{
    protected RouteHelper $routeHelper;

    public function __construct(
        protected Menu $menu,
        protected RequestInfo $requestInfo
    )
    { 
        $this->routeHelper = $this->menu->routeHelper;
    }

    /**
     * @param string $parent
     * @return array routename => routename
     */
    public function getChildRoutes(string $parent): array
    {
        $output = [];

        try {
            $menuItem = $this->menu->find($parent);
        } catch (MenuItemNotFoundException $minfe) {
            return $output;
        }

        foreach ($menuItem->getChildren() as $child) {
            if ($child instanceof RouteLinkItem) {
                $output[$child->name] = $child->name;
            }
        }

        return $output;
    }

    /**
     * @return array[] routename => [label, url]
     */
    public function getCurrentChildUrls(): array
    {
        $current = $this->requestInfo->getRouteName();
        if (null===$current) {
            return [];
        }
        $routes = $this->getChildRoutes($current);
        return $this->getRouteUrls($routes, $this->requestInfo->getParams());
    }

    public function getCurrentParentRoute(): ?string
    {
        $current = $this->requestInfo->getRouteName();
        if (null===$current) {
            return null;
        }
        return $this->getParentRoute($current);
    }
    
    public function getCurrentParentUrl(): ?string
    {
        $parent = $this->getCurrentParentRoute();
        if (null===$parent) {
            return null;
        }
        return $this->getRouteUrl($parent, $this->requestInfo->getParams());
    }

    /**
     * @return string label
     */
    public function getCurrentLabel(): string
    {
        $current = $this->requestInfo->getRouteName();
        if ($current) {
            try {
                $menuItem = $this->menu->find($current);
                if ($menuItem instanceof RouteLinkItem) {
                    return $menuItem->label;
                }
            } catch (MenuItemNotFoundException $minfe) {
            }
        }
        return '';
    }
    
    /**
     * @param int $maxSteps
     * @return array[] routename => [label, url]
     */
    public function getCurrentParentUrls(int $maxSteps = 1): array
    {
        $current = $this->requestInfo->getRouteName();
        if (null===$current) {
            return [];
        }
        $routes = $this->getParentRoutes($current, $maxSteps);
        return $this->getRouteUrls($routes, $this->requestInfo->getParams());
    }
    
    /**
     * @return array[] routename => [label, url]
     */
    public function getCurrentSiblingUrls(): array
    {
        $current = $this->requestInfo->getRouteName();
        if (null===$current) {
            return [];
        }
        $routes = $this->getSiblingRoutes($current);
        return $this->getRouteUrls($routes, $this->requestInfo->getParams());
    }

    /**
     * @param array $names Related names
     * @param array $paramLateMappings
     * @return array[] routename => [label, Late::url]
     */
    public function getLateRelatedUrls(array $names, array $paramLateMappings = [], BridgeInterface $bridge = null): array
    {
        $output = [];
        foreach ($this->getRelatedRoutes($names) as $name) {
            $url = $this->getLateRouteUrl($name, $paramLateMappings, $bridge);
            if ($url) {
                $output[$name] = $url;
            }
        }
        return $output;
    }

    /**
     * @param string $route
     * @param array  $paramLateMappings
     * @return array[] [label, Late::url]
     */
    public function getLateRouteUrl(string $route, array $paramLateMappings = [], BridgeInterface $bridge = null): ?array
    {
        try {
            $menuItem = $this->menu->find($route);
        } catch (MenuItemNotFoundException $minfe) {
            return null;
        }

        if ($this->routeHelper->hasAccessToRoute($route)) {
            $url = $this->routeHelper->getLateRouteUrl($route, $paramLateMappings, $bridge);
            return [
                'label' => $menuItem->label,
                'url'   => $url,
            ];
        }
        return null;
    }

    /**
     * @param array $names
     * @param array $paramLateMappings
     * @return array[] routename => [label, Late::url]
     */
    public function getLateRouteUrls(array $names, array $paramLateMappings = [], BridgeInterface $bridge = null): array
    {
        $output = [];
        foreach ($names as $name) {
            $url = $this->getLateRouteUrl($name, $paramLateMappings, $bridge);
            if ($url) {
                $output[$name] = $url;
            }
        }
        return $output;
    }
    
    public function getParentRoute(string $route): ?string
    {
        try {
            $menuItem = $this->menu->find($route);
            $parent   = $menuItem->getParent();
            if ($parent instanceof RouteLinkItem) {
                return $parent->name;
            }
        } catch (MenuItemNotFoundException $minfe) {
        }
        return null;
    }

    /**
     * @param string $route
     * @param int    $maxSteps
     * @return array routename => routename
     */
    public function getParentRoutes(string $route, int $maxSteps = 1): array
    {
        $output  = [];
        $current = $this->getParentRoute($route);
        if ($current === null) {
            return $output;
        }

        while (count($output) < $maxSteps) {
            try {
                $menuItem = $this->menu->find($current);
                $parent   = $menuItem->getParent();
                if ($parent instanceof RouteLinkItem) {
                    $output[$parent->name] = $current = $parent->name;
                } else {
                    break;
                }
            } catch (MenuItemNotFoundException $minfe) {
                break;
            }
        }
        return $output;
    }

    /**
     * @param array $routePart Route part related to current, e.g. a different action in the same route.
     * @return string
     */
    public function getRelatedRoute(string $routePart): string
    {
        return $this->routeHelper->getRelatedRoute($this->requestInfo->getRouteName(), $routePart) ?: '';
    }

    /**
     * @param array $routes Routes related to current, e.g. a different action in the same route.
     * @return array route => route
     */
    public function getRelatedRoutes(array $routes): array
    {
        $current = $this->requestInfo->getRouteName();
        $output  = [];
        foreach ($routes as $routePart) {
            $route = $this->routeHelper->getRelatedRoute($current, $routePart);
            if ($route) {
                $output[$route] = $route;
            }
        }
        return $output;
    }

    public function getRouteUrl(?string $route, array $params): ?string
    {
        if ($route) {
            try {
                return $this->routeHelper->getRouteUrlOnMatch($route, $params);
            } catch (MenuItemNotFoundException $minfe) { }
        }            
        return null;
    }
    
    /**
     * @param array $routes an array of routenames
     * @param array $params Route parameter values
     * @return array[] routename => [label, url]
     */
    public function getRouteUrls(array $routes, array $params): array
    {
        $output = [];

        foreach ($routes as $route) {
            try {
                $menuItem = $this->menu->find($route);
            } catch (MenuItemNotFoundException $minfe) {
                continue;
            }

            if ($this->routeHelper->hasAccessToRoute($route)) {
                $url = $this->routeHelper->getRouteUrlOnMatch($menuItem->name, $params);
                $output[$route] = [
                    'label' => $menuItem->label,
                    'url'   => $url, 
                    ];
            }
        }

        return $output;
    }
    
    /**
     * @param string $current
     * @return array routename => routename
     */
    public function getSiblingRoutes(string $current): array
    {
        $routes = $this->getChildRoutes($this->getParentRoute($current));
        unset($routes[$current]);
        return $routes;
    }
}