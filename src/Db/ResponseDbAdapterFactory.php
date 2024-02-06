<?php

namespace Gems\Db;

use Gems\Helper\Env;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ResponseDbAdapterFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $profiler = null;
        if (isset($_ENV['DB_PROFILE']) && $_ENV['DB_PROFILE'] === '1') {
            $profiler = new Profiler();
        }

        return new ResponseDbAdapter($this->getConfig(), null, null, $profiler);
    }

    public function getConfig(): array
    {
        return [
            'driver'    => 'pdo_mysql',
            'dsn'       => Env::get('RESPONSE_DB_DSN', $this->config['responseData']['dsn'] ?? Env::get('DB_DSN', $this->config['db']['dsn'] ?? null)),
            'host'      => Env::get('RESPONSE_DB_HOST', $this->config['responseData']['host'] ?? Env::get('DB_HOST', $this->config['db']['host'] ?? null)),
            'username'  => Env::get('RESPONSE_DB_USER', $this->config['responseData']['username'] ?? Env::get('DB_USER', $this->config['db']['username'] ?? null)),
            'password'  => Env::get('RESPONSE_DB_PASS', $this->config['responseData']['password'] ?? Env::get('DB_PASS', $this->config['db']['password'] ?? null)),
            'database'  => Env::get('RESPONSE_DB_NAME', $this->config['responseData']['database'] ?? Env::get('DB_NAME', $this->config['db']['database'] ?? null)),
            'options'   => $this->config['responseData']['options'] ?? $this->config['db']['options'] ?? [],
        ];
    }
}