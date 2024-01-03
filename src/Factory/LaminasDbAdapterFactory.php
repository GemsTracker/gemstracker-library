<?php

namespace Gems\Factory;

use Gems\Db\ConfigRepository;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LaminasDbAdapterFactory implements FactoryInterface
{
    public const ADAPTER_ALIAS_PREFIX = 'databaseAdapter';
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $profiler = null;
        if (isset($_ENV['DB_PROFILE']) && $_ENV['DB_PROFILE'] === '1') {
            $profiler = new Profiler();
        }

        if ($requestedName === Adapter::class || $requestedName === AdapterInterface::class) {
            return $this->getDefaultAdapter($container, $profiler);
        }

        if ($adapter = $this->getAdapterFromConfig($container, $requestedName, $profiler)) {
            return $adapter;
        }

        return $this->getDefaultAdapter($container, $profiler);
    }

    protected function getAdapterFromConfig(ContainerInterface $container, string $requestedName, ?Profiler $profiler): ?Adapter
    {
        $config = $container->get('config');
        $baseName = $requestedName;
        if (str_starts_with($requestedName, static::ADAPTER_ALIAS_PREFIX)) {
            $pos = strlen(static::ADAPTER_ALIAS_PREFIX);
            $baseName = substr($requestedName, $pos-1);
        }
        if (isset($config['databases'][$baseName])) {
            $adapterConfig = $config['databases'][$baseName] + $config['db'];
            return new Adapter($adapterConfig, null, null, $profiler);
        }

        return null;
    }

    protected function getDefaultAdapter(ContainerInterface $container, ?Profiler $profiler): Adapter
    {
        if ($container->get(\PDO::class)) {
            $pdo = $container->get(\PDO::class);
            if ($pdo instanceof \PDO) {
                return new Adapter(new Pdo($container->get(\PDO::class)), null, null, $profiler);
            }
        }

        /**
         * @var ConfigRepository $config
         */
        $config = $container->get(ConfigRepository::class);
        return new Adapter($config->getConfig(), null, null, $profiler);
    }
}