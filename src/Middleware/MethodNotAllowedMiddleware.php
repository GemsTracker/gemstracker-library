<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function is_array;
use function join;

/**
 * Emit a 405 Method Not Allowed response
 *
 * If the request composes a route result, and the route result represents a
 * failure due to request method, this middleware will emit a 405 response,
 * along with an Allow header indicating allowed methods, as reported by the
 * route result.
 *
 * If no route result is composed, and/or it's not the result of a method
 * failure, it passes handling to the provided handler.
 *
 * @final
 */
class MethodNotAllowedMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult instanceof RouteResult || ! $routeResult->isMethodFailure()) {
            return $handler->handle($request);
        }

        $allowedMethods = $routeResult->getAllowedMethods();
        assert(is_array($allowedMethods));

        return new JsonResponse(
            [
                'error' => sprintf('Method %s not allowed', $request->getMethod())
            ],
            StatusCode::STATUS_METHOD_NOT_ALLOWED,
            [
            'Allow' => join(',', $allowedMethods),
            ],
        );
    }
}
