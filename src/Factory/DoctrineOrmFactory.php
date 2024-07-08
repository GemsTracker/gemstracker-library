<?php

declare(strict_types=1);


namespace Gems\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Cache\CacheItemPoolInterface;

class DoctrineOrmFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return EntityManagerInterface
     * @throws \Doctrine\ORM\ORMException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): EntityManagerInterface
    {
        $connection = $container->get(Connection::class);
        $config = $container->get('config');

        $doctrineConfig = $config['doctrine'] ?? [];
        $paths = array_column($doctrineConfig, 'path');

        $isDevMode = false;
        if (isset($config['app'], $config['app']['env']) && $config['app']['env'] === 'development') {
            $isDevMode = true;
        }

        $cache = $container->get(CacheItemPoolInterface::class);

        $attributeMetadataConfig = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode, null, $cache);
        if (!$isDevMode) {
            $attributeMetadataConfig->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        }
        if (isset($config['doctrine'], $config['doctrine']['proxydir'])) {
            $attributeMetadataConfig->setProxyDir($config['doctrine']['proxydir']);
        }

        $namingStrategy = new UnderscoreNamingStrategy(CASE_LOWER, true);
        $attributeMetadataConfig->setNamingStrategy($namingStrategy);
        $entityManager = new EntityManager($connection, $attributeMetadataConfig);

        return $entityManager;
    }
}
