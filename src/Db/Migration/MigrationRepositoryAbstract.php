<?php

namespace Gems\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use Gems\Model\IteratorModel;
use Gems\Model\MetaModelLoader;
use Laminas\Db\Sql\Select;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\String\Str;

abstract class MigrationRepositoryAbstract
{
    protected string $defaultDatabase = 'gems';

    protected int $defaultOrder = 1000;

    protected string $modelName = 'migrationModel';

    public function __construct(
        protected readonly array $config,
        protected readonly Databases $databases,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getDbNamesFromConfigs(array $configs): array
    {
        return array_column($configs, 'db');
    }

    public function getIdFromName(string $name): string
    {
        return Str::kebab(str_replace('\\', ' ',$name));
    }

    abstract public function getInfo(): array;

    protected function getLoggedResources(?string $resource = null): array
    {
        $adapter = $this->databases->getDefaultDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $select = $resultFetcher->getSelect('gems__migration_logs');

        if ($resource !== null) {
            $select->where(['gml_type' => $resource]);
        }

        $subSelect = $resultFetcher->getSelect();
        $subSelect->from(['ml1' => 'gems__migration_logs'])
            ->columns(['gml_id_migration'])
            ->join(['ml2' => 'gems__migration_logs'],
                'ml1.gml_name = ml2.gml_name AND ml1.gml_created < ml2.gml_created',
                [],
                Select::JOIN_LEFT,
            )->where->isNull('ml2.gml_created');

        $select->where->in('gml_id_migration', $subSelect);

        return $resultFetcher->fetchAll($select);
    }

    public function getModelName(): string
    {
        return $this->modelName;
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
                        'module' => 'gems',
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
        if (isset($this->config['responseData']['enabled'], $this->config['responseData']['migrations'][$resource]) && $this->config['responseData']['enabled'] === true) {
            $resourceDirectories = array_merge($resourceDirectories, $this->config['responseData']['migrations'][$resource]);
        }

        foreach($resourceDirectories as $key=>$resourceDirectory) {
            if (is_string($resourceDirectory)) {
                if (is_dir($resourceDirectory)) {
                    $resourceDirectories[$key] = [
                        'db' => $this->defaultDatabase,
                        'path' => $resourceDirectory,
                        'module' => 'gems',
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
