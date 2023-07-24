<?php

namespace GemsTest\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\Dsn;
use Gems\Db\Migration\TableRepository;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\CreateTableMigrationEvent;
use Gems\Model\IteratorModel;
use Gems\Model\MetaModelLoader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Model\MetaModel;

/**
 * @group database
 */
class TableRepositoryTest extends MigrationRepositoryTestAbstract
{

    public function setUp(): void
    {
        $this->createLogTable();
    }

    public function tearDown(): void
    {
        $this->deleteLogTable();
    }
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
                'module' => 'gems',
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

        $tableInfo = $repository->getInfo();

        $sql2 = file_get_contents(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql');

        $expected = [
            'test__other_table' => [
                'name' => 'test__other_table',
                'module' => 'test',
                'type' => 'table',
                'description' => null,
                'order' => 100,
                'data' => '',
                'sql' => '',
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', filemtime(__DIR__ . '/../../TestData/Db/TableRepository/test__other_table.100.sql')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/TableRepository/test__other_table.100.sql'),
                'db' => 'gemsTest',
                'status' => 'new',
            ],
            'test__table' => [
                'name' => 'test__table',
                'module' => 'test',
                'type' => 'table',
                'description' => 'This is a test tables description',
                'order' => 1000,
                'data' => $sql2,
                'sql' => $sql2,
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', filemtime(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql'),
                'db' => 'gemsTest',
                'status' => 'new',
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

        // We expect the migration logs table from the setUp!
        if (isset($tableInfo['gems__migration_logs'])) {
            unset($tableInfo['gems__migration_logs']);
        }

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
                'module' => 'test',
                'type' => 'table',
                'description' => null,
                'order' => 100,
                'data' => '',
                'sql' => '',
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', filemtime(__DIR__ . '/../../TestData/Db/TableRepository/test__other_table.100.sql')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/TableRepository/test__other_table.100.sql'),
                'db' => 'gems',
            ],
            'test__table' => [
                'name' => 'test__table',
                'module' => 'test',
                'type' => 'table',
                'description' => 'This is a test tables description',
                'order' => 1000,
                'data' => $sql2,
                'sql' => $sql2,
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', filemtime(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql')),
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

        $tableItems = $repository->getInfo();

        $exceptionCount = 0;
        foreach ($tableItems as $tableItem) {
            try {
                $repository->createTable($tableItem);
            } catch (\Exception $e) {
                $exceptionCount++;
            }
        }

        $resultFetcher = new ResultFetcher($adapter);
        $result = $resultFetcher->fetchAll('DESCRIBE test__table');

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

        $this->deleteTestTable();
    }

    public function testCreateOnlyNewTables()
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
        $eventDispatcherProphecy->dispatch(Argument::type(CreateTableMigrationEvent::class))->shouldNotBeCalled();

        $repository = $this->getDatabaseRepository($config, $databases, $eventDispatcherProphecy->reveal());

        $adapter = $this->getTestDatabase();

        $sql = file_get_contents(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql');

        $table = new TableGateway('gems__migration_logs', $adapter);
        $table->insert([
            'gml_name' => 'test__table',
            'gml_type' => 'table',
            'gml_version' => 1,
            'gml_module' => 'gems',
            'gml_status' => 'success',
            'gml_duration' => .1,
            'gml_sql' => $sql,
            'gml_created' => new Expression('NOW()'),
        ]);

        $model = $repository->getModel();

        $tableItems = $model->load(['status' => 'new']);

        $exceptionCount = 0;
        foreach ($tableItems as $tableItem) {
            try {
                $repository->createTable($tableItem);
            } catch (\Exception $e) {
                $exceptionCount++;
            }
        }

        $currentTables = $repository->getTableInfoFromDb();
        $this->assertCount(1, $currentTables);
        $this->assertEquals(1, $exceptionCount);
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
        $translatorProphecy->trans(Argument::type('string'), Argument::cetera())->willReturnArgument(0);

        $metaModelLoader = $this->prophesize(MetaModelLoader::class);
        $model = new IteratorModel(new MetaModel('databaseTableModel', $metaModelLoader->reveal()));
        $metaModelLoader->createModel(IteratorModel::class, 'databaseTableModel')->willReturn($model);
        $metaModelLoader->getDefaultTypeInterface(Argument::type('int'))->willReturn(null);

        return new TableRepository($config, $databases, $translatorProphecy->reveal(), $eventDispatcher, $metaModelLoader->reveal());
    }
}
