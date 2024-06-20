<?php

namespace GemsTest\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\Migration\MigrationModelFactory;
use Gems\Db\Migration\MigrationRepositoryAbstract;
use Gems\Db\Migration\SeedRepository;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\RunSeedMigrationEvent;
use Gems\Model\IteratorModel;
use Gems\Model\MetaModelLoader;
use GemsTest\Data\Db\SeedRepository\PhpSeed;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ConstructorProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModel;

#[Group('database')]
class SeedRepositoryTest extends MigrationRepositoryTestAbstract
{
    public function setUp(): void
    {
        parent::setUp();
        $this->createLogTable();
        $this->createTestTable();
    }

    public function tearDown(): void
    {
        $this->deleteLogTable();
        $this->deleteTestTable();
        parent::tearDown();
    }


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
                'module' => 'gems',
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
            'another-test-seed' => [
                'id' => 'another-test-seed',
                'name' => 'anotherTestSeed',
                'type' => 'seed',
                'description' => null,
                'order' => 100,
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
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/anotherTestSeed.100.yaml')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/anotherTestSeed.100.yaml'),
                'db' => 'gems',
                'module' => 'gems',
            ],
            'json-test-seed' => [
                'id' => 'json-test-seed',
                'name' => 'jsonTestSeed',
                'type' => 'seed',
                'description' => null,
                'order' => 1000,
                'data' => [
                    'test__table' => [
                        [
                            'tt_id' => 21,
                            'tt_description' => 'hello json',
                        ],
                        [
                            'tt_description' => 'hello another json',
                        ],
                    ],
                ],
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/jsonTestSeed.json')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/jsonTestSeed.json'),
                'db' => 'gems',
                'module' => 'gems',
            ],
            'test-seed' => [
                'id' => 'test-seed',
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
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/testSeed.yml')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/testSeed.yml'),
                'db' => 'gems',
                'module' => 'gems',
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
            'gems-test-data-db-seed-repository-php-seed' => [
                'id' => 'gems-test-data-db-seed-repository-php-seed',
                'name' => PhpSeed::class,
                'type' => 'seed',
                'description' => null,
                'order' => 501,
                'data' => [
                    'test__table' => [
                        [
                            'tt_description' => 'hi php',
                        ],
                    ],
                ],
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php'),
                'db' => 'gems',
                'module' => 'gems',
            ],
        ];

        $this->assertEquals($expected, $seedInfo);
    }

    public function testGetSeedInfo()
    {
        $repository = $this->getAllSeedsRepository();

        $seedInfo = $repository->getInfo();

        $expected = [
            'another-test-seed' => [
                'id' => 'another-test-seed',
                'name' => 'anotherTestSeed',
                'type' => 'seed',
                'description' => null,
                'order' => 100,
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
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/anotherTestSeed.100.yaml')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/anotherTestSeed.100.yaml'),
                'db' => 'gems',
                'module' => 'gems',
                'status' => 'new',
                'executed' => null,
                'duration' => null,
                'sql' => null,
                'comment' => null,
            ],
            'gems-test-data-db-seed-repository-php-seed' => [
                'id' => 'gems-test-data-db-seed-repository-php-seed',
                'name' => PhpSeed::class,
                'type' => 'seed',
                'description' => null,
                'order' => 501,
                'data' => [
                    'test__table' => [
                        [
                            'tt_description' => 'hi php',
                        ],
                    ],
                ],
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/PhpSeed.php'),
                'db' => 'gems',
                'module' => 'gems',
                'status' => 'new',
                'executed' => null,
                'duration' => null,
                'sql' => null,
                'comment' => null,
            ],
            'json-test-seed' => [
                'id' => 'json-test-seed',
                'name' => 'jsonTestSeed',
                'type' => 'seed',
                'description' => null,
                'order' => 1000,
                'data' => [
                    'test__table' => [
                        [
                            'tt_id' => 21,
                            'tt_description' => 'hello json',
                        ],
                        [
                            'tt_description' => 'hello another json',
                        ],
                    ],
                ],
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/jsonTestSeed.json')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/jsonTestSeed.json'),
                'db' => 'gems',
                'module' => 'gems',
                'status' => 'new',
                'executed' => null,
                'duration' => null,
                'sql' => null,
                'comment' => null,
            ],
            'test-seed' => [
                'id' => 'test-seed',
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
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/SeedRepository/testSeed.yml')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/testSeed.yml'),
                'db' => 'gems',
                'module' => 'gems',
                'status' => 'new',
                'executed' => null,
                'duration' => null,
                'sql' => null,
                'comment' => null,
            ],
        ];

        $this->assertEquals($expected, $seedInfo);
        // Also assert that the order of the array elements is as expected.
        $this->assertEquals(array_keys($expected), array_keys($seedInfo), 'Seeds are in the wrong order');
    }

    public function testRunSeed()
    {
        $repository = $this->getAllSeedsRepository(4);
        $seedInfo = $repository->getInfo();

        foreach($seedInfo as $seed) {
            $repository->runSeed($seed);
        }

        $db = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($db);
        $result = $resultFetcher->fetchAll('SELECT * FROM test__table');

        $expected = [
            [
                'tt_id' => 5,
                'tt_description' => 'hello yaml',
            ],
            [
                'tt_id' => 6,
                'tt_description' => 'hello another yaml',
            ],
            [
                'tt_id' => 7,
                'tt_description' => 'hi php',
            ],
            [
                'tt_id' => 21,
                'tt_description' => 'hello json',
            ],
            [
                'tt_id' => 22,
                'tt_description' => 'hello another json',
            ],
            [
                'tt_id' => 23,
                'tt_description' => 'hello yml',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testRunOnlyNewSeeds()
    {
        $repository = $this->getAllSeedsRepository(3);

        $db = $this->getTestDatabase();
        $table = new TableGateway('gems__migration_logs', $db);
        $table->insert([
            'gml_name' => 'testSeed',
            'gml_type' => 'seed',
            'gml_version' => 1,
            'gml_module' => 'gems',
            'gml_status' => 'success',
            'gml_duration' => .1,
            'gml_sql' => "INSERT INTO `test__table` (`tt_description`) VALUES ('hello yml')",
            'gml_created' => new Expression('NOW()'),
        ]);

        $model = $this->getModel($repository);

        $seeds = $model->load(['status' => 'new']);

        foreach($seeds as $seed) {
            $repository->runSeed($seed);
        }

        $db = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($db);
        $result = $resultFetcher->fetchAll('SELECT * FROM test__table');

        $expected = [
            [
                'tt_id' => 5,
                'tt_description' => 'hello yaml',
            ],
            [
                'tt_id' => 6,
                'tt_description' => 'hello another yaml',
            ],
            [
                'tt_id' => 7,
                'tt_description' => 'hi php',
            ],
            [
                'tt_id' => 21,
                'tt_description' => 'hello json',
            ],
            [
                'tt_id' => 22,
                'tt_description' => 'hello another json',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    protected function getAllSeedsRepository(int $eventCalled = 0): SeedRepository
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

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::type(RunSeedMigrationEvent::class))->shouldBeCalledTimes($eventCalled);

        return $this->getRepository($config, $databases, $eventDispatcherProphecy->reveal(), $overloaderProphecy->reveal());
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
        return new SeedRepository($config, $databases, $eventDispatcher, $overloader);
    }

    protected function getModel(MigrationRepositoryAbstract $repository): DataReaderInterface
    {
        $translatorProphecy = $this->prophesize(TranslatorInterface::class);
        $translatorProphecy->trans(Argument::type('string'), Argument::cetera())->willReturnArgument(0);
        $translatorProphecy->_(Argument::type('string'), Argument::cetera())->willReturnArgument(0);

        $metaModelLoader = $this->prophesize(MetaModelLoader::class);
        $model = new IteratorModel(new MetaModel('databaseSeedModel', $metaModelLoader->reveal()));
        $metaModelLoader->createModel(IteratorModel::class, 'databaseSeedModel')->willReturn($model);
        $metaModelLoader->getDefaultTypeInterface(Argument::type('int'))->willReturn(null);

        $factory = new MigrationModelFactory($translatorProphecy->reveal(), $metaModelLoader->reveal());
        return $factory->createModel($repository);
    }
}
