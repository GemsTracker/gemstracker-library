<?php

namespace Gems\Factory;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PdoFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /**
         * @var $dbal Connection
         */
        $dbal = $container->get(Connection::class);
        $pdo = $dbal->getNativeConnection();

        return $pdo;
    }
}