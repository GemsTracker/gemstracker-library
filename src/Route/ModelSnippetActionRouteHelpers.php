<?php

namespace Gems\Route;

use Gems\Legacy\LegacyController;
use Gems\Middleware\HandlerCsrfMiddleware;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\LegacyModelMiddleware;
use Gems\SnippetsLoader\SnippetMiddleware;
use Zalt\SnippetsActions\NoCsrfInterface;
use Zalt\SnippetsActions\ParameterActionInterface;
use Zalt\SnippetsActions\PostActionInterface;

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
        'autofilter',
        'create',
        'edit',
        'index',
        'delete',
    ];

    protected array $modelSnippetCustomMiddleware = [
        SnippetMiddleware::class,
        LegacyCurrentUserMiddleware::class,
        LegacyModelMiddleware::class,
        LegacyController::class,
    ];

    public function createRoute(
        string $name,
        string $path,
        array $middleware = [],
        array $methods = ['GET'],
    )
    {
        return [
            $name => [
                'name' => $name,
                'path' => $path,
                'methods' => $methods,
                'middleware' => $middleware,
            ],
        ];
    }

    public function createBrowseRoutes(string $baseName,
                                       string $controllerClass,
                                       ?string $basePath = null,
                                       string|bool|null $basePrivilege = null,
                                       ?array $pages = null,
                                       ?array $customMiddleware = null,
                                       ?array $parameters = null,
                                       ?array $parameterRoutes = null,
                                       ?array $postRoutes = null,
                                       ?array $parentParameters = null,
                                       bool $genericExport = false,
                                       array $noCsrfRoutes = ['index']): array
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

        if ($genericExport) {
            array_unshift($pages, 'export');
            $postRoutes[] = 'export';
        }

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
            } elseif ($genericExport && $pageName === 'export') {
                $route['path'] .= '[/step/{step:batch|download}]';
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
                if (! in_array($pageName, $noCsrfRoutes)) {
                    array_unshift($route['middleware'], HandlerCsrfMiddleware::class);
                }
            }

            $routes[$baseName . '.' . $pageName] = $route;
        }

        return $routes;
    }
    
    public function createHandlerRoute(
        string $baseName,
        string $controllerClass,
        array $parentParameters = []): array
    {
        // Set basic variables for all routes
        $basePath = '/' . str_replace('.', '/', $baseName);
        $basePrivilege = 'pr.' . $baseName;

        $middleware = [
            SnippetMiddleware::class,
            LegacyCurrentUserMiddleware::class,
            LegacyModelMiddleware::class,
            $controllerClass,
        ];

        $parameters = $controllerClass::$parameters;
        $combinedParameters = [];
        foreach ($parameters as $parameterName => $parameterRegex) {
            $combinedParameters[] = '{' . $parameterName . ':'. $parameterRegex . '}';
        }
        $parameterString = join('/', $combinedParameters);

        // Create the sub routes
        $routes = [];
        foreach ($controllerClass::$actions as $pageName => $actionClass) {
            $interfaces = class_implements($actionClass) ?? [];

            $route = [
                'allowed_methods' => ['GET'],
                'middleware'      => $middleware,
                'name'            => $baseName . '.' . $pageName,
                'options' => [
                    'action'      => $pageName,
                    'controller'  => $controllerClass,
                    'privilege'   => $basePrivilege . '.' . $pageName,
                ],
                'params'          => $parentParameters,
                'path'            => $basePath . '/' . $pageName,
            ];

            if ($pageName === 'index' || $pageName === 'show') {
                $route['path'] = $basePath;
            }
            if (isset($interfaces[ParameterActionInterface::class])) {
                $route['path'] .= '/' . $parameterString;
                $route['params'] = array_merge($route['params'], array_keys($parameters));
            }
            if (isset($interfaces[PostActionInterface::class])) {
                $route['allowed_methods'][] = 'POST';
                if (! isset($interfaces[NoCsrfInterface::class])) {
                    array_unshift($route['middleware'], HandlerCsrfMiddleware::class);
                }
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
                                       ?array $parentParameters = null,
                                       bool $genericExport = false,
                                       array $noCsrfRoutes = ['index']): array
    {
        return $this->createBrowseRoutes(
            $baseName, 
            $controllerClass, 
            $basePath, $basePrivilege,
            $pages, 
            $customMiddleware ?: [
                LegacyCurrentUserMiddleware::class,
                LegacyModelMiddleware::class,
                $controllerClass,
            ],
            $parameters, 
            $parameterRoutes, 
            $postRoutes, 
            $parentParameters,
            $genericExport,
            $noCsrfRoutes);
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

    public function updateRoutes(
        string $baseName,
        ?string $controllerClass = null,
        ?array $pages = null,
    ): array
    {
        if ($pages === null) {
            $pages = $this->defaultPages;
        }
        $routes = [];
        foreach($pages as $pageName) {
            $route = [];
            if ($controllerClass !== null) {
                $route['middleware'] = [
                    LegacyCurrentUserMiddleware::class,
                    $controllerClass,
                ];
            }

            $routes[$baseName . '.' . $pageName] = $route;
        }

        return $routes;
    }
}