<?php

declare(strict_types=1);

namespace Gems\Console;

use Mezzio\Exception\InvalidArgumentException;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Psr\Container\ContainerInterface;

class ConsoleAppRoutes
{
    public function __construct(
        ContainerInterface $container,
        MiddlewareFactoryInterface $middlewareFactory,
        array $config,
    )
    {
        /**
         * @var RouteCollector $routeCollector
         */
        $routeCollector = $container->get(RouteCollector::class);

        $routes = $config['routes'] ?? [];

        foreach ($routes as $key => $spec) {
            if (! isset($spec['path']) || ! isset($spec['middleware'])) {
                continue;
            }

            $methods = Route::HTTP_METHOD_ANY;
            if (isset($spec['allowed_methods'])) {
                $methods = $spec['allowed_methods'];
                if (! is_array($methods)) {
                    throw new InvalidArgumentException(sprintf(
                        'Allowed HTTP methods for a route must be in form of an array; received "%s"',
                        gettype($methods)
                    ));
                }
            }

            $name  = $spec['name'] ?? (is_string($key) ? $key : null);
            $middleware = $middlewareFactory->prepare($spec['middleware']);
            $route = $routeCollector->route(
                $spec['path'],
                $middleware,
                $methods,
                $name
            );

            if (isset($spec['options'])) {
                $options = $spec['options'];
                if (! is_array($options)) {
                    throw new InvalidArgumentException(sprintf(
                        'Route options must be an array; received "%s"',
                        gettype($options)
                    ));
                }

                $route->setOptions($options);
            }
        }
    }
}