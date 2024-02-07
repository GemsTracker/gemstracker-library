<?php

namespace Gems\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\RunSeedMigrationEvent;
use Gems\Model\MetaModelLoader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\String\Str;

class SeedRepository extends MigrationRepositoryAbstract
{

    protected string $modelName = 'databaseSeedModel';

    public function __construct(
        array $config,
        Databases $databases,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        MetaModelLoader $metaModelLoader,
        protected readonly ProjectOverloader $overloader,
    ) {
        parent::__construct($config, $databases, $translator, $eventDispatcher, $metaModelLoader);
    }

    protected $seedFileTypes = [
        'yml',
        'yaml',
        'json',
    ];

    protected function resolveReferences(array $data, array $references): array
    {
        foreach($data as $rowKey => $row) {
            foreach($row as $column => $value) {
                foreach($references as $reference => $replacement) {
                    if ($value === '{{' . $reference . '}}') {
                        $data[$rowKey][$column] = $replacement;
                    }
                }
            }
        }
        return $data;
    }

    protected function getSeedDataFromFile(SplFileInfo $file): array|null
    {
        $extension = $file->getExtension();

        if ($extension === 'yml' || $extension === 'yaml') {
            return Yaml::parse($file->getContents());
        }
        if ($extension === 'json') {
            return json_decode($file->getContents(), true);
        }

        return null;
    }

    /*protected function getQueriesFromData(Adapter $adapter, array $data): array
    {
        $sql = new Sql($adapter);
        $sqlStatements = [];
        foreach($data as $table => $rows) {
            foreach($rows as $row) {
                $insert = $sql->insert($table);
                $insert->values($row);
                $sqlStatements[$table][] = $sql->buildSqlString($insert);
            }
        }

        return $sqlStatements;
    }*/

    protected function getQueriesFromRows(Adapter $adapter, string $table, array $rows): array
    {
        $sql = new Sql($adapter);
        $sqlStatements = [];
        foreach($rows as $row) {
            $insert = $sql->insert($table);
            $insert->values($row);
            $sqlStatements[] = $sql->buildSqlString($insert);
        }
        return $sqlStatements;
    }

    public function getInfo(): array
    {
        $seedsFromFiles = $this->getSeedsFromFiles();

        $seedsFromClasses = $this->getSeedsFromClasses();

        $seedInfo = array_merge($seedsFromFiles,$seedsFromClasses);

        $seedLogs = array_column($this->getLoggedResources('seed'), null, 'gml_name');

        // Sort by order first and name second.
        uasort($seedInfo, function($a, $b) {
            if ($a['order'] != $b['order']) {
                return $a['order'] - $b['order'];
            }
            return strcmp($a['name'], $b['name']);
        });

        foreach($seedInfo as $seedKey => $seedRow) {
            $seedInfo[$seedKey]['status'] = 'new';
            $seedInfo[$seedKey]['executed'] = null;
            $seedInfo[$seedKey]['duration'] = null;
            $seedInfo[$seedKey]['sql'] = null;
            $seedInfo[$seedKey]['comment'] = null;
            if (isset($seedLogs[$seedRow['name']])) {
                $matchingLog = $seedLogs[$seedRow['name']];
                $seedInfo[$seedKey]['status'] = $matchingLog['gml_status'];
                $seedInfo[$seedKey]['executed'] = $matchingLog['gml_created'];
                $seedInfo[$seedKey]['sql'] = $matchingLog['gml_sql'];
                $seedInfo[$seedKey]['comment'] = $matchingLog['gml_comment'];
            }
        }

        return $seedInfo;
    }

    public function getSeedsFromClasses(): array
    {
        $seedClasses = $this->getResourceClasses('seeds');

        $seeds = [];

        foreach($seedClasses as $seedClassInfo) {
            $seedClassName = $seedClassInfo['class'];
            $id = Str::kebab($seedClassName);
            $seedClass = $this->overloader->create($seedClassName);
            $description = null;
            if (!$seedClass instanceof SeedInterface) {
                throw new MigrationException("$seedClassName is not a valid seed class");
            }
            $data = $seedClass();
            $reflectionClass = new \ReflectionClass($seedClassName);

            $seed = [
                'id' => $id,
                'name' => $seedClassName,
                'module' => $seedClassInfo['module'] ?? 'gems',
                'type' => 'seed',
                'description' => $description,
                'order' => $seedClass->getOrder() ?? $this->defaultOrder,
                'data' => $data,
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', filemtime($reflectionClass->getFileName())),
                'location' => $reflectionClass->getFileName(),
                'db' => $seedClassInfo['db'],
            ];

            $seeds[$id] = $seed;
        }
        return $seeds;
    }

