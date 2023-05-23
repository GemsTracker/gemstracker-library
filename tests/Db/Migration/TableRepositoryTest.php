<?php

namespace Db\Migration;

use Gems\Db\Databases;
use Gems\Db\Dsn;
use Gems\Db\Migration\TableRepository;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\CreateTableMigrationEvent;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class TableRepositoryTest extends TestCase
{
    use ProphecyTrait;

    public function testGetDbConnectionNamesFromDirs()
    {
        $config = [
            'migrations' => [
                'tables' => [
                    __DIR__ . '/../../TestData/Db/TableRepository',
                    [
                        'db' => 'gemsData',
                        'path' => __DIR__ . '/../../TestData/Db/TableRepository',
                    ],
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config);
        $databaseNames = $repository->getDbNamesFromDirs();

        $expected = [
            'gems',
            'gemsData',
        ];
        $this->assertEquals($expected, $databaseNames);
    }

    public function testGetTablesDirectories()
    {
        $config = [
            'migrations' => [
                'tables' => [
                    __DIR__ . '/../../TestData/Db/TableRepository',
                    [
                        'db' => 'gemsData',
                        'path' => __DIR__ . '/../../TestData/Db/TableRepository',
                    ],
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config);

        $expected = [
            [
                'db' => 'gems',
                'path' => __DIR__ . '/../../TestData/Db/TableRepository',
            ],
            [
                'db' => 'gemsData',
                'path' => __DIR__ . '/../../TestData/Db/TableRepository',
            ],
        ];
        $directories = $repository->getTablesDirectories();

        $this->assertEquals($expected, $directories);
    }

    public function testGetTableInfoEmptyDb()
    {
        $databases = $this->getDatabases();

        $config = [
            'migrations' => [
                'tables' => [
                    [
                        'db' => 'gemsTest',
                        'path' => __DIR__ . '/../../TestData/Db/TableRepository',
                    ],
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config, $databases);

        $tableInfo = $repository->getTableInfo();

        $sql2 = file_get_contents(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql');

        $expected = [
            'test__other_table' => [
                'name' => 'test__other_table',
                'group' => 'test',
                'type' => 'table',
                'description' => null,
                'order' => 100,
                'sql' => '',
                'lastChanged' => 1684246415,
                'location' => realpath(__DIR__ . '/../../TestData/Db/TableRepository/test__other_table.100.sql'),
                'db' => 'gemsTest',
                'exists' => false,
            ],
            'test__table' => [
                'name' => 'test__table',
                'group' => 'test',
                'type' => 'table',
                'description' => 'This is a test tables description',
                'order' => 1000,
                'sql' => $sql2,
                'lastChanged' => 1684763706,
                'location' => realpath(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql'),
                'db' => 'gemsTest',
                'exists' => false,
            ],
        ];

        $this->assertEquals($expected, $tableInfo);
    }

    public function testGetTableInfoFromDbEmpty()
    {
        $databases = $this->getDatabases();

        $config = [
            'migrations' => [
                'tables' => [
                    [
                        'db' => 'gemsTest',
                        'path' => 'someDir',
                    ],
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config, $databases);

        $tableInfo = $repository->getTableInfoFromDb();

        $this->assertIsArray($tableInfo);
        $this->assertEmpty($tableInfo);
    }

    public function testGetTableInfoFromFiles()
    {
        $config = [
            'migrations' => [
                'tables' => [
                    __DIR__ . '/../../TestData/Db/TableRepository',
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config);
        $tables = $repository->getTableInfoFromFiles();

        $sql2 = file_get_contents(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql');

        $expected = [
            'test__other_table' => [
                'name' => 'test__other_table',
                'group' => 'test',
                'type' => 'table',
                'description' => null,
                'order' => 100,
                'sql' => '',
                'lastChanged' => 1684246415,
                'location' => realpath(__DIR__ . '/../../TestData/Db/TableRepository/test__other_table.100.sql'),
                'db' => 'gems',
            ],
            'test__table' => [
                'name' => 'test__table',
                'group' => 'test',
                'type' => 'table',
                'description' => 'This is a test tables description',
                'order' => 1000,
                'sql' => $sql2,
                'lastChanged' => 1684763706,
                'location' => realpath(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql'),
                'db' => 'gems',
            ],
        ];

        $this->assertEquals($expected, $tables);
    }

    public function testCreateTable()
    {
        $databases = $this->getDatabases();

        $config = [
            'migrations' => [
                'tables' => [
                    [
                        'db' => 'gemsTest',
                        'path' => __DIR__ . '/../../TestData/Db/TableRepository',
                    ],
                ],
            ],
        ];

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::type(CreateTableMigrationEvent::class))->shouldBeCalledTimes(1);

        $repository = $this->getDatabaseRepository($config, $databases, $eventDispatcherProphecy->reveal());

        $adapter = $databases->getDatabase('gemsTest');

        $tableItems = $repository->getTableInfo();

        $exceptionCount = 0;
        foreach($tableItems as $tableItem) {
            try {
                $repository->createTable($tableItem);
            } catch (\Exception $e) {
                $exceptionCount++;
            }
        }

        $resultFetcher = new ResultFetcher($adapter);
        $result = $resultFetcher->fetchAll('DESCRIBE test_table');

        $expected = [
            [
                'Field' => 'tt_id',
                'Type' => 'bigint unsigned',
                'Null' => 'NO',
                'Key' => 'PRI',
                'Default' => null,
                'Extra' => 'auto_increment',
            ],
            [
                'Field' => 'tt_description',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => null,
                'Default' => null,
                'Extra' => null,
            ],
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals(1, $exceptionCount);

        $resultFetcher->query('DROP TABLE test_table');
    }

    protected function getDatabases()
    {
        $dbConfig = [
            'driver'    => 'pdo_mysql',
            'host'      => getenv('DB_HOST'),
            'username'  => getenv('DB_USER'),
            'password'  => getenv('DB_PASS'),
            'database'  => 'gems_test',
        ];

        $adapter = new Adapter(new Pdo(new \Pdo(Dsn::fromConfig($dbConfig))));
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Databases::ALIAS_PREFIX . 'GemsTest')->willReturn(true);
        $containerProphecy->get(Databases::ALIAS_PREFIX . 'GemsTest')->willReturn($adapter);

        return new Databases($containerProphecy->reveal());
    }

    protected function getDatabaseRepository(array $config = [], ?Databases $databases = null, ?EventDispatcherInterface $eventDispatcher = null)
    {
        if ($databases === null) {
            $databasesProphecy = $this->prophesize(Databases::class);
            $databases = $databasesProphecy->reveal();
        }

        if ($eventDispatcher === null) {
            $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
            $eventDispatcher = $eventDispatcherProphecy->reveal();
        }

        $translatorProphecy = $this->prophesize(Translator::class);

        return new TableRepository($config, $databases, $translatorProphecy->reveal(), $eventDispatcher);
    }
}