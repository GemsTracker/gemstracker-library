<?php

namespace Gems\Db;

use Gems\Helper\Env;

class ConfigRepository
{
    public function __construct(
        protected readonly array $config,
    )
    {
    }

    public function getConfig(): array
    {
        return [
            'driver'    => 'pdo_mysql',
            'dsn'       => Env::get('DB_DSN', $this->config['db']['dsn'] ?? null),
            'host'      => Env::get('DB_HOST', $this->config['db']['host'] ?? null),
            'username'  => Env::get('DB_USER', $this->config['db']['username'] ?? null),
            'password'  => Env::get('DB_PASS', $this->config['db']['password'] ?? null),
            'database'  => Env::get('DB_NAME', $this->config['db']['database'] ?? null),
            'options'   => $this->config['db']['options'] ?? [],
        ];
    }

    public function getDsn(): string|null
    {
        $config = $this->getConfig();

        return $config['dsn'] ?? null;
    }



    public function getDoctrineConfig(): array
    {
        $config = $this->getConfig();

        return [
            'driver' => strtolower($config['driver']),
            'host' => $config['host'],
            'user' => $config['username'],
            'password' => $config['password'],
            'dbname' => $config['database'],
            'driverOptions' => $config['options'],
        ];
    }


}