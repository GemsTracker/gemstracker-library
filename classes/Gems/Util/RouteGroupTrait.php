<?php

namespace Gems\Util;

trait RouteGroupTrait
{
    public function routeGroup(array $groupOptions, array $routes): array
    {
        foreach ($routes as $i => $route) {
            if (isset($groupOptions['path'])) {
                $route['path'] = rtrim($groupOptions['path'], '/') . '/' . ltrim($route['path'] ?? '', '/');
            }

            if (isset($groupOptions['middleware'])) {
                $route['middleware'] = array_merge(
                    (array)$groupOptions['middleware'],
                    (array)($route['middleware'] ?? [])
                );
            }

            if (isset($groupOptions['options'])) {
                $route['options'] = array_merge(
                    $groupOptions['options'],
                    $route['options'] ?? []
                );
            }

            $routes[$i] = $route;
        }

        return $routes;
    }
}
