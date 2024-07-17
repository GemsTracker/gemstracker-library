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

        return new ResponseDbAdapter($this->getConfig($container), null, null, $profiler);
    }

    public function getConfig(ContainerInterface $container): array
    {
        $config = $container->get('config');
        return [
            'driver'    => 'pdo_mysql',
            'dsn'       => Env::get('RESPONSE_DB_DSN', $config['responseData']['dsn'] ?? Env::get('DB_DSN', $config['db']['dsn'] ?? null)),
            'host'      => Env::get('RESPONSE_DB_HOST', $config['responseData']['host'] ?? Env::get('DB_HOST', $config['db']['host'] ?? null)),
            'username'  => Env::get('RESPONSE_DB_USER', $config['responseData']['username'] ?? Env::get('DB_USER', $config['db']['username'] ?? null)),
            'password'  => Env::get('RESPONSE_DB_PASS', $config['responseData']['password'] ?? Env::get('DB_PASS', $config['db']['password'] ?? null)),
            'database'  => Env::get('RESPONSE_DB_NAME', $config['responseData']['database'] ?? Env::get('DB_NAME', $config['db']['database'] ?? null)),
            'options'   => $config['responseData']['options'] ?? $config['db']['options'] ?? [],
        ];
    }
}