    public function getSeedsFromFiles()
    {
        $directories = $this->getSeedsDirectories();
        $seeds = [];

        $finder = new Finder();
        $searchNames = array_map(function(string $filename) {
            return "*.$filename";
        }, $this->seedFileTypes);

        foreach($directories as $directory) {
            $currentFinder = clone ($finder);
            $files = $currentFinder->files()->name($searchNames)->in($directory['path']);

            foreach ($files as $file) {
                $filenameParts = explode('.', $file->getBaseName());
                $name = $filenameParts[0];
                $id = Str::kebab($name);
                $data = $this->getSeedDataFromFile($file);
                $description = $data['description'] ?? null;
                $seed = [
                    'id' => $id,
                    'name' => $filenameParts[0],
                    'module' => $directory['module'] ?? 'gems',
                    'type' => 'seed',
                    'description' => $description,
                    'order' => $this->defaultOrder,
                    'data' => $data,
                    'lastChanged' => \DateTimeImmutable::createFromFormat('U', $file->getMTime()),
                    'location' => $file->getRealPath(),
                    'db' => $directory['db'],
                ];
                if (count($filenameParts) === 3 && is_numeric($filenameParts[1])) {
                    $seed['order'] = (int)$filenameParts[1];
                }
                $seeds[$id] = $seed;
            }
        }

        return $seeds;
    }

    public function getSeedsDirectories(): array
    {
        return $this->getResourceDirectories('seeds');
    }

    public function runSeed(array $seedInfo)
    {
        if (!isset($seedInfo['db'], $seedInfo['data']) || empty($seedInfo['data'])) {
            throw new \Exception('Not enough info to run seed');
        }
        $adapter = $this->databases->getDatabase($seedInfo['db']);
        if (!$adapter instanceof Adapter) {
            throw new \Exception('Not enough info to run seed');
        }

        $resultFetcher = new ResultFetcher($adapter);

        $start = microtime(true);

        $finalQueries = [];
        $generatedValues = [];

        $connection = $resultFetcher->getAdapter()->getDriver()->getConnection();
        $localTransaction = false;

        try {
            if (!$connection->inTransaction()) {
                $connection->beginTransaction();
                $localTransaction = true;
            }
            foreach($seedInfo['data'] as $seedTable => $seedRows) {
                $sqlQueries = $this->getQueriesFromRows($adapter, $seedTable, $this->resolveReferences($seedRows, $generatedValues));

                foreach($sqlQueries as $index => $sqlQuery) {
                    $resultFetcher->query($sqlQuery);
                    $generatedValues[$seedTable.'.'.$index] = $resultFetcher->getAdapter()->getDriver()->getLastGeneratedValue();
                    $finalQueries[] = $sqlQuery;
                }
            }

            if ($localTransaction) {
                $connection->commit();
            }

            /*foreach($sqlQueriesPerTable as $table => $sqlQueries) {
                foreach($sqlQueries as $index => $sqlQuery) {
                    $sqlQuery = $this->resolveReferences($sqlQuery, $generatedValues);
                    $resultFetcher->query($sqlQuery);
                    $generatedValues[$table.'_'.$index] = $resultFetcher->getAdapter()->getDriver()->getLastGeneratedValue();
                    $finalQueries[] = $sqlQuery;
                }
            }*/

            $event = new RunSeedMigrationEvent(
                'seed',
                1,
                $seedInfo['module'],
                $seedInfo['name'],
                'success',
                join("\n", $finalQueries),
                null,
                $start,
                microtime(true),
            );
            $this->eventDispatcher->dispatch($event);
        } catch(\Exception $e) {
            if ($localTransaction && $connection->inTransaction()) {
                $connection->rollback();
            }
            $event = new RunSeedMigrationEvent(
                'seed',
                1,
                $seedInfo['module'],
                $seedInfo['name'],
                'error',
                join("\n", $finalQueries),
                $e->getMessage(),
                $start,
                microtime(true),
            );
            $this->eventDispatcher->dispatch($event);
            throw new MigrationException(sprintf('Seed %s failed, %s', $seedInfo['name'], $e->getMessage()));
        }
    }
}
