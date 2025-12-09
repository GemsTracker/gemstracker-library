<?php

declare(strict_types=1);

namespace GemsTest\Db\Migration;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\ResultFetcher;
use GemsTest\testUtils\LaminasDbTrait;
use GemsTest\testUtils\TestCase;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
class DatabaseInfoTest extends TestCase
{
    use LaminasDbTrait;

    private DatabaseInfo $dbInfo;

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestTables();
        $this->dbInfo = new DatabaseInfo($this->db);
    }

    public function tearDown(): void
    {
        $this->deleteTestTables();
        parent::tearDown();
    }

    protected function createTestTables()
    {
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        foreach (["test__users", "test__posts"] as $table) {
            $tableSql = file_get_contents(__DIR__ . '/../../TestData/Db/DatabaseInfo/' . $table . '.sql');
            $resultFetcher->query($tableSql);
        }
    }

    protected function deleteTestTables()
    {
        $adapter = $this->getTestDatabase();
        $resultFetcher = new ResultFetcher($adapter);
        foreach (["test__posts", "test__users"] as $table) {
            $resultFetcher->query("DROP TABLE IF EXISTS `$table`");
        }
    }

    protected function getTestDatabase(): Adapter
    {
        return $this->db;
    }

    public function testTableExists()
    {
        $this->assertTrue($this->dbInfo->tableExists('test__users'));
        $this->assertTrue($this->dbInfo->tableExists('test__posts'));
        $this->assertFalse($this->dbInfo->tableExists('nonexistent'));
    }

    public function testTableHasColumn()
    {
        $this->assertTrue($this->dbInfo->tableHasColumn('test__users', 'username'));
        $this->assertFalse($this->dbInfo->tableHasColumn('test__users', 'nonexistent'));
    }

    public function testTableHasConstraint()
    {
        $constraints = $this->dbInfo->getMetaData()->getConstraints('test__users');
        $uniqueName = null;
        foreach ($constraints as $constraint) {
            if ($constraint->getType() === 'UNIQUE') {
                $uniqueName = $constraint->getName();
                break;
            }
        }
        $this->assertTrue($uniqueName !== null);
        $this->assertTrue($this->dbInfo->tableHasConstraint('test__users', $uniqueName));
    }

    public function testTableHasForeignKey()
    {
        $this->assertTrue($this->dbInfo->tableHasForeignKey('test__posts', 'user_id', 'test__users', 'id'));
        $this->assertFalse($this->dbInfo->tableHasForeignKey('test__posts', 'user_id', 'test__users', 'nonexistent'));
    }

    public function testTableHasIndex()
    {
        // Use explicit index name created in setUp
        $this->assertTrue($this->dbInfo->tableHasIndex('test__users', 'email_idx'));
        $this->assertFalse($this->dbInfo->tableHasIndex('test__users', 'nonexistent_idx'));
    }

    public function testTableHasIndexOnColumns()
    {
        $this->assertTrue($this->dbInfo->tableHasIndexOnColumns('test__users', ['email']));
        $this->assertTrue($this->dbInfo->tableHasIndexOnColumns('test__posts', ['content']));
        $this->assertFalse($this->dbInfo->tableHasIndexOnColumns('test__users', ['nonexistent']));
    }
}
