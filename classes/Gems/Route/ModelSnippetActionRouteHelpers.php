<?php

namespace Gems\Route;

use Gems\Legacy\LegacyController;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;

class ModelSnippetActionRouteHelpers
{
    protected array $defaultPages = ['index', 'autofilter', 'show', 'create', 'edit', 'delete'];

    protected array $modelSnippetCustomFirmware = [
        SecurityHeadersMiddleware::class,
        LegacyCurrentUserMiddleware::class,
        LegacyController::class,
    ];

    public function createBrowseRoutes(string $baseName, string $basePath, string $basePrivilege, string $controllerClass, ?array $pages = null, ?array $customMiddleware = null): array
    {
        if ($pages === null) {
            $pages = $this->defaultPages;
        }

        if ($customMiddleware === null) {
            $customMiddleware = $this->modelSnippetCustomFirmware;
        }

        $routes = [];

        if (in_array('index', $pages)) {
            $routes[$baseName . '.index'] = [
                'name' => $baseName . '.index',
                'path' => $basePath . '/index',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET', 'POST'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'index',
                    'privilege' => $basePrivilege . '.index',
                ]
            ];
        }

        if (in_array('autofilter', $pages)) {
            $routes[$baseName . '.autofilter'] = [
                'name' => $baseName . '.autofilter',
                'path' => $basePath . '/autofilter',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'autofilter',
                    'privilege' => $basePrivilege . '.autofilter',
                ]
            ];
        }

        if (in_array('show', $pages)) {
            $routes[$baseName . '.show'] = [
                'name' => $baseName . '.show',
                'path' => $basePath . '/show/{id:\d+}',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'show',
                    'privilege' => $basePrivilege . '.show',
                ]
            ];
        }

        if (in_array('create', $pages)) {
            $routes[$baseName . '.create'] = [
                'name' => $baseName . '.create',
                'path' => $basePath . '/create',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'create',
                    'privilege' => $basePrivilege . '.create',
                ]
            ];
        }

        if (in_array('edit', $pages)) {
            $routes[$baseName . '.edit'] = [
                'name' => $baseName . '.edit',
                'path' => $basePath . '/edit/{id:\d+}',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'edit',
                    'privilege' => $basePrivilege . '.edit',
                ]
            ];
        }

        if (in_array('delete', $pages)) {
            $routes[$baseName . '.delete'] = [
                'name' => $baseName . '.delete',
                'path' => $basePath . '/delete/{id:\d+}',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'delete',
                    'privilege' => $basePrivilege . '.delete',
                ]
            ];
        }

        if (in_array('export', $pages)) {
            $routes[$baseName . '.export'] = [
                'name' => $baseName . '.export',
                'path' => $basePath . '/export',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'export',
                    'privilege' => $basePrivilege . '.export',
                ]
            ];
        }

        if (in_array('import', $pages)) {
            $routes[$baseName . '.import'] = [
                'name' => $baseName . '.import',
                'path' => $basePath . '/import',
                'middleware' => $customMiddleware,
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => $controllerClass,
                    'action' => 'import',
                    'privilege' => $basePrivilege . '.import',
                ]
            ];
        }

        return $routes;
    }
}