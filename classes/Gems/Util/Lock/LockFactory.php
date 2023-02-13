<?php

namespace Gems\Util\Lock;

use Gems\Exception;
use Gems\Util\Lock\Storage\CacheLock;
use Gems\Util\Lock\Storage\DatabaseLock;
use Gems\Util\Lock\Storage\FileLock;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LockFactory implements FactoryInterface
{
    public string $defaultLockStorage = FileLock::class;

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): LockInterface
    {
        $storageType = $options['type'] ?? $this->defaultLockStorage;
        $lockStorageClass = $this->getLockStorageClass($storageType);
        $lockStorage = $container->build($lockStorageClass);

        $config = $container->get('config');
        $rootDir = $config['rootDir'];

        return new $requestedName($lockStorage, $rootDir);
    }

    public static function __callStatic(string $type, array $arguments)
    {
        [$container, $requestedName] = $arguments;
        if (!$container instanceof ContainerInterface) {
            throw new Exception(sprintf('Expected %s as argument', ContainerInterface::class));
        }
        return (new self())($container, $requestedName, ['type' => $type]);
    }

    public function getLockStorageClass(string $type): string
    {
        $className = match ($type) {
            CacheLock::class, 'cache', 'cacheLock' => CacheLock::class,
            DatabaseLock::class, 'db', 'database', 'databaseLock' => DatabaseLock::class,
            FileLock::class, 'file', 'fileLock' => FileLock::class,
            default => null,
        };

        if ($className === null) {
            throw new Exception("Lock storage of type '$type' does not exist" );
        }

        return $className;
    }
}