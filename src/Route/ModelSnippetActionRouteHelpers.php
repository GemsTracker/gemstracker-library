<?php

namespace Gems\Route;

use Gems\Legacy\LegacyController;
use Gems\Middleware\HandlerCsrfMiddleware;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\LegacyModelMiddleware;
use Gems\SnippetsLoader\SnippetMiddleware;
use Laminas\Stdlib\ArrayUtils\MergeReplaceKey;
use Zalt\SnippetsActions\NoCsrfInterface;
use Zalt\SnippetsActions\ParameterActionInterface;
use Zalt\SnippetsActions\PostActionInterface;
use Zalt\SnippetsHandler\ActionNotSnippetActionException;

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
        array $options = [],
        array $params = [],
    )
    {
        return [
            $name => [
                'name' => $name,
                'path' => $path,
                'methods' => $methods,
                'middleware' => $middleware,
                'options' => $options,
                'params' => $params,
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
                                       bool $genericImport = false,
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

        if ($genericImport) {
            array_unshift($pages, 'import');
            $postRoutes[] = 'import';
        }
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
                if ('autofilter' === $pageName) {
                    $route['options']['privilege'] = $basePrivilege . '.index';
                } else {
                    $route['options']['privilege'] = $basePrivilege . '.' . $pageName;
                }
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
        ?string $basePath = null): array
    {
        if (! (property_exists($controllerClass, 'actions') && property_exists($controllerClass, 'parameters'))) {
            throw new ActionNotSnippetActionException("Class $controllerClass does not have static actions and parameters properties!");
        }

        // Set basic variables for all routes
        $parentParameters = [];
        if ($basePath === null) {
            $baseParts = explode('.', $baseName);
            if (count($baseParts) > 1) {
                $childDef = array_pop($baseParts);
            } else {
                $childDef = false;
            }
            $basePath = '/' . implode( '/', $baseParts);

            $parentParameters = [];
            if (property_exists($controllerClass, 'parentParameters')) {
                $parentParameters = $controllerClass::$parentParameters;
                foreach ($parentParameters as $parameterName => $parameterRegex) {
                    $basePath .= '/{' . $parameterName . ':'. $parameterRegex . '}';
                }
            }

            if ($childDef !== false) {
                $basePath .= '/' . $childDef;
            }
        }

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

//        $parentParameters = [];
//        if (property_exists($controllerClass, 'parentParameters')) {
//            $parentParameters = $controllerClass::$parentParameters;
//        }

        $parameterString = join('/', $combinedParameters);
        $optionalParameters = null;
        if (property_exists($controllerClass, 'optionalParameters')) {
            $optionalParameters = $controllerClass::$optionalParameters;
            $optionalParameterString = '';
            foreach (array_reverse($optionalParameters) as $parameterName => $parameterRegex) {
                $optionalParameterString = '[/{' . $parameterName . ':'. $parameterRegex . '}' . $optionalParameterString . ']';
            }
            $parameterString .= $optionalParameterString;
        }

        // Create the sub routes
        $routes = [];
        foreach ($controllerClass::$actions as $pageName => $actionClass) {
            if (!class_exists($actionClass)) {
                throw new \Exception(sprintf('Action class "%s" does not exist', $actionClass));
            }
            $interfaces = class_implements($actionClass);

            $pagePrivilege = 'autofilter' == $pageName ? 'index' : $pageName;

            $route = [
                'allowed_methods' => ['GET'],
                'middleware'      => $middleware,
                'name'            => $baseName . '.' . $pageName,
                'options' => [
                    'action'      => $pageName,
                    'controller'  => $controllerClass,
                    'privilege'   => $basePrivilege . '.' . $pagePrivilege,
                ],
                'params'          => array_keys($parentParameters),
                'path'            => $basePath . '/' . $pageName,
            ];

            if ($pageName === 'index' || $pageName === 'show') {
                $route['path'] = $basePath;
            }
            if (isset($interfaces[ParameterActionInterface::class])) {
                $route['path'] .= '/' . $parameterString;
                $route['params'] = array_merge($route['params'], array_keys($parameters));
                if (property_exists($controllerClass, 'optionalParameters')) {
                    $route['params'] = array_merge($route['params'], array_keys($controllerClass::$optionalParameters));
                }
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
                                       bool $genericImport = false,
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
            $genericImport,
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
                $route['middleware'] = new MergeReplaceKey([
                    LegacyCurrentUserMiddleware::class,
                    $controllerClass,
                ]);
            }

            $routes[$baseName . '.' . $pageName] = $route;
        }

        return $routes;
    }
}