<?php

namespace GemsTest\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\Migration\SeedRepository;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Loader\ConstructorProjectOverloader;

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
                'lastChanged' => 1684872533,
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
                'lastChanged' => 1684873449,
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
                'lastChanged' => 1684873518,
                'location' => realpath(__DIR__ . '/../../TestData/Db/SeedRepository/testSeed.yml'),
                'db' => 'gems',
            ],
        ];

        $this->assertEquals($expected, $seedInfo);
    }

    public function testGetSeedsFromClasses()
    {
        $config = [
            'migrations' => [
                'seeds' => [
                    \GemsTest\data\Db\SeedRepository\TestPhpSeed::class,
                ],
            ],
        ];

        $repository = $this->getRepository($config);

        $seedInfo = $repository->getSeedsFromClasses();

        $expected = [

        ];

        print_r($seedInfo);

        $this->assertEquals($expected, $seedInfo);
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