<?php

namespace Gems\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClientIpMiddleware implements MiddlewareInterface
{
    public const CLIENT_IP_ATTRIBUTE = 'clientIp';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        if (isset($serverParams['REMOTE_ADDR'])) {
            $request = $request->withAttribute(static::CLIENT_IP_ATTRIBUTE, $serverParams['REMOTE_ADDR']);
        }

        return $handler->handle($request);
    }
}