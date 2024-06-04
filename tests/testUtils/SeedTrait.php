<?php

namespace GemsTest\testUtils;

use Gems\Db\Databases;
use Gems\Db\Migration\SeedRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zalt\Loader\ProjectOverloader;

trait SeedTrait
{
    protected function getSeedRepository(): SeedRepository
    {
        $uses = array_flip(TraitUtil::getClassTraits(static::class));
        $config = [];
        if (isset($uses[ConfigTrait::class])) {
            $config = $this->getConfig();
        }
        if (!$this instanceof TestCase) {
            throw new Exception('SeedTrait should be used in a unit test');
        }

        $databases = $this->createMock(Databases::class);
        $databases->expects($this->any())->method('getDatabase')->willReturn($this->db);
        $databases->expects($this->any())->method('getDefaultDatabase')->willReturn($this->db);

        $container = $this->createMock(ContainerInterface::class);
        $overLoader = new ProjectOverloader($container);

        $eventDispatcher = $this->createMock(EventDispatcher::class);

        return new SeedRepository($config, $databases, $eventDispatcher, $overLoader);
    }
    public function seed(array|string $seeds): void
    {
        if (!is_array($seeds)) {
            $seeds = [$seeds];
        }
        $seedRepository = $this->getSeedRepository();
        foreach ($seeds as $seed) {
            $info = $seedRepository->getSeedInfo($seed);
            $seedRepository->runSeed($info);
        }
    }
}