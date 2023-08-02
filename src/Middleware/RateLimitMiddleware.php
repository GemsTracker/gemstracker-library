<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\AuthNew\Adapter\AuthenticationIdentityInterface;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Cache\RateLimiter;
use Laminas\Diactoros\Response\EmptyResponse;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

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
     * Get the request cache key based on the route and either the login
     * details or the IP address.
     *
     * @param ServerRequestInterface $request
     */
    protected function getRequestKey(ServerRequestInterface $request): string
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        $route = $routeResult->getMatchedRoute();
        $method = $request->getMethod();

        // Now we check if this is an authenticated session.
        $currentIdentity = $request->getAttribute(AuthenticationMiddleware::CURRENT_IDENTITY_ATTRIBUTE);
        if (is_null($currentIdentity)) {
            $currentIdentity = $request->getAttribute(AuthenticationMiddleware::CURRENT_IDENTITY_WITHOUT_TFA_ATTRIBUTE);
        }

        // Either use the username or the IP address as session identifier in the hash.
        $sessionPart = null;
        if ($currentIdentity instanceOf AuthenticationIdentityInterface) {
            // Use the username as session part.
            $sessionPart = $currentIdentity->getLoginName();
        } else {
            // Use the client IP address as session part.
            $sessionPart = $request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE);

        }

        if ($route instanceof Route && !is_null($sessionPart)) {
            $key = implode('.', [$route->getName(), $method, $sessionPart, 'rate-limit']);
            return sha1($key);
        }

        throw new RuntimeException('Unable to generate request key.');
    }
}
