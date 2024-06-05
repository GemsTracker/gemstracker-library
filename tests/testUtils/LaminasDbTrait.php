<?php

namespace GemsTest\testUtils;

use PDO;
use Gems\Helper\Env;
use Laminas\Db\Adapter\Adapter;

trait LaminasDbTrait
{
    protected Adapter $db;

    protected PDO $pdo;

    public function initDb(): void
    {
        $dsn = $this->getDsn();

        $this->pdo = new PDO($dsn);


        if ($this->getDsnDriverName() === 'sqlite') {
            SqliteFunctions::addSqlFunctonsToPdoAdapter($this->pdo);
        }

        $this->db = new Adapter(new \Laminas\Db\Adapter\Driver\Pdo\Pdo($this->pdo));
    }

    protected function getDsn(): ?string
    {
        $dsn = Env::get('DB_DSN');
        if ($dsn) {
            return $dsn;
        }

        $driverName = $this->getDsnDriverName();
        $databaseName = Env::get('DB_DATABASE');
        if ($driverName === null) {
            $driverName = 'sqlite';
            $databaseName = ':memory:';
        }

        $dsn = $driverName;
        if ($driverName !== 'sqlite') {
            $dsn .= sprintf('host=%s;dbname=%s;user=%s;password=%s;',
                Env::get('DB_HOST'),
                Env::get('DB_DATABASE'),
                Env::get('DB_USERNAME'),
                Env::get('DB_PASSWORD')
            );
            if ($charset = Env::get('DB_CHARSET')) {
                $dsn .= sprintf('charset=%s', $charset);
            }
            return $dsn;
        }
        $dsn .= sprintf(':%s', $databaseName);
        return $dsn;
    }

    protected function getDsnDriverName(): string|null
    {
        return match(Env::get('DB_CONNECTION')) {
            'Pdo_Mysql', 'Mysqli', 'mysql' => 'mysql',
            'Pdo_Sqlite', 'sqlite' => 'sqlite',
            'Pdo_pgsql', 'Pgsql', 'pgsql' => 'pgsql',
            'Sqlsrv', 'mssql' => 'mssql',
            default => null,
        };
    }
}