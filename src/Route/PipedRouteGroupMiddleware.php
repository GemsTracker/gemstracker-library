<?php

namespace Gems\Route;

use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteResult;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PipedRouteGroupMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly MiddlewareFactoryInterface $middlewareFactory,
        private readonly array $groups,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        if ($routeResult->isFailure()) {
            return $handler->handle($request);
        }

        $routeGroup = $routeResult->getMatchedRoute()->getOptions()['routeGroup'] ?? null;

        if (!$routeGroup || empty($this->groups[$routeGroup]['middleware'])) {
            return $handler->handle($request);
        }

        $routeMiddleware = $this->middlewareFactory->prepare($this->groups[$routeGroup]['middleware']);
        return $routeMiddleware->process($request, $handler);
    }
}