<?php

declare(strict_types=1);


namespace Gems\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Cache\CacheItemPoolInterface;

class DoctrineDbalFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return Connection
     * @throws \Doctrine\DBAL\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Connection
    {
        $config = $container->get('config');

        $databaseConfig = $this->getDatabaseConfig($config['db']);
        $connection = DriverManager::getConnection($databaseConfig);

        $cache = $container->get(CacheItemPoolInterface::class);
        if ($cache instanceof CacheItemPoolInterface) {
            $connectionConfig = $connection->getConfiguration();
            $connectionConfig->setResultCache($cache);
        }

        return $connection;
    }

    protected function getDatabaseConfig(array $config)
    {
        if (isset($config['url'])) {
            return [
                'url' => $config['url'],
            ];
        }

        return [
            'driver' => strtolower($config['driver']),
            'host' => $config['host'],
            'user' => $config['username'],
            'password' => $config['password'],
            'dbname' => $config['database'],
        ];
    }
}
