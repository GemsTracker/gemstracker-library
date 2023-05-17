<?php

namespace Gems\Db;

use Gems\Model\IteratorModel;
use Laminas\Db\Adapter\Adapter;
use MUtil\Model\ModelAbstract;
use MUtil\Translate\Translator;
use Zalt\Model\MetaModelInterface;

class DatabaseRepository
{
    protected string $defaultDatabase = 'gems';

    protected int $defaultOrder = 1000;

    public function __construct(
        private readonly array $config,
        private readonly Databases $databases,
        private readonly Translator $translator,
    )
    {}

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

    public function getDsn(array $config): ?string
    {
        if (isset($config['dsn'])) {
            return $config['dsn'];
        }
        $connectionName = $config['driver'] ?? null;
        if (!isset($config['database'])) {
            throw new \Exception('No database in config');
        }
        $dbName = $config['database'];
        $driverName = $this->getDsnDriverName($connectionName);
        if ($driverName === 'sqlite') {
            return "$driverName:$dbName";
        }

        $host = $config['host'] ?? 'localhost';
        $dsnParts = [
            'host' => $host,
        ];

        if (isset($config['port'])) {
            $dsnParts['port'] = $config['port'];
        }
        $dsnParts['dbname'] = $dbName;
        if (isset($config['username'])) {
            $dsnParts['user'] = $config['username'];
        }
        if (isset($config['password'])) {
            $dsnParts['password'] = $config['password'];
        }
        if (isset($config['charset'])) {
            $dsnParts['charset'] = $config['charset'];
        }

        $dsn = "$driverName:";
        foreach($dsnParts as $key=>$value) {
            $dsn .= "$key=$value;";
        }

        return rtrim($dsn, ';');
    }

    public function getDsnDriverName(?string $connection): string
    {
        return match (strtolower($connection)) {
            'pdo_sqlite', 'sqlite' => 'sqlite',
            'pdo_pgsql', 'pgsql' => 'pgsql',
            'sqlsrv', 'mssql' => 'mssql',
            default => 'mysql',
        };
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
                    'order' => $this->defaultOrder,
                    'script' => '',
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
        $model->set('script', [
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
            if (!is_dir($tableDirectory['path'])) {
                continue;
            }
            foreach (new \GlobIterator($tableDirectory['path'] . DIRECTORY_SEPARATOR . '*.sql') as $file) {
                $filenameParts = explode('.', $file->getBaseName('.sql'));
                $name = $filenameParts[0];
                $fileContent = file_get_contents($file->getPathname());
                $table = [
                    'name' => $filenameParts[0],
                    'group' => $this->getGroupName($name),
                    'type' => 'table',
                    'order' => $this->defaultOrder,
                    'script' => $fileContent,
                    'lastChanged' => $file->getMTime(),
                    'location' => $tableDirectory['path'],
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
        $tableDirectories = $this->config['migrations']['tables'] ?? [];

        foreach($tableDirectories as $key=>$tableDirectory) {
            if (is_string($tableDirectory)) {
                $tableDirectories[$key] = [
                    'db' => $this->defaultDatabase,
                    'path' => $tableDirectory,
                ];
            }
        }

        return $tableDirectories;
    }
}