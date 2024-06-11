<?php

namespace GemsTest;

use Laminas\ConfigAggregator\ConfigAggregator;

class TestConfigProvider
{
    public function __invoke(): array
    {
        return [
            ConfigAggregator::ENABLE_CACHE => false,
            'cache' => [
                'adapter' => 'array',
            ],
            'debug' => true,
            'email' => [
                'dsn' => 'null://null',
                'site' => 'test@gemstracker.test',
            ],
            'mezzio-session-cache' => [
                'cache_item_pool_service' => \Gems\Session\SessionCacheAdapter::class,
                'cookie_name' => '__Secure-gems_session',
                'cookie_secure' => true,
                'cookie_http_only' => true,
                'cookie_same_site' => 'Strict',
            ],

        ];
    }
}