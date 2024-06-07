<?php

namespace GemsTest\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\Migration\MigrationModelFactory;
use Gems\Db\Migration\MigrationRepositoryAbstract;
use Gems\Db\Migration\PatchRepository;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\RunPatchMigrationEvent;
use Gems\Model\IteratorModel;
use Gems\Model\MetaModelLoader;
use GemsTest\TestData\Db\PatchRepository\PhpPatch;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ConstructorProjectOverloader;
use Zalt\Model\MetaModel;

/**
 * @group database
 */
class PatchRepositoryTest extends MigrationRepositoryTestAbstract
{
    use ProphecyTrait;

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

    public function testGetPatchesDirectories()
    {
        $config = [
            'migrations' => [
                'patches' => [
                    __DIR__ . '/../../TestData/Db/PatchRepository',
                    [
                        'db' => 'gemsData',
                        'path' => __DIR__ . '/../../TestData/Db/PatchRepository',
                    ],
                ],
            ],
        ];

        $repository = $this->getRepository($config);

        $expected = [
            [
                'db' => 'gems',
                'path' => __DIR__ . '/../../TestData/Db/PatchRepository',
                'module' => 'gems',
            ],
            [
                'db' => 'gemsData',
                'path' => __DIR__ . '/../../TestData/Db/PatchRepository',
            ],
        ];
        $directories = $repository->getPatchesDirectories();

        $this->assertEquals($expected, $directories);
    }

    public function testGetPatchesFromClasses()
    {
        require_once(__DIR__ . '/../../TestData/Db/PatchRepository/PhpPatch.php');
        $config = [
            'migrations' => [
                'patches' => [
                    PhpPatch::class,
                ],
            ],
        ];

        $seed = new PhpPatch();
        $overloaderProphecy = $this->prophesize(ConstructorProjectOverloader::class);
        $overloaderProphecy->create(PhpPatch::class)->willReturn($seed);

        $repository = $this->getRepository($config, null, null, $overloaderProphecy->reveal());

        $patchInfo = $repository->getPatchesFromClasses();

        $expected = [

            'gems-test-test-data-db-patch-repository-php-patch' => [
                'id' => 'gems-test-test-data-db-patch-repository-php-patch',
                'name' => PhpPatch::class,
                'type' => 'patch',
                'description' => 'add created field to test table',
                'order' => 100,
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/PatchRepository/PhpPatch.php')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/PatchRepository/PhpPatch.php'),
                'db' => 'gems',
                'module' => 'gems',
                'source' => 'class',
                'class' => new PhpPatch,
            ],
        ];

