<?php

namespace Gems\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\CreateTableMigrationEvent;
use Gems\Model\IteratorModel;
use Laminas\Db\Adapter\Adapter;
use MUtil\Model\ModelAbstract;
use MUtil\Translate\Translator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Zalt\Model\MetaModelInterface;


class TableRepository extends MigrationRepositoryAbstract
{
    protected int $defaultOrder = 1000;



    public function createTable(array $tableInfo): void
    {
        if (!isset($tableInfo['db'], $tableInfo['sql']) || empty($tableInfo['sql'])) {
            throw new \Exception('Not enough table info to create table');
        }
        $adapter = $this->databases->getDatabase($tableInfo['db']);
        if (!$adapter instanceof Adapter) {
            throw new \Exception('Not enough table info to create table');
        }
        $resultFetcher = new ResultFetcher($adapter);
        $event = new CreateTableMigrationEvent(
            'table',
            $tableInfo['name'],
            $tableInfo['group'],
            $tableInfo['name'],
            'sucess',
            $tableInfo['sql'],
            microtime(true),
        );
        $resultFetcher->query($tableInfo['sql']);
        $event->setEnd();

        $this->eventDispatcher->dispatch($event);
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
                $table = [
                    'name' => $tableName,
                    'group' => $this->getGroupName($tableName),
                    'type' => $tableType,
                    'description' => null,
                    'order' => $this->defaultOrder,
                    'sql' => '',
                    'lastChanged' => null,
                    'location' => null,
                    'db' => $dbName,
                ];

                $tables[$tableName] = $table;
            }
        }
        return $tables;
    }

    /**
     * Get combined tables from files and db. exists field is added, showing which tables already exist
     *
     * @return array
     */
    public function getTableInfo(): array
    {
        $tableInfoFromFiles = $this->getTableInfoFromFiles();
        $tableInfoFromDb = $this->getTableInfoFromDb();

        $tables = [];
        foreach($tableInfoFromFiles as $tableName => $table) {
            if (isset($tableInfoFromDb[$tableName])) {
                $tables[$tableName] = $tableInfoFromDb[$tableName];
                $tables[$tableName]['exists'] = true;
                continue;
            }
            $tables[$tableName] = $table;
            $tables[$tableName]['exists'] = false;
        }

        return $tables + $tableInfoFromFiles;
    }

    public function getDbTableModel(): ModelAbstract
    {
        $model = new IteratorModel('databaseTables', $this->getTableInfo());
        $model->set('group', [
            'maxlength', 40, 'type',
            MetaModelInterface::TYPE_STRING,
        ]);
        $model->set('name', [
            'key' => true,
            'maxlength' => 40,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $model->set('type', [
            'maxlength' => 40,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $model->set('order', [
            'decimals' => 0,
            'default' => 1000,
            'maxlength' => 6,
            'type' => MetaModelInterface::TYPE_NUMERIC
        ]);
        $model->set('defined', [
            'type' => MetaModelInterface::TYPE_NUMERIC
        ]);
        $model->set('exists', [
            'type' => MetaModelInterface::TYPE_NUMERIC
        ]);
        $model->set('state', [
            'type' => MetaModelInterface::TYPE_NUMERIC
        ]);
        $model->set('sql', [
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $model->set('lastChanged', [
            'type' => MetaModelInterface::TYPE_DATETIME
        ]);
        $model->set('location', [
            'maxlength' => 12,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $model->set('state', [
            'multiOptions' => [
                'created' => $this->translator->_('created'),
                'notCreated' => $this->translator->_('not created'),
                'unknown' => $this->translator->_('unknown'),
            ],
        ]);

        return $model;
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
                $fileContent = $file->getContents();
                $firstRow = substr($fileContent, 0, strpos($fileContent, "\n"));
                $description = null;
                if (str_starts_with($firstRow, '--')) {
                    $description = trim(substr($firstRow, 2));
                }

                $table = [
                    'name' => $name,
                    'group' => $this->getGroupName($name),
                    'type' => 'table',
                    'description' => $description,
                    'order' => $this->defaultOrder,
                    'sql' => $fileContent,
                    'lastChanged' => $file->getMTime(),
                    'location' => $file->getRealPath(),
                    'db' => $tableDirectory['db'],
                ];

                if (count($filenameParts) === 2 && is_numeric($filenameParts[1])) {
                    $table['order'] = (int)$filenameParts[1];
                }
                $tables[$name] = $table;
            }
        }

        return $tables;
    }

    public function getTablesDirectories(): array
    {
        return $this->getResourceDirectories('tables');
    }
}