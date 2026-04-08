<?php

namespace Gems\Middleware;

use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteResult;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LazyRouteMiddlewareMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly MiddlewareFactoryInterface $middlewareFactory,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        $options = $routeResult->getMatchedRoute()->getOptions();

        if (!isset($options['middleware'])) {
            return $handler->handle($request);
        }

        $middleware = $this->middlewareFactory->prepare($options['middleware']);
        return $middleware->process($request, $handler);
    }
}