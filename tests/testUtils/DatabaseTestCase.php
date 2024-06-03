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
    use DatabaseTransactions;
    use LaminasDb;

    protected readonly ResultFetcher $resultFetcher;

    protected array $dbTables = [];

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
        $this->beginDatabaseTransaction();
        if ($this->dbTables) {
            $tableRepository = $this->getTableRepository();
            if (!$tableRepository->hasMigrationTable()) {
                $tableRepository->createMigrationTable();
            }
            $tableRepository->createTables($this->dbTables);
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