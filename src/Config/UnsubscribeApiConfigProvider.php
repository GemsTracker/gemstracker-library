<?php

namespace Gems\Config;

use Gems\Api\Middleware\ApiRequestExceptionMiddleware;
use Gems\Api\RestModelConfigProviderAbstract;
use Gems\Communication\Handler\UnsubscribeHandler;
use Gems\Middleware\AuditLogMiddleware;
use Gems\Middleware\LocaleMiddleware;
use Gems\Middleware\RateLimitMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;

class UnsubscribeApiConfigProvider extends RestModelConfigProviderAbstract
{
    public function __invoke(): array
    {
        return [
            'routes' => [
                ...$this->createRoute(
                    name: 'unsubscribe',
                    path: '/api/unsubscribe',
                    handler: UnsubscribeHandler::class,
                    allowedMethods: ['POST'],
                    middleware: [
                        SecurityHeadersMiddleware::class,
                        RateLimitMiddleware::class,
                        LocaleMiddleware::class,
                        AuditLogMiddleware::class,
                        ApiRequestExceptionMiddleware::class,
                    ],
                ),
            ],
            'ratelimit' => [
                'api.unsubscribe.POST' => '5/3600',
            ]
        ];
    }
}
