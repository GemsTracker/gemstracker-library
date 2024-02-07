<?php

namespace Gems\Db\Migration;

use Gems\Db\ResultFetcher;
use Gems\Event\Application\CreateTableMigrationEvent;
use Laminas\Db\Adapter\Adapter;
use Symfony\Component\Finder\Finder;
use Zalt\String\Str;


class TableRepository extends MigrationRepositoryAbstract
{
    protected int $defaultOrder = 1000;

    protected string $migrationTableName = 'gems__migration_logs';

    protected string $modelName = 'databaseTableModel';

    public function createMigrationTable()
    {
        $tablesInfo = $this->getTableInfoFromFiles();
        if (isset($tablesInfo[$this->migrationTableName])) {
            $this->createTable($tablesInfo[$this->migrationTableName]);
            return;
        }
        throw new MigrationException(sprintf('Migration table %s could not be created', $this->migrationTableName));
    }

    public function createTable(array $tableInfo): void
    {
        if (!isset($tableInfo['db'], $tableInfo['sql']) || empty($tableInfo['sql'])) {
            throw new MigrationException('Not enough table info to create table');
        }
        $adapter = $this->databases->getDatabase($tableInfo['db']);
        if (!$adapter instanceof Adapter) {
            throw new MigrationException('Not enough table info to create table');
        }
        $resultFetcher = new ResultFetcher($adapter);
        $start = microtime(true);
        try {
            $resultFetcher->query($tableInfo['sql']);
            $event = new CreateTableMigrationEvent(
                'table',
                1,
                $tableInfo['module'],
                $tableInfo['name'],
                'success',
                $tableInfo['sql'],
                null,
                $start,
                microtime(true),
            );
            $this->eventDispatcher->dispatch($event);
        } catch (\Exception $e) {
            $event = new CreateTableMigrationEvent(
                'table',
                1,
                $tableInfo['module'],
                $tableInfo['name'],
                'error',
                $tableInfo['sql'],
                $e->getMessage(),
                $start,
                microtime(true),
            );
            $this->eventDispatcher->dispatch($event);
            throw new MigrationException($e->getMessage());
        }
    }

    protected function getGroupName(string $name): ?string
    {
        if ($pos = strpos($name,  '__')) {
            return substr($name, 0,  $pos);
        }

        return null;
    }

    public function getDbNamesFromDirs(): array
    {
        $tableDirectories = $this->getTablesDirectories();
        return array_column($tableDirectories, 'db');
    }

    public function getTableInfoFromDb()
    {
        $dbNames = $this->getDbNamesFromDirs();
        $tables = [];
        foreach($dbNames as $dbName) {
            $adapter = $this->databases->getDatabase($dbName);
            if (!$adapter instanceof Adapter) {
                continue;
            }
            $resultFetcher = new ResultFetcher($adapter);
            $sql = 'SHOW FULL TABLES';
            $tableNames = $resultFetcher->fetchAll($sql);

            foreach($tableNames as $tableInfo) {
                list($tableName, $rawTableType) = array_values($tableInfo);
                $tableType = match($rawTableType) {
                    'VIEW' => 'view',
                    default => 'table',
                };
                $id = Str::kebab($tableName);
                $table = [
                    'id' => $id,
                    'name' => $tableName,
                    'module' => $this->getGroupName($tableName),
                    'type' => $tableType,
                    'description' => null,
                    'order' => $this->defaultOrder,
                    'sql' => '',
                    'lastChanged' => null,
                    'location' => null,
                    'db' => $dbName,
                ];

                $tables[$id] = $table;
            }
        }
        return $tables;
    }

    /**
     * Get combined tables from files and db. exists field is added, showing which tables already exist
     *
     * @return array
     */
    public function getInfo(): array
    {
        $tableInfoFromFiles = $this->getTableInfoFromFiles();
        $tableInfoFromDb = $this->getTableInfoFromDb();

        $tablesLog = array_column($this->getLoggedResources('table'), null, 'gml_name');

        $tables = [];
        foreach($tableInfoFromFiles as $tableName => $table) {
            $tables[$tableName] = $table;
            $tables[$tableName]['status'] = 'new';
            if (isset($tableInfoFromDb[$tableName])) {
                $tables[$tableName] = $tableInfoFromDb[$tableName];
                $tables[$tableName]['sql'] = $table['sql'];
                $tables[$tableName]['status'] = 'success';
            }
            if (isset($tablesLog[$tableName])) {
                $matchingLog = $tablesLog[$tableName];
                $tables[$tableName]['status'] = $matchingLog['gml_status'];
                $tables[$tableName]['executed'] = $matchingLog['gml_created'];
                $tables[$tableName]['comment'] = $matchingLog['gml_comment'];
            }
        }

        return $tables + $tableInfoFromFiles;
    }

    public function getTableInfoFromFiles(): array
    {
        $tableDirectories = $this->getTablesDirectories();
        $tables = [];

        foreach($tableDirectories as $tableDirectory) {
            $finder = new Finder();
            $files = $finder->files()->name('*.sql')->in($tableDirectory['path']);

            foreach ($files as $file) {
                $filenameParts = explode('.', $file->getFilenameWithoutExtension());
                $name = $filenameParts[0];
                $id = Str::kebab($name);
                $fileContent = $file->getContents();
                $firstRow = substr($fileContent, 0, strpos($fileContent, "\n"));
                $description = null;
                if (str_starts_with($firstRow, '--')) {
                    $description = trim(substr($firstRow, 2));
                }


                $table = [
                    'id' => $id,
                    'name' => $name,
                    'module' => $this->getGroupName($name),
                    'type' => 'table',
                    'description' => $description,
                    'order' => $this->defaultOrder,
                    'data' => $fileContent,
                    'sql' => $fileContent,
                    'lastChanged' => \DateTimeImmutable::createFromFormat('U', $file->getMTime()),
                    'location' => $file->getRealPath(),
                    'db' => $tableDirectory['db'],
                ];

                if (count($filenameParts) === 2 && is_numeric($filenameParts[1])) {
                    $table['order'] = (int)$filenameParts[1];
                }
                $tables[$id] = $table;
            }
        }

        return $tables;
    }

    public function getTablesDirectories(): array
    {
        return $this->getResourceDirectories('tables');
    }

    public function hasMigrationTable(): bool
    {
        $tableData = $this->getTableInfoFromDb();
        if (isset($tableData[$this->migrationTableName])) {
            return true;
        }
        return false;
    }
}
