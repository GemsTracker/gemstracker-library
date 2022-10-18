<?php

namespace Gems\Factory;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LaminasDbAdapterFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if ($container->get(\PDO::class)) {
            return new Adapter(new Pdo($container->get(\PDO::class)));
        }

        $config = $container->get('config');
        return new Adapter($config['db']);
    }
}