        $this->assertEquals($expected, $patchInfo);
    }

    public function testGetPatchesFromFiles()
    {
        $config = [
            'migrations' => [
                'patches' => [
                    __DIR__ . '/../../TestData/Db/PatchRepository',
                ],
            ],
        ];

        $repository = $this->getRepository($config);

        $result = $repository->getPatchesFromFiles();

        $data = file_get_contents(__DIR__ . '/../../TestData/Db/PatchRepository/123.somePatch.up.sql');

        $expected = [
            'some-patch' => [
                'id' => 'some-patch',
                'name' => 'somePatch',
                'module' => 'gems',
                'type' => 'patch',
                'description' => 'Patch for testing stuff!',
                'order' => 123,
                'data' => $data,
                'sql' => [
                    "ALTER TABLE test__table\n    ADD tt_name varchar(255)",
                ],
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/PatchRepository/123.somePatch.up.sql')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/PatchRepository/123.somePatch.up.sql'),
                'db' => 'gems',
                'source' => 'file',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetPatchesInfo()
    {
        $repository = $this->getAllPatchesRepository();

        $patchesInfo = $repository->getInfo();

        $data = file_get_contents(__DIR__ . '/../../TestData/Db/PatchRepository/123.somePatch.up.sql');

        $expected = [
            'gems-test-test-data-db-patch-repository-php-patch' => [
                'id' => 'gems-test-test-data-db-patch-repository-php-patch',
                'name' => PhpPatch::class,
                'type' => 'patch',
                'description' => 'add created field to test table',
                'order' => 100,
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/PatchRepository/PhpPatch.php')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/PatchRepository/PhpPatch.php'),
                'db' => 'gems',
                'module' => 'gems',
                'status' => 'new',
                'executed' => null,
                'duration' => null,
                'comment' => null,
                'source' => 'class',
                'class' => new PhpPatch,
            ],
            'some-patch' => [
                'id' => 'some-patch',
                'name' => 'somePatch',
                'module' => 'gems',
                'type' => 'patch',
                'description' => 'Patch for testing stuff!',
                'order' => 123,
                'data' => $data,
                'sql' => [
                    "ALTER TABLE test__table\n    ADD tt_name varchar(255)",
                ],
                'lastChanged' => \DateTimeImmutable::createFromFormat('U', (string) filemtime(__DIR__ . '/../../TestData/Db/PatchRepository/123.somePatch.up.sql')),
                'location' => realpath(__DIR__ . '/../../TestData/Db/PatchRepository/123.somePatch.up.sql'),
                'db' => 'gems',
                'status' => 'new',
                'executed' => null,
                'duration' => null,
                'comment' => null,
                'source' => 'file'
            ],
        ];

        $this->assertEquals($expected, $patchesInfo);
    }

    public function testRunPatches()
    {
        $repository = $this->getAllPatchesRepository(2);

        $model = $this->getModel($repository);
        $patches = $model->load(null, ['order']);

        foreach($patches as $patch) {
            $repository->runPatch($patch);
        }

        $adapter = $this->getTestDatabase();
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
            [
                'Field' => 'tt_created',
                'Type' => 'timestamp',
                'Null' => 'NO',
                'Key' => '',
                'Default' => 'CURRENT_TIMESTAMP',
                'Extra' => 'DEFAULT_GENERATED',
            ],
            [
                'Field' => 'tt_name',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => '',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testRunOnlyNewPatches()
    {
        $repository = $this->getAllPatchesRepository(1);
        $adapter = $this->getTestDatabase();

        $table = new TableGateway('gems__migration_logs', $adapter);
        $table->insert([
            'gml_name' => 'somePatch',
            'gml_type' => 'patch',
            'gml_version' => 1,
            'gml_module' => 'gems',
            'gml_status' => 'success',
            'gml_duration' => .1,
            'gml_sql' => "ALTER TABLE test__table\r\n    ADD tt_name varchar(255)",
            'gml_created' => new Expression('NOW()'),
        ]);

        $model = $this->getModel($repository);
        $patches = $model->load(['status' => 'new'], ['order']);

        foreach($patches as $patch) {
            $repository->runPatch($patch);
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
            [
                'Field' => 'tt_created',
                'Type' => 'timestamp',
                'Null' => 'NO',
                'Key' => '',
                'Default' => 'CURRENT_TIMESTAMP',
                'Extra' => 'DEFAULT_GENERATED',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    protected function getAllPatchesRepository(int $eventCalled = 0): PatchRepository
    {
        require_once(__DIR__ . '/../../TestData/Db/PatchRepository/PhpPatch.php');
        $config = [
            'migrations' => [
                'patches' => [
                    PhpPatch::class,
                    __DIR__ . '/../../TestData/Db/PatchRepository',
                ],
            ],
        ];

        $patch = new PhpPatch();
        $overloaderProphecy = $this->prophesize(ConstructorProjectOverloader::class);
        $overloaderProphecy->create(PhpPatch::class)->willReturn($patch);

        $databases = $this->getDatabases();

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::type(RunPatchMigrationEvent::class))->shouldBeCalledTimes($eventCalled);

        return $this->getRepository($config, $databases, $eventDispatcherProphecy->reveal(), $overloaderProphecy->reveal());
    }


    protected function getRepository(array $config = [],
        ?Databases $databases = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?ConstructorProjectOverloader $overloader = null,
    ): PatchRepository
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
        return new PatchRepository($config, $databases, $eventDispatcher, $overloader);
    }

    protected function getModel(MigrationRepositoryAbstract $repository)
    {
        $translatorProphecy = $this->prophesize(TranslatorInterface::class);
        $translatorProphecy->trans(Argument::type('string'), Argument::cetera())->willReturnArgument(0);
        $translatorProphecy->_(Argument::type('string'), Argument::cetera())->willReturnArgument(0);

        $metaModelLoader = $this->prophesize(MetaModelLoader::class);
        $model = new IteratorModel(new MetaModel('databasePatchesModel', $metaModelLoader->reveal()));
        $metaModelLoader->createModel(IteratorModel::class, 'databasePatchesModel')->willReturn($model);
        $metaModelLoader->getDefaultTypeInterface(Argument::type('int'))->willReturn(null);

        $factory = new MigrationModelFactory($translatorProphecy->reveal(), $metaModelLoader->reveal());
        return $factory->createModel($repository);
    }
}
