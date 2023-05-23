<?php

namespace Gems\Db;

class Dsn
{
    public static function fromConfig(array $config): ?string
    {
        if (isset($config['dsn'])) {
            return $config['dsn'];
        }
        $connectionName = $config['driver'] ?? null;
        if (!isset($config['database'])) {
            throw new \Exception('No database in config');
        }
        $dbName = $config['database'];
        $driverName = static::getDsnDriverName($connectionName);
        if ($driverName === 'sqlite') {
            return "$driverName:$dbName";
        }

        $host = $config['host'] ?? 'localhost';
        $dsnParts = [
            'host' => $host,
        ];

        if (isset($config['port'])) {
            $dsnParts['port'] = $config['port'];
        }
        $dsnParts['dbname'] = $dbName;
        if (isset($config['username'])) {
            $dsnParts['user'] = $config['username'];
        }
        if (isset($config['password'])) {
            $dsnParts['password'] = $config['password'];
        }
        if (isset($config['charset'])) {
            $dsnParts['charset'] = $config['charset'];
        }

        $dsn = "$driverName:";
        foreach($dsnParts as $key=>$value) {
            $dsn .= "$key=$value;";
        }

        return rtrim($dsn, ';');
    }

    public static function getDsnDriverName(?string $connection): string
    {
        return match (strtolower($connection)) {
            'pdo_sqlite', 'sqlite' => 'sqlite',
            'pdo_pgsql', 'pgsql' => 'pgsql',
            'sqlsrv', 'mssql' => 'mssql',
            default => 'mysql',
        };
    }
}