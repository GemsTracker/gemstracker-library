<?php

namespace Db;

use Gems\Db\Dsn;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    /**
     * @dataProvider dsnDataProvider
     */
    public function testFromConfig($config, $expectedDsn)
    {
        $this->assertEquals($expectedDsn, Dsn::fromConfig($config));
    }

    public static function dsnDataProvider()
    {
        return [
            [['driver' => 'sqlite', 'database' => 'test_database'], 'sqlite:test_database'],
            [['driver' => 'pdo_sqlite', 'database' => 'test_db'], 'sqlite:test_db'],
            [['driver' => 'pdo_sqlite', 'dsn' => 'sqlite:test_db', 'database' => 'test_db'], 'sqlite:test_db'],
            [['driver' => 'pdo_mysql', 'database' => 'test_db'], 'mysql:host=localhost;dbname=test_db'],
            [['driver' => 'pdo_mysql', 'host' => 'database.test', 'database' => 'test_db'], 'mysql:host=database.test;dbname=test_db'],
            [['driver' => 'pdo_mysql', 'host' => 'localhost', 'database' => 'test_db', 'username' => 'root'], 'mysql:host=localhost;dbname=test_db;user=root'],
            [['driver' => 'pdo_mysql', 'host' => 'localhost', 'database' => 'test_db', 'username' => 'root', 'password' => 'test123'], 'mysql:host=localhost;dbname=test_db;user=root;password=test123'],
            [['driver' => 'pdo_mysql', 'host' => 'localhost', 'port' => '1234', 'database' => 'test_db', 'username' => 'root', 'password' => 'test123'], 'mysql:host=localhost;port=1234;dbname=test_db;user=root;password=test123'],
            [['driver' => 'mysql', 'host' => 'localhost', 'port' => '1234', 'charset' => 'utf8', 'database' => 'test_db', 'username' => 'root', 'password' => 'test123'], 'mysql:host=localhost;port=1234;dbname=test_db;user=root;password=test123;charset=utf8'],
        ];
    }
}