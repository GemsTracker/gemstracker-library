<?php

declare(strict_types=1);


namespace Gems\Cache;


use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class CacheFactory
{
    protected $defaultCacheDirectory = 'data/cache';

    public function __invoke(ContainerInterface $container, string $requestedName, array $options = null): AdapterInterface
    {
        $config = $container->get('config');

        $namespace = '';
        if (isset($config['cache']['namespace'])) {
            $namespace = $config['cache']['namespace'];
        }
        $defaultLifetime = 0;
        if (isset($config['cache']['default_lifetime'])) {
            $defaultLifetime = $config['cache']['default_lifetime'];
        }

        if (isset($config['cache'], $config['cache']['adapter'])) {
            switch($config['cache']['adapter']) {
                case 'redis':
                    $dsn = 'redis://localhost';
                    if (isset($config['cache']['dsn'])) {
                        $dsn = $config['cache']['dsn'];
                    }
                    $client = RedisAdapter::createConnection($dsn);
                    $cache = new RedisAdapter($client, $namespace, $defaultLifetime);
                    break;

                case 'file':
                    $directory = $this->defaultCacheDirectory;
                    if (isset($config['cache']['directory'])) {
                        $directory = $config['cache']['directory'];
                    }
                    $cache = new FilesystemAdapter($namespace, $defaultLifetime, $directory);
                    break;

                case 'null':
                default:
                    $cache = new NullAdapter();

            }
        }

        return new TagAwareAdapter($cache);
    }
}
