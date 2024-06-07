<?php

namespace GemsTest\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use GemsTest\testUtils\LaminasDbTrait;
use GemsTest\testUtils\TestCase;
use Laminas\Db\Adapter\Adapter;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class MigrationRepositoryTestAbstract extends TestCase
{
    use LaminasDbTrait;
    use ProphecyTrait;
    protected function createLogTable(): void
    {
        $tableSql = file_get_contents(__DIR__ . '/../../../configs/db/tables/gems__migration_logs.1.sql');
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $resultFetcher->query($tableSql);
    }

    protected function createTestTable()
    {
        $tableSql = file_get_contents(__DIR__ . '/../../TestData/Db/TableRepository/test__table.sql');
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $resultFetcher->query($tableSql);
    }

    protected function deleteLogTable()
    {
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $resultFetcher->query('DROP TABLE `gems__migration_logs`');
    }

    protected function deleteTestTable()
    {
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        $resultFetcher->query('DROP TABLE `test__table`');
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

    protected function getTestDatabase(): Adapter
    {
        return $this->db;
    }
}