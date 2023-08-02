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
 * Limit the amount of requests that are possible on this endpoint, or api in
 * general. Note that rate limiting will not work if no cache is configured,
 * but we have no way of checking that here.
 *
 * Class RateLimitMiddleware
 * @package Gems\Middleware
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private string $routeName;

    private string $requestMethod;

    protected int $decayTimeInSeconds = 60;

    protected int $maxAttempts = 120;


    public function __construct(
        protected readonly RateLimiter $limiter,
        protected readonly array $config,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->prepare($request)) {
            throw new RuntimeException('Unable to prepare for rate limiting.');
        }

        if (!$this->needsRatelimit()) {
            return $handler->handle($request);
        }

        $key = $this->getRequestKey($request);

        if ($this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            return new EmptyResponse(429);
        }

        $this->limiter->hit($key, $this->decayTimeInSeconds);

        return $handler->handle($request);
    }

    /**
     * Fill some variables we need to do our work.
     * Returns false if anything is not in order.
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    private function prepare(ServerRequestInterface $request): bool
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        $route = $routeResult->getMatchedRoute();
        if (!$route instanceof Route) {
            return false;
        }
        $this->routeName = $route->getName();
        $this->requestMethod = $request->getMethod();

        return true;
    }

    /**
     * Get the request cache key based on the route and either the login
     * details or the IP address.
     *
     * @param ServerRequestInterface $request
     */
    protected function getRequestKey(ServerRequestInterface $request): string
    {
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

        if (!is_null($sessionPart)) {
            $key = implode('.', [$this->routeName, $this->requestMethod, $sessionPart, 'rate-limit']);
            return sha1($key);
        }

        throw new RuntimeException('Unable to generate request key.');
    }

    /**
     * Return true if we need to do rate limiting for this request.
     * If we do, we also set the configuration for the rate limit.
     *
     * @return boolean
     */
    protected function needsRatelimit(): bool
    {
        $keys = $this->getConfigKeys();
        foreach ($keys as $key) {
            if (!isset($this->config['ratelimit'][$key])) {
                continue;
            }

            return $this->parseConfig($this->config['ratelimit'][$key]);
        }

        return false;
    }

    /**
     * Return an array of config keys we have to check in the rate limit config.
     * We check the entire route name tree, with and without the request method
     * appended, as well as defaults, so for a GET request to the 'foo.bar'
     * route, we'll generate a list like this:
     * - foo.bar.GET
     * - foo.bar
     * - foo.GET
     * - foo
     * - default.GET
     * - default
     *
     * @return array<string>
     */
    private function getConfigKeys(): array
    {
        $parts = explode('.', $this->routeName);
        for ($i = count($parts); $i > 0; $i--) {
            $key = implode('.', array_slice($parts, 0, $i));
            $configKeys[] = $key . '.' . $this->requestMethod;
            $configKeys[] = $key;
        }
        $configKeys[] = 'default.' . $this->requestMethod;;
        $configKeys[] = 'default';

        return $configKeys;
    }

    /**
     * Parse a ratelimit config value. The value can either be false to
     * disable rate limiting for the route (and its children) or a string
     * like '5/60', defined as <number of requests> per <time in seconds>.
     *
     * Also returns false if the value is incorrectly set.
     *
     * @param string|false $configValue
     * @return boolean
     */
    private function parseConfig(string|false $configValue): bool
    {
        if ($configValue === false) {
            return false;
        }
        $values = explode('/', $configValue, 2);
        if (count($values) != 2) {
            return false;
        }
        $this->maxAttempts = intval($values[0]);
        $this->decayTimeInSeconds = intval($values[1]);

        return true;
    }
}
