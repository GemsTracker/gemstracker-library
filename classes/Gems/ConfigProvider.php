<?php

namespace Gems;

use Gems\Cache\CacheFactory;
use Gems\Legacy\LegacyController;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;
use Gems\Factory\EventDispatcherFactory;
use Gems\Factory\MonologFactory;
use Gems\Factory\ProjectOverloaderFactory;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Csrf\FlashCsrfGuardFactory;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Session\Cache\CacheSessionPersistence;
use Mezzio\Session\Cache\CacheSessionPersistenceFactory;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionMiddlewareFactory;
use Mezzio\Session\SessionPersistenceInterface;
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
            'cache'        => $this->getCacheSettings(),
            'db'           => $this->getDbSettings(),
            'dependencies' => $this->getDependencies(),
            'log'          => $this->getLoggers(),
            'migrations'   => $this->getMigrations(),
            //'templates'    => $this->getTemplates(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getCacheSettings(): array
    {
        $cacheAdapter = null;
        if ($envAdapter = getenv('CACHE_ADAPTER')) {
            $cacheAdapter = $envAdapter;
        }

        return [
            'adapter' => $cacheAdapter,
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
                'LegacyLogger' => MonologFactory::class,
                'embeddedLoginLog' => MonologFactory::class,

                // Cache
                \Symfony\Component\Cache\Adapter\AdapterInterface::class => CacheFactory::class,

                // Session
                SessionMiddleware::class => SessionMiddlewareFactory::class,
                CacheSessionPersistence::class => CacheSessionPersistenceFactory::class,
                FlashMessageMiddleware::class => FlashMessageMiddleware::class,
                CsrfMiddleware::class => CsrfMiddlewareFactory::class,
            ],
            'abstract_factories' => [
                ReflectionBasedAbstractFactory::class,
            ],
            'aliases' => [
                // Cache
                \Psr\Cache\CacheItemPoolInterface::class => \Symfony\Component\Cache\Adapter\AdapterInterface::class,

                // Session
                SessionPersistenceInterface::class => CacheSessionPersistence::class,
                CsrfGuardFactoryInterface::class => FlashCsrfGuardFactory::class,
            ]
        ];
    }

    protected function getLoggers(): array
    {
        return [
            'LegacyLogger' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => LogLevel::NOTICE,
                        'options' => [
                            'stream' =>  'data/logs/errors.log',
                        ],
                    ],
                ],
            ],
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

    protected function getMigrations(): array
    {
        return [
            'migrations' => [
                __DIR__ . '/../../configs/db/migrations',
            ],
            'seeds' => [
                __DIR__ . '/../../configs/db/seeds',
            ],
        ];
    }

    protected function getRoutes(): array
    {
        return [
            [
                'name' => 'setup.reception.index',
                'path' => '/setup/reception/index',
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LegacyController::class,
                ],
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => \Gems_Default_ReceptionAction::class,
                    'action' => 'index',
                ]
            ],
            [
                'name' => 'track-builder.source.index',
                'path' => '/track-builder/source/index',
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LegacyController::class,
                ],
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => \Gems_Default_SourceAction::class,
                    'action' => 'index',
                ]
            ],
            [
                'name' => 'track-builder.source.create',
                'path' => '/track-builder/source/create',
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LegacyCurrentUserMiddleware::class,
                    LegacyController::class,
                ],
                'allowed_methods' => ['GET', 'POST'],
                'options' => [
                    'controller' => \Gems_Default_SourceAction::class,
                    'action' => 'create',
                ]
            ],
        ];
    }
}
