<?php

namespace Gems\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\RunSeedMigrationEvent;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Source\Factory;
use Laminas\Db\Sql\Sql;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Zalt\Loader\ProjectOverloader;

class SeedRepository extends MigrationRepositoryAbstract
{
    public string $lastSql = '';

    protected string $modelName = 'databaseSeedModel';

    protected readonly ResultFetcher $resultFetcher;

    protected array $tableKeys = [];

    public function __construct(
        array $config,
        Databases $databases,
        EventDispatcherInterface $eventDispatcher,
        protected readonly ProjectOverloader $overloader,
    ) {
        parent::__construct($config, $databases, $eventDispatcher);

        $adapter = $this->databases->getDefaultDatabase();
        $this->resultFetcher = new ResultFetcher($adapter);
    }

    protected array $seedFileTypes = [
        'yml',
        'yaml',
        'json',
    ];

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

    public function getSeedInfo(string $seedFile): array
    {
        if (class_exists($seedFile)) {
            return $this->getSeedClassInfo($seedFile);
        }

        if (file_exists($seedFile)) {
            $file = new SplFileInfo($seedFile, '', '');
            return $this->getSeedFileInfo($file);
        }

        return [];
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
        $index = 0;
        foreach($rows as $row) {
            $index++;
            $keyFilter = $this->getKeyFilter($adapter, $table, $row);

            if ($keyFilter !== null) {
                $check = $sql->select($table)->where($keyFilter);
                if ($this->resultFetcher->fetchRow($check)) {
                    continue;
                }
            }

            $insert = $sql->insert($table);
            $insert->values($row);
            $sqlStatements[$index] = $sql->buildSqlString($insert);
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

    protected function getKeyFilter(Adapter $adapter, $tableName, $row): array|null
    {
        $keysets = $this->getTableKeysets($adapter, $tableName);
        $filter = [];
        foreach($keysets as $keys) {
            $skipFilter = false;
            foreach($keys as $key) {
                if (isset($row[$key])) {
                    $filter[$key] = $row[$key];
                } else {
                    $skipFilter = true;
                }
            }
            if ($filter && (! $skipFilter)) {
                // Return first searchable value
                return $filter;
            }
        }

        return null;
    }

    protected function getTableKeysets(Adapter $adapter, string $tableName): array
    {
        if (!isset($this->tableKeys[$tableName])) {
            $metaData = Factory::createSourceFromAdapter($adapter);
            $table = $metaData->getTable($tableName);
            $keys = [];
            foreach($table->getConstraints() as $constraint) {
                if ($constraint->getType() === 'PRIMARY KEY' || $constraint->getType() === 'UNIQUE') {
                    $keys[] = $constraint->getColumns();
                }
            }
            $this->tableKeys[$tableName] = $keys;
        }

        return $this->tableKeys[$tableName];
    }

    public function getSeedsFromClasses(): array
    {
        $seedClasses = $this->getResourceClasses('seeds');

        $seeds = [];

        foreach($seedClasses as $seedClassInfo) {
            $seedClassName = $seedClassInfo['class'];

            $seed = $this->getSeedClassInfo($seedClassName, $seedClassInfo['module'], $seedClassInfo['db']);
            $seeds[$seed['id']] = $seed;
        }
        return $seeds;
    }

    protected function getSeedClassInfo(string $seedClassName, string $module = 'gems', string|null $db = null): array
    {
        $id = $this->getIdFromName($seedClassName);
        $seedClass = $this->overloader->create($seedClassName);
        $description = null;
        if (!$seedClass instanceof SeedInterface) {
            throw new MigrationException("$seedClassName is not a valid seed class");
        }
        $data = $seedClass();
        $order = $seedClass->getOrder();
        if ($order === 0) {
            $order = $this->defaultOrder;
        }
        $reflectionClass = new \ReflectionClass($seedClassName);

        return [
            'id' => $id,
            'name' => $seedClassName,
            'module' => $module,
            'type' => 'seed',
            'description' => $description,
            'order' => $order,
            'data' => $data,
            'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime($reflectionClass->getFileName())),
            'location' => $reflectionClass->getFileName(),
            'db' => $db ?? $this->defaultDatabase,
        ];
    }

    public function getSeedFileInfo(SplFileInfo $file, string $module = 'gems', string|null $db = null): array
    {
        $filenameParts = explode('.', $file->getBaseName());
        $name = $filenameParts[0];
        $id = $this->getIdFromName($name);
        $data = $this->getSeedDataFromFile($file);
        $description = $data['description'] ?? null;
        $seed = [
            'id' => $id,
            'name' => $filenameParts[0],
            'module' => $module,
            'type' => 'seed',
            'description' => $description,
            'order' => $this->defaultOrder,
            'data' => $data,
            'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) $file->getMTime()),
            'location' => $file->getRealPath(),
            'db' => $db ?? $this->defaultDatabase,
        ];
        if (count($filenameParts) === 3 && is_numeric($filenameParts[1])) {
            $seed['order'] = (int)$filenameParts[1];
        }
        return $seed;
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
                $seed = $this->getSeedFileInfo($file, $directory['module'], $directory['db']);
                $seeds[$seed['id']] = $seed;
            }
        }

        return $seeds;
    }

    public function getSeedsDirectories(): array
    {
        return $this->getResourceDirectories('seeds');
    }

    protected function resolveReferences(array $data, array $references): array
    {
        foreach($data as $rowKey => $row) {
            foreach($row as $column => $value) {
                foreach($references as $reference => $replacement) {
                    if (isset($data[$rowKey][$column]) && $data[$rowKey][$column] === '{{' . $reference . '}}') {
                        $data[$rowKey][$column] = $replacement;
                    }
                }
                if (is_string($data[$rowKey][$column] ?? '') && str_starts_with($data[$rowKey][$column] ?? '', '{{')) {
                    unset($data[$rowKey]);
                }
            }
        }

        return $data;
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
                    $this->lastSql = $sqlQuery;
                    $resultFetcher->query($sqlQuery);
                    $generatedValues[$seedTable.'.'.$index] = $resultFetcher->getAdapter()->getDriver()->getLastGeneratedValue();
                    $finalQueries[] = $sqlQuery;
                }
            }

            if ($localTransaction) {
                $connection->commit();
            }

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
