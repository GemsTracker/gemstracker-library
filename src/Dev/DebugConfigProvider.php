<?php

namespace Gems\Dev;

use Gems\Factory\DebugBarMiddlewareFactory;
use Gems\Helper\Env;
use Middlewares\Debugbar as DebugbarMiddleware;

class DebugConfigProvider
{
    public function __invoke(): array
    {
        $debug = (bool)Env::get('APP_DEBUG');
        if ($debug) {
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