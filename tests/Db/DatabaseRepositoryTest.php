<?php

namespace GemsTest\Db;

use Gems\Db\DatabaseRepository;
use Gems\Db\Databases;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DatabaseRepositoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider dsnDataProvider
     */
    public function testGetDsn($config, $expectedDsn)
    {
        $repository = $this->getDatabaseRepository();
        $this->assertEquals($expectedDsn, $repository->getDsn($config));
    }

    public static function dsnDataProvider()
    {
        return [
            [['driver' => 'sqlite', 'database' => 'test_database'], 'sqlite:test_database'],
            [['driver' => 'pdo_sqlite', 'database' => 'test_db'], 'sqlite:test_db'],
            [['driver' => 'pdo_sqlite', 'dsn' => 'sqlite:test_db', 'database' => 'test_db'], 'sqlite:test_db'],
            [['driver' => 'pdo_mysql', 'database' => 'test_db'], 'mysql:host=localhost;dbname=test_db'],
            [['driver' => 'pdo_mysql', 'host' => 'database.test', 'database' => 'test_db'], 'mysql:host=database.test;dbname=test_db'],
            [['driver' => 'pdo_mysql', 'host' => 'localhost', 'database' => 'test_db', 'username' => 'root'], 'mysql:host=localhost;dbname=test_db;user=root'],
            [['driver' => 'pdo_mysql', 'host' => 'localhost', 'database' => 'test_db', 'username' => 'root', 'password' => 'test123'], 'mysql:host=localhost;dbname=test_db;user=root;password=test123'],
            [['driver' => 'pdo_mysql', 'host' => 'localhost', 'port' => '1234', 'database' => 'test_db', 'username' => 'root', 'password' => 'test123'], 'mysql:host=localhost;port=1234;dbname=test_db;user=root;password=test123'],
            [['driver' => 'mysql', 'host' => 'localhost', 'port' => '1234', 'charset' => 'utf8', 'database' => 'test_db', 'username' => 'root', 'password' => 'test123'], 'mysql:host=localhost;port=1234;dbname=test_db;user=root;password=test123;charset=utf8'],
        ];
    }

    public function testGetDbConnectionNamesFromDirs()
    {
        $config = [
            'migrations' => [
                'tables' => [
                    'someDir',
                    [
                        'db' => 'gemsData',
                        'path' => 'someOtherDir',
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
                    'someDir',
                    [
                        'db' => 'gemsData',
                        'path' => 'someOtherDir',
                    ],
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config);

        $expected = [
            [
                'db' => 'gems',
                'path' => 'someDir',
            ],
            [
                'db' => 'gemsData',
                'path' => 'someOtherDir',
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
                        'path' => __DIR__ . '/../data/Db/DatabaseRepository',
                    ],
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config, $databases);

        $tableInfo = $repository->getTableInfo();

        $expected = [
            'test__other_table' => [
                'name' => 'test__other_table',
                'group' => 'test',
                'type' => 'table',
                'order' => 100,
                'script' => '',
                'lastChanged' => 1684246415,
                'location' => __DIR__ . '/../data/Db/DatabaseRepository',
                'db' => 'gemsTest',
                'exists' => false,
            ],
            'test__table' => [
                'name' => 'test__table',
                'group' => 'test',
                'type' => 'table',
                'order' => 1000,
                'script' => '',
                'lastChanged' => 1684246415,
                'location' => __DIR__ . '/../data/Db/DatabaseRepository',
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
                    __DIR__ . '/../data/Db/DatabaseRepository',
                ],
            ],
        ];

        $repository = $this->getDatabaseRepository($config);
        $tables = $repository->getTableInfoFromFiles();

        $expected = [
            'test__other_table' => [
                'name' => 'test__other_table',
                'group' => 'test',
                'type' => 'table',
                'order' => 100,
                'script' => '',
                'lastChanged' => 1684246415,
                'location' => __DIR__ . '/../data/Db/DatabaseRepository',
                'db' => 'gems',
            ],
            'test__table' => [
                'name' => 'test__table',
                'group' => 'test',
                'type' => 'table',
                'order' => 1000,
                'script' => '',
                'lastChanged' => 1684246415,
                'location' => __DIR__ . '/../data/Db/DatabaseRepository',
                'db' => 'gems',
            ],
        ];

        $this->assertEquals($expected, $tables);
    }

    protected function getDatabases()
    {
        $repository = $this->getDatabaseRepository();
        $dbConfig = [
            'driver'    => 'pdo_mysql',
            'host'      => getenv('DB_HOST'),
            'username'  => getenv('DB_USER'),
            'password'  => getenv('DB_PASS'),
            'database'  => 'gems_test',
        ];

        $adapter = new Adapter(new Pdo(new \Pdo($repository->getDsn($dbConfig))));

        $databasesProphecy = $this->prophesize(Databases::class);
        $databasesProphecy->getDatabase('gemsTest')->willReturn($adapter);
        return $databasesProphecy->reveal();
    }

    protected function getDatabaseRepository(array $config = [], ?Databases $databases = null)
    {
        if ($databases === null) {
            $databasesProphecy = $this->prophesize(Databases::class);
            $databases = $databasesProphecy->reveal();
        }

        $translatorProphecy = $this->prophesize(Translator::class);

        return new DatabaseRepository($config, $databases, $translatorProphecy->reveal());
    }
}