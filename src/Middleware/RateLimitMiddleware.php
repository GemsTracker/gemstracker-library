<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\Cache\RateLimiter;
use Laminas\Diactoros\Response\EmptyResponse;
use Mezzio\Router\RouteResult;
use RuntimeException;
use Mezzio\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Limit the amount of requests that are possible on this endpoint, or api in general
 *
 * Class RateLimitMiddleware
 * @package Auth\Middleware
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    protected int $decayTimeInSeconds = 60;

    protected int $maxAttempts = 120;


    public function __construct(
        protected readonly RateLimiter $limiter
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->getRequestKey($request);

        if ($this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            return new EmptyResponse(429);
        }

        $this->limiter->hit($key, $this->decayTimeInSeconds);

        return $handler->handle($request);
    }

    /**
     * Get the request cache key based on either the login details or the IP
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getRequestKey(ServerRequestInterface $request)
    {

        // TODO Get username from auth class.. preferred in plain, not in user object
        $username = $request->getAttribute('auth_username');
        $group = $request->getAttribute('auth_group');
        $request->getAttribute('auth_username');

        if ($username !== null && $group !== null) {
            return sha1($username . UserRepository::$delimiter . $group . '.rate-limit');
        }

        $routeResult = $request->getAttribute(RouteResult::class);
        $route = $routeResult->getMatchedRoute();

        $server = $request->getServerParams();

        // TODO server params from request object
        if ($route instanceof Route && isset($server['REMOTE_ADDR'])) {
            return sha1($route->getName() . $server['REMOTE_ADDR']  . '.rate-limit');
        }

        throw new RuntimeException('Unable to generate request key.');
    }
}
