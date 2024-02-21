<?php

namespace Gems\Session;

use Gems\Cache\CacheFactory;
use Psr\Container\ContainerInterface;

class SessionCacheAdapterFactory extends CacheFactory
{
    public function getCacheConfig(ContainerInterface $container): array
    {
        $config = $container->get('config');

        $cacheConfig = $config['cache'];

        if (isset($config['session']['cache'])) {
            $cacheConfig = $config['session']['cache'] + $config['cache'];
        }

        return $cacheConfig;
    }
}