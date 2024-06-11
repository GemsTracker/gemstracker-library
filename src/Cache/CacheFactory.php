<?php

declare(strict_types=1);


namespace Gems\Cache;


use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheFactory
{
    protected string $defaultCacheDirectory = 'data/cache';

    public function __invoke(ContainerInterface $container, string $requestedName, array $options = null): AdapterInterface
    {
        $config = $this->getCacheConfig($container);

        $namespace = $config['namespace'] ?? '';
        $defaultLifetime = $config['default_lifetime'] ?? 0;

        $adapter = $config['adapter'] ?? null;

        switch($adapter) {
            case 'redis':
                $dsn = $config['dsn'] ?? 'redis://localhost';
                /**
                 * @psalm-suppress UndefinedClass
                 */
                $client = RedisAdapter::createConnection($dsn);
                $cache = new RedisAdapter($client, $namespace, $defaultLifetime);
                break;

            case 'file':
                $directory = $config['directory'] ?? $this->defaultCacheDirectory;
                $cache = new FilesystemAdapter($namespace, $defaultLifetime, $directory);
                break;

            case 'array':
                $cache = new ArrayAdapter($defaultLifetime);
                break;

            case 'null':
            default:
                $cache = new NullAdapter();
                break;
        }

        return new HelperAdapter($cache);
    }

    protected function getCacheConfig(ContainerInterface $container): array
    {
        $config = $container->get('config');
        if (isset($config['cache'])) {
            return $config['cache'];
        }
        return [];
    }
}
