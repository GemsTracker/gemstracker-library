<?php

namespace Gems\Db;

use Laminas\Db\Adapter\Adapter;
use Psr\Container\ContainerInterface;

class Databases
{
    public const DEFAULT_DATABASE_NAME = 'gems';
    public function __construct(
        protected readonly ContainerInterface $container,
    )
    {}

    public function getDefaultDatabase(): ?Adapter
    {
        return $this->container->get(Adapter::class);
    }

    public function getDatabase(string $name): ?Adapter
    {
        if ($name === static::DEFAULT_DATABASE_NAME) {
            return $this->getDefaultDatabase();
        }
        $containerAlias = 'databaseAdapter' . ucfirst($name);
        if ($this->container->has($containerAlias)) {
            return $this->container->get($containerAlias);
        }

        return null;
    }
}