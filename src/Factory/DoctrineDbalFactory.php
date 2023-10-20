<?php

declare(strict_types=1);


namespace Gems\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Gems\Db\ConfigRepository;
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

        $configRepository = new ConfigRepository($config);
        $databaseConfig = $configRepository->getDoctrineConfig();

        $connection = DriverManager::getConnection($databaseConfig);

        $cache = $container->get(CacheItemPoolInterface::class);
        if ($cache instanceof CacheItemPoolInterface) {
            $connectionConfig = $connection->getConfiguration();
            $connectionConfig->setResultCache($cache);
        }

        return $connection;
    }
}
