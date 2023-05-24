<?php

namespace GemsTest\Db\Migration;

use Gems\Config\App;
use Gems\Db\Databases;
use Gems\Db\Dsn;
use Gems\Db\Migration\SeedRepository;
use Gems\Db\ResultFetcher;
use GemsTest\Data\Db\SeedRepository\PhpSeed;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Loader\ConstructorProjectOverloader;
use Zalt\Loader\ProjectOverloader;

class SeedRepositoryTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSeedDirectories()
    {
        $config = [
            'migrations' => [
                'seeds' => [
                    __DIR__ . '/../../TestData/Db/SeedRepository',
                    [
                        'db' => 'gemsData',
                        'path' => __DIR__ . '/../../TestData/Db/SeedRepository',
                    ],
                ],
            ],
        ];

        $repository = $this->getRepository($config);

        $expected = [
            [
                'db' => 'gems',
                'path' => __DIR__ . '/../../TestData/Db/SeedRepository',
            ],
            [
                'db' => 'gemsData',
                'path' => __DIR__ . '/../../TestData/Db/SeedRepository',
            ],
        ];
        $directories = $repository->getSeedsDirectories();

        $this->assertEquals($expected, $directories);
    }

    public function testGetSeedsFromFiles()
    {
        $config = [
            'migrations' => [
                'seeds' => [
                    __DIR__ . '/../../TestData/Db/SeedRepository',
                ],
            ],
        ];

        $repository = $this->getRepository($config);

        $seedInfo = $repository->getSeedsFromFiles();

        $expected = [
            'anotherTestSeed' => [
                'name' => 'anotherTestSeed',
                'type' => 'seed',
                'description' => null,
                'order' => 1000,
                'data' => [
                    'test__table' => [
                        [
                            'tt_id' => 5,
                            'tt_description' => 'hello yaml',
                        ],
                        [
                            'tt_description' => 'hello another yaml',
                        ],
                    ],
                ],
                'lastChanged' => filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/anotherTestSeed.100.yaml'),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/anotherTestSeed.100.yaml'),
                'db' => 'gems',
            ],
            'jsonTestSeed' => [
                'name' => 'jsonTestSeed',
                'type' => 'seed',
                'description' => null,
                'order' => 1000,
                'data' => [
                    'test__table' => [
                        [
                            'tt_id' => 20,
                            'tt_description' => 'hello json',
                        ],
                        [
                            'tt_description' => 'hello another json',
                        ],
                    ],
                ],
                'lastChanged' => filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/jsonTestSeed.json'),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/jsonTestSeed.json'),
                'db' => 'gems',
            ],
            'testSeed' => [
                'name' => 'testSeed',
                'type' => 'seed',
                'description' => null,
                'order' => 1000,
                'data' => [
                    'test__table' => [
                        [
                            'tt_description' => 'hello yml',
                        ],
                    ],
                ],
                'lastChanged' => filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/jsonTestSeed.json'),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/testSeed.yml'),
                'db' => 'gems',
            ],
        ];

        $this->assertEquals($expected, $seedInfo);
    }

    public function testGetSeedsFromClasses()
    {
        require_once(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php');
        $config = [
            'migrations' => [
                'seeds' => [
                    PhpSeed::class,
                ],
            ],
        ];

        $seed = new PhpSeed();
        $overloaderProphecy = $this->prophesize(ConstructorProjectOverloader::class);
        $overloaderProphecy->create(PhpSeed::class)->willReturn($seed);

        $repository = $this->getRepository($config, null, null, $overloaderProphecy->reveal());

        $seedInfo = $repository->getSeedsFromClasses();

        $expected = [
            [
                'name' => PhpSeed::class,
                'type' => 'seed',
                'description' => null,
                'order' => 1000,
                'data' => [
                    'test__table' => [
                        [
                            'tt_description' => 'hi php',
                        ],
                    ],
                ],
                'lastChanged' => filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php'),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php'),
                'db' => 'gems',
            ],
        ];

        $this->assertEquals($expected, $seedInfo);
    }

    public function testGetSeedInfo()
    {
        require_once(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php');
        $config = [
            'migrations' => [
                'seeds' => [
                    PhpSeed::class,
                    __DIR__ . '/../../TestData/Db/SeedRepository',
                ],
            ],
        ];

        $seed = new PhpSeed();
        $overloaderProphecy = $this->prophesize(ConstructorProjectOverloader::class);
        $overloaderProphecy->create(PhpSeed::class)->willReturn($seed);

        $databases = $this->getDatabases();
        $repository = $this->getRepository($config, $databases, null, $overloaderProphecy->reveal());
        $this->createLogTable();

        $seedInfo = $repository->getSeedInfo();

        $expected = [
            [
                'name' => PhpSeed::class,
                'type' => 'seed',
                'description' => null,
                'order' => 1000,
                'data' => [
                    'test__table' => [
                        [
                            'tt_description' => 'hi php',
                        ],
                    ],
                ],
                'lastChanged' => filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php'),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php'),
                'db' => 'gems',
            ],
        ];

        $this->assertEquals($expected, $seedInfo);
    }


    protected function getTestDatabase()
    {
        $dbConfig = [
            'driver'    => 'pdo_mysql',
            'host'      => getenv('DB_HOST'),
            'username'  => getenv('DB_USER'),
            'password'  => getenv('DB_PASS'),
            'database'  => 'gems_test',
        ];

        return new Adapter(new Pdo(new \Pdo(Dsn::fromConfig($dbConfig))));
    }

    protected function getDatabases()
    {
        $adapter = $this->getTestDatabase();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Adapter::class)->willReturn(true);
        $containerProphecy->get(Adapter::class)->willReturn($adapter);
        $containerProphecy->has(Databases::ALIAS_PREFIX . 'GemsTest')->willReturn(true);
        $containerProphecy->get(Databases::ALIAS_PREFIX . 'GemsTest')->willReturn($adapter);

        return new Databases($containerProphecy->reveal());
    }

    protected function createLogTable()
    {
        $tableSql = file_get_contents(__DIR__ . '/../../../configs/db/tables/gems__migration_logs.1.sql');
        print_r($tableSql);
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $resultFetcher->query($tableSql);
    }

    protected function deleteLogTable()
    {
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $resultFetcher->query('DELETE TABLE test_table');
    }


    protected function getRepository(array $config = [],
        ?Databases $databases = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?ConstructorProjectOverloader $overloader = null,
    ): SeedRepository
    {
        if ($databases === null) {
            $databasesProphecy = $this->prophesize(Databases::class);
            $databases = $databasesProphecy->reveal();
        }

        if ($eventDispatcher === null) {
            $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
            $eventDispatcher = $eventDispatcherProphecy->reveal();
        }

        if ($overloader === null) {
            $overloaderProphecy = $this->prophesize(ConstructorProjectOverloader::class);
            $overloader = $overloaderProphecy->reveal();
        }

        $translatorProphecy = $this->prophesize(Translator::class);

        return new SeedRepository($config, $databases, $translatorProphecy->reveal(), $eventDispatcher, $overloader);
    }
}
