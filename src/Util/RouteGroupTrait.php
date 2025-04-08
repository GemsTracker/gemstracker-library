<?php

namespace Gems\Util;

use Laminas\Stdlib\ArrayUtils\MergeReplaceKey;

trait RouteGroupTrait
{
    public function routeGroup(array $groupOptions, array $routes, bool $privileges = true): array
    {
        foreach ($routes as $i => $route) {
            if (isset($groupOptions['path'])) {
                $route['path'] = rtrim($groupOptions['path'], '/') . '/' . ltrim($route['path'] ?? '', '/');
            }

            if (isset($groupOptions['middleware'])) {
                if ($route['middleware'] instanceof MergeReplaceKey) {
                    $route['middleware'] = new MergeReplaceKey(array_unique(array_merge(
                        (array)$groupOptions['middleware'],
                        (array)($route['middleware']->getData() ?? [])
                    )));
                } else {
                    $route['middleware'] = array_unique(array_merge(
                        (array)$groupOptions['middleware'],
                        (array)($route['middleware'] ?? [])
                    ));
                }
            }

            if (isset($groupOptions['options'])) {
                $route['options'] = array_merge(
                    $groupOptions['options'],
                    $route['options'] ?? []
                );
            }

            if (! $privileges) {
                unset($route['options']['privilege']);
            }

            $routes[$i] = $route;
        }

        return $routes;
    }
}
