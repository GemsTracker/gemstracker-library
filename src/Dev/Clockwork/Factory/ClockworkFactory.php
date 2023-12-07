<?php

namespace Gems\Dev\Clockwork\Factory;

use Clockwork\Authentication\NullAuthenticator;
use Clockwork\Clockwork;
use Clockwork\DataSource\DBALDataSource;
use Clockwork\DataSource\PhpDataSource;
use Clockwork\Storage\FileStorage;
use Doctrine\DBAL\Connection;
use Gems\Dev\Clockwork\DataSource\LaminasDbDataSource;
use Gems\Dev\Clockwork\DataSource\MonologDataSource;
use Gems\Dev\Clockwork\DataSource\TwigDataSource;
use Gems\Dev\Clockwork\DataSource\ZendDbDataSource;
use Gems\Log\Loggers;
use Laminas\Db\Adapter\Adapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Twig\Environment;

class ClockworkFactory implements FactoryInterface
{
    protected string $defaultStorageDir = 'data/clockwork';

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Clockwork
    {
        $clockwork = new Clockwork();
        $clockwork->storage(new FileStorage($this->defaultStorageDir));
        $clockwork->authenticator(new NullAuthenticator());
        $clockwork->addDataSource(new PhpDataSource());

        // Add twig
        $twigEnvironment = $container->get(Environment::class);
        $clockwork->addDataSource(new TwigDataSource($twigEnvironment));

        // Add Laminas Db Profiler
        $adapter = $container->get(Adapter::class);
        $clockwork->addDataSource(new LaminasDbDataSource($adapter));

        // Add Zend Db Profiler
        $adapter = $container->get(\Zend_Db_Adapter_Abstract::class);
        $clockwork->addDataSource(new ZendDbDataSource($adapter));

        // Add Doctrine DataSource
        $adapter = $container->get(Connection::class);
        $clockwork->addDataSource(new DBALDataSource($adapter));

        // Add Monolog
        /**
         * @var Loggers $loggerRepository
         */
        $loggerRepository = $container->get(Loggers::class);
        $loggers = $loggerRepository->listLoggers();
        foreach($loggers as $loggerName) {
            /**
             * @var Logger $logger
             */
            $logger = $loggerRepository->getLogger($loggerName);
            $clockwork->addDataSource(new MonologDataSource($logger));
        }

        return $clockwork;
    }
}