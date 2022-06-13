<?php

namespace Gems;

use Gems\Factory\EventDispatcherFactory;
use Gems\Factory\MonologFactory;
use Gems\Factory\ProjectOverloaderFactory;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zalt\Loader\ProjectOverloader;

class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return mixed[]
     */
    public function __invoke(): array
    {
        return [
            'db'           => $this->getDbSettings(),
            'dependencies' => $this->getDependencies(),
            //'templates'    => $this->getTemplates(),
            //'routes'       => $this->getRoutes(),
            'log'           => $this->getLoggers(),
        ];
    }

    /**
     * @return boolean[]|string[]
     */
    public function getDbSettings(): array
    {
        return [
            'driver'    => 'Mysqli',
            'host'      => getenv('DB_HOST'),
            'username'  => getenv('DB_USER'),
            'password'  => getenv('DB_PASS'),
            'database'  => getenv('DB_NAME'),
            'charset'   => 'utf8',
        ];
    }

    /**
     * Returns the container dependencies
     * @return mixed[]
     */
    public function getDependencies(): array
    {
        return [
            'factories'  => [
                EventDispatcher::class => EventDispatcherFactory::class,
                ProjectOverloader::class => ProjectOverloaderFactory::class,

                // Logs
                'embeddedLoginLog' => MonologFactory::class,
            ],
        ];
    }

    protected function getLoggers(): array
    {
        return [
            'embeddedLoginLog' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => LogLevel::NOTICE,
                        'options' => [
                            'stream' =>  'data/logs/embed-logging.log',
                        ],
                    ],
                ],
            ],
        ];
    }
}
