<?php

declare(strict_types=1);


namespace Gems\Cache;


use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheFactory
{
    protected string $defaultCacheDirectory = 'data/cache';

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

        $cache = new NullAdapter();
        if (isset($config['cache']) && array_key_exists('adapter', $config['cache'])) {
            switch($config['cache']['adapter']) {
                case 'redis':
                    $dsn = 'redis://localhost';
                    if (isset($config['cache']['dsn'])) {
                        $dsn = $config['cache']['dsn'];
                    }
                    /**
                     * @psalm-suppress UndefinedClass
                     */
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
                    break;

            }
        }

        return new HelperAdapter($cache);
    }
}
