<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response  = $handler->handle($request);
        return $response
            ->withHeader('Strict-Transport-Security', 'frame-ancestors \'none\'')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('X-Frame-Options', 'deny');
    }
}
