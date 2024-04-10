<?php

namespace Gems\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\RequestUtil;

class ClientIpMiddleware implements MiddlewareInterface
{
    public const CLIENT_IP_ATTRIBUTE = 'clientIp';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ipAddress = RequestUtil::getClientIp($request);
        if ($ipAddress) {
            $request = $request->withAttribute(static::CLIENT_IP_ATTRIBUTE, $ipAddress);
        }

        return $handler->handle($request);
    }
}