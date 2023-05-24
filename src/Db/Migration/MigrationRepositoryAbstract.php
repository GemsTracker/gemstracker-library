<?php

namespace Gems\Db\Migration;

use Db\Migration\TableRepositoryTest;
use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use GemsTest\TestData\Db\SeedRepository\TestPhpSeed;
use Laminas\Db\Sql\Expression;
use MUtil\Translate\Translator;
use Psr\EventDispatcher\EventDispatcherInterface;

class MigrationRepositoryAbstract
{
    protected string $defaultDatabase = 'gems';

    protected int $defaultOrder = 1000;

    public function __construct(
        protected readonly array $config,
        protected readonly Databases $databases,
        protected readonly Translator $translator,
        protected readonly EventDispatcherInterface $eventDispatcher,
    )
    {}

    public function getDbNamesFromConfigs(array $configs): array
    {
        return array_column($configs, 'db');
    }

    protected function getLoggedResources(?string $resource = null): array
    {
        $adapter = $this->databases->getDefaultDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $select = $resultFetcher->getSelect('gems__migration_logs');

        if ($resource !== null) {
            $select->where(['gml_type' => $resource]);
        }

        $subSelect = $resultFetcher->getSelect('gems__migration_logs')
            ->columns([new Expression('MAX(gml_created)')])
            ->group(['gml_name']);
        $select->where->in('gml_id_migration', $subSelect);

        return $resultFetcher->fetchAll($select);
    }

    protected function getResourceClasses(string $resource): array
    {
        $resources = $this->config['migrations'][$resource] ?? [];

        foreach($resources as $key=>$resource) {
            if (is_string($resource)) {
                if (class_exists($resource)) {
                    $resources[$key] = [
                        'db' => $this->defaultDatabase,
                        'class' => $resource,
                    ];
                    continue;
                }
                // resource is not a class
                unset($resources[$key]);
            }
            if (!isset($resource['class']) || !class_exists($resource['class'])) {
                // not a class, or class does not exist
                unset($resources[$key]);
            }
        }

        return $resources;
    }

    protected function getResourceDirectories(string $resource): array
    {
        $resourceDirectories = $this->config['migrations'][$resource] ?? [];

        foreach($resourceDirectories as $key=>$resourceDirectory) {
            if (is_string($resourceDirectory)) {
                if (is_dir($resourceDirectory)) {
                    $resourceDirectories[$key] = [
                        'db' => $this->defaultDatabase,
                        'path' => $resourceDirectory,
                    ];
                    continue;
                }
                // Dir does not exist
                unset($resourceDirectories[$key]);
            }
            if (isset($resourceDirectory['class'])) {
                // not a dir, but a php class
                unset($resourceDirectories[$key]);
            }
        }

        return $resourceDirectories;
    }
}
