<?php

namespace Gems\Route;

use Gems\Legacy\LegacyController;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\SnippetsLoader\SnippetMiddleware;

trait ModelSnippetActionRouteHelpers
{
    protected array $defaultPages = [
        'index',
        'autofilter',
        'create',
        'show',
        'edit',
        'delete'
    ];

    protected array $defaultParameters = [
        'id' => '\d+',
    ];

    protected array $defaultParameterRoutes = [
        'show',
        'edit',
        'delete',
    ];

    protected array $defaultPostRoutes = [
        'create',
        'edit',
        'index',
        'delete',
    ];

    protected array $modelSnippetCustomMiddleware = [
        SnippetMiddleware::class,
        LegacyCurrentUserMiddleware::class,
        LegacyController::class,
    ];

    public function createBrowseRoutes(string $baseName,
                                       string $controllerClass,
                                       ?string $basePath = null,
                                       string|bool|null $basePrivilege = null,
                                       ?array $pages = null,
                                       ?array $customMiddleware = null,
                                       ?array $parameters = null,
                                       ?array $parameterRoutes = null,
                                       ?array $postRoutes = null,
                                       ?array $parentParameters = null): array
    {
        if ($basePath === null) {
            $basePath = '/' . str_replace('.', '/', $baseName);
        }

        if ($basePrivilege === null) {
            $basePrivilege = 'pr.' . $baseName;
        }

        if ($pages === null) {
            $pages = $this->defaultPages;
        }

        if ($customMiddleware === null) {
            $customMiddleware = $this->modelSnippetCustomMiddleware;
        }

        $routes = [];

        if ($parameters === null) {
            $parameters = $this->defaultParameters;
        }

        if ($parameterRoutes === null) {
            $parameterRoutes = $this->defaultParameterRoutes;
        }

        if ($postRoutes === null) {
            $postRoutes = $this->defaultPostRoutes;
        }

        $combinedParameters = [];
        foreach ($parameters as $parameterName => $parameterRegex) {
            $combinedParameters[] = '{' . $parameterName . ':'. $parameterRegex . '}';
        }

        $parameterString = join('/', $combinedParameters);

        foreach($pages as $pageName) {
            $route = [
                'name' => $baseName . '.' . $pageName,
                'path' => $basePath . '/' . $pageName,
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => $pageName,
                ],
            ];

            if ($basePrivilege !== false) {
                $route['options']['privilege'] = $basePrivilege . '.' . $pageName;
            }

            if ($pageName === 'index' || $pageName === 'show') {
                $route['path'] = $basePath;
            }

            if ($parentParameters !== null) {
                $route['params'] = $parentParameters;
            }

            if (in_array($pageName, $parameterRoutes)) {
                $route['path'] .= '/' . $parameterString;

                if (!array_key_exists('params', $route)) {
                    $route['params'] = [];
                }
                $route['params'] = [...$route['params'], ...array_keys($parameters)];
            }

            if (in_array($pageName, $postRoutes)) {
                $route['allowed_methods'][] = 'POST';
            }

            $routes[$baseName . '.' . $pageName] = $route;
        }

        return $routes;
    }

    public function createSnippetRoutes(string $baseName,
                                       string $controllerClass,
                                       ?string $basePath = null,
                                       string|bool|null $basePrivilege = null,
                                       ?array $pages = null,
                                       ?array $customMiddleware = null,
                                       ?array $parameters = null,
                                       ?array $parameterRoutes = null,
                                       ?array $postRoutes = null,
                                       ?array $parentParameters = null): array
    {
        return $this->createBrowseRoutes(
            $baseName, 
            $controllerClass, 
            $basePath, $basePrivilege,
            $pages, 
            $customMiddleware ?: [
                LegacyCurrentUserMiddleware::class,
                $controllerClass,
            ],
            $parameters, 
            $parameterRoutes, 
            $postRoutes, 
            $parentParameters);
    }

    public function getDefaultMiddleware(string|array $additionalMiddleware): array
    {
        $middleware = $this->modelSnippetCustomMiddleware;
        if (is_string($additionalMiddleware)) {
            $additionalMiddleware = [$additionalMiddleware];
        }
        array_pop($middleware);
        return $middleware + $additionalMiddleware;
    }
}