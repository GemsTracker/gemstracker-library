<?php

namespace Gems\Factory;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LaminasDbAdapterFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $profiler = null;
        if (isset($_ENV['DB_PROFILE']) && $_ENV['DB_PROFILE'] === '1') {
            $profiler = new Profiler();
        }

        if ($container->get(\PDO::class)) {
            $pdo = $container->get(\PDO::class);
            if ($pdo instanceof \PDO) {
                return new Adapter(new Pdo($container->get(\PDO::class)), null, null, $profiler);
            }
        }

        $config = $container->get('config');
        return new Adapter($config['db'], null, null, $profiler);
    }
}