<?php

namespace Gems\Route;

use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Router\RouteResult;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareGroupMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $groups,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $routeGroup = $routeResult->getMatchedRoute()->getOptions()['routeGroup'] ?? null;

        if (!$routeGroup || empty($this->groups[$routeGroup]['middleware'])) {
            return $handler->handle($request);
        }

        $pipeline = new MiddlewarePipe();
        foreach($this->groups[$routeGroup]['middleware'] as $middlewareClassName) {
            $pipeline->pipe($this->container->get($middlewareClassName));
        }
        return $pipeline->process($request, $handler);
    }
}