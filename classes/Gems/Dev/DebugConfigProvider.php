<?php

namespace Gems\Dev;

use Gems\Factory\DebugBarMiddlewareFactory;
use Middlewares\Debugbar as DebugbarMiddleware;

class DebugConfigProvider
{
    public function __invoke(): array
    {
        if (getenv('APP_DEBUG') === true || getenv('APP_DEBUG') === 1) {
            return [
                'pipeline' => [
                    DebugbarMiddleware::class,
                ],
                'dependencies' => [
                    'factories' => [
                        DebugbarMiddleware::class => DebugBarMiddlewareFactory::class,
                    ],
                ],
            ];
        }

        return [];
    }
}