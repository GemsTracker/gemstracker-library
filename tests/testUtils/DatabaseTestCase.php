<?php

namespace GemsTest\testUtils;

use Gems\Db\Databases;
use Gems\Db\Migration\TableRepository;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Source\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DatabaseTestCase extends TestCase
{
    use ConfigTrait, ConfigModulesTrait {
        ConfigModulesTrait::getModules insteadof ConfigTrait;
    }
    use DatabaseTransactionsTrait;
    use LaminasDbTrait;
    use SeedTrait;

    protected ResultFetcher $resultFetcher;

    protected array $dbTables = [];

    protected array $seeds = [];

    protected function getTableRepository(): TableRepository
    {
        $databases = $this->createMock(Databases::class);
        $databases->expects($this->any())->method('getDatabase')->willReturn($this->db);
        $databases->expects($this->any())->method('getDefaultDatabase')->willReturn($this->db);

        $eventDispatcher = $this->createMock(EventDispatcher::class);

        return new TableRepository($this->getConfig(), $databases, $eventDispatcher);
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->initDb();
        $this->resultFetcher = new ResultFetcher($this->db);

        if ($this->dbTables) {
            $tableRepository = $this->getTableRepository();
            if (!$tableRepository->hasMigrationTable()) {
                $tableRepository->createMigrationTable();
            }
            $tableRepository->createTables($this->dbTables);
        }

        $this->beginDatabaseTransaction();

        if ($this->seeds) {
            foreach($this->seeds as $seed) {
                $this->seed($seed);
            }
        }
    }

    protected function tearDown(): void
    {
        $this->rollbackDatabaseTransaction();

        $metaData = Factory::createSourceFromAdapter($this->db);
        foreach($metaData->getTableNames() as $tableName) {
            $this->db->query('DROP TABLE IF EXISTS ' . $tableName, Adapter::QUERY_MODE_EXECUTE);
        }
    }
}