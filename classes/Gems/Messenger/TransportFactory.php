<?php

namespace Gems\Messenger;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\Bridge\Redis\Transport\Connection as RedisConnection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\PostgreSqlConnection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;

class TransportFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): TransportInterface
    {
        $config = $container->get('config');
        if (!isset($config['messenger'], $config['messenger']['buses'], $config['messenger']['transports'][$requestedName])) {
            throw new TransportException('No message transport config found');
        }
        $transportConfig = $config['messenger']['transports'][$requestedName];
        if (!isset($transportConfig['dsn'])) {
            throw new TransportException(sprintf('No transport dsn found for %s', $requestedName));
        }
        $dsnParts = explode('://', $transportConfig['dsn']);

        switch($dsnParts[0]) {
            case 'in-memory':
                return $this->getInMemoryTransport($transportConfig);
            case 'redis':
                return $this->getRedisTransport($transportConfig);
            case 'doctrine':
                return $this->getDoctrineTransport($transportConfig, $container);
            case 'sync':
                return $this->getSyncTransport($transportConfig);
            default:
                throw new \Exception(sprintf('Transport %s not supported', $dsnParts[0]));
        }
    }

    protected function getDoctrineTransport(array $transportConfig, ContainerInterface $container)
    {
        if (!class_exists(DoctrineTransport::class)) {
            throw new TransportException('Missing package "symfony/doctrine-messenger". Use `composer req symfony/doctrine-messenger` to fix this.');
        }

        try {
            $entityManagerClass = EntityManagerInterface::class;
            if (isset($transportConfig['entityManager']) && $container->has($transportConfig['entityManager'])) {
                $entityManagerClass = $transportConfig['entityManager'];
            }
            $entityManager = $container->get($entityManagerClass);

            $serializer = new PhpSerializer();
            if (isset($transportConfig['serializer'])) {
                $serializer = new $transportConfig['serializer']();
            }

            $driverConnection = $entityManager->getConnection();

            $useNotify = true;
            $configOptions = [];
            if (isset($transportConfig['options'])) {
                $configOptions = $transportConfig['options'];
                if (isset($transportConfig['options']['use_notify'])) {
                    $useNotify = $transportConfig['options']['use_notify'];
                }
            }

            $configuration = Connection::buildConfiguration($transportConfig['dsn'], $configOptions);

            if ($useNotify && $driverConnection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                $connection = new PostgreSqlConnection($configuration, $driverConnection);
            } else {
                $connection = new Connection($configuration, $driverConnection);
            }

            return new DoctrineTransport($connection, $serializer);
        } catch (\Throwable $exception) {
            throw new TransportException('Missing dependency on the Doctrine ManagerRegistry!', 0, $exception);
        }
    }

    protected function getInMemoryTransport(array $transportConfig): InMemoryTransport
    {
        $serializer = null;
        if (isset($transportConfig['serializer'])) {
            $serializer = new $transportConfig['serializer']();
        }
        return new InMemoryTransport($serializer);
    }

    protected function getRedisTransport(array $transportConfig): RedisTransport
    {
        if (!class_exists(RedisTransport::class)) {
            throw new TransportException('Missing package "symfony/redis-messenger". Use `composer req symfony/redis-messenger` to fix this.');
        }

        return new RedisTransport(RedisConnection::fromDsn($transportConfig['dsn']));
    }

    protected function getSyncTransport(array $transportConfig): SyncTransport
    {

        return new SyncTransport();
    }
}