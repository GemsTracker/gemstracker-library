<?php

namespace Gems\Util;

use Psr\Http\Message\ServerRequestInterface;

class RequestUtil
{
    public static function isSecure(ServerRequestInterface $request): bool
    {
        $serverParams = $request->getServerParams();
        if (isset($serverParams['HTTP_X_FORWARDED_SCHEME'])) {
            return strtolower($serverParams['HTTP_X_FORWARDED_SCHEME']) === 'https';
        }
        if (isset($serverParams['REQUEST_SCHEME'])) {
            return strtolower($serverParams['REQUEST_SCHEME']) === 'https';
        }
        if (isset($serverParams['HTTPS'])) {
            return $serverParams['HTTPS'] == '1';
        }
        return false;
    }
}