<?php

namespace GemsTest\testUtils;

use Gems\Db\Databases;
use Gems\Db\Migration\TableRepository;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Source\Factory;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait DatabaseMigrationsTrait
{
    protected array $dbTables = [];

    protected function getTableRepository(): TableRepository
    {
        $databases = $this->createMock(Databases::class);
        $databases->expects($this->any())->method('getDatabase')->willReturn($this->db);
        $databases->expects($this->any())->method('getDefaultDatabase')->willReturn($this->db);

        $eventDispatcher = $this->createMock(EventDispatcher::class);

        return new TableRepository($this->getConfig(), $databases, $eventDispatcher);
    }
    public function runDatabaseMigrations(): void
    {
        if ($this->dbTables) {
            $tableRepository = $this->getTableRepository();
            if (!$tableRepository->hasMigrationTable()) {
                $tableRepository->createMigrationTable();
            }
            $tableRepository->createTables($this->dbTables);
        }
    }

    public function removeDatabases(): void
    {
        $metaData = Factory::createSourceFromAdapter($this->db);
        foreach($metaData->getTableNames() as $tableName) {
            $this->db->query('DROP TABLE IF EXISTS ' . $tableName, Adapter::QUERY_MODE_EXECUTE);
        }
    }
}