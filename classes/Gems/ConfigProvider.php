<?php

namespace Gems;

use Gems\Cache\CacheFactory;
use Gems\Config\App;
use Gems\Legacy\LegacyController;
use Gems\Middleware\SecurityHeadersMiddleware;
use Gems\Factory\EventDispatcherFactory;
use Gems\Factory\MonologFactory;
use Gems\Factory\ProjectOverloaderFactory;
use Gems\Route\ModelSnippetActionRouteHelpers;
use Gems\Translate\TranslationFactory;
use Laminas\Diactoros\Response\JsonResponse;
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
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

class ConfigProvider
{
    use ModelSnippetActionRouteHelpers;

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
        $appConfigProvider = new App();
        $surveyConfigProvider = new Survey();

        return [
            'app'           => $appConfigProvider(),
            'cache'         => $this->getCacheSettings(),
            'contact'       => $this->getContactSettings(),
            'db'            => $this->getDbSettings(),
            'dependencies'  => $this->getDependencies(),
            'email'         => $this->getEmailSettings(),
            'locale'        => $this->getLocaleSettings(),
            'log'           => $this->getLoggers(),
            'monitor'       => $this->getMonitorSettings(),
            'survey'        => $surveyConfigProvider(),
            'migrations'    => $this->getMigrations(),

            'password'      => $this->getPasswordSettings(),
            'routes'        => $this->getRoutes(),
            'security'      => $this->getSecuritySettings(),
            'templates'     => $this->getTemplates(),
            'translations'  => $this->getTranslationSettings(),
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

    protected function getContactSettings(): array
    {
        return [
            'docsUrl' => 'https://gemstracker.org/wiki/doku.php',
            'manualUrl' => 'https://gemstracker.org/wiki/doku.php?id=userzone:userdoc:start'
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

                // Translation
                TranslatorInterface::class => TranslationFactory::class,
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

    protected function getEmailSettings(): array
    {
        return [
            // BCC every sent mail to this address.
            'bcc' => null,

            // block any sending of mail.
            'block' => false,

            /* When set to 1 all mails are not sent to the
            suplied TO address, but redirects them to
            the current user or the current FROM address.
            This allows testing without altering respondent
            e-mail addresses. */
            'bounce' => false,

            // Default Template code for a Reset password mail
            'createAccountTemplate' => 'accountCreate',

            // Have the mail depend on the user's language setting
            'multiLanguage' => true,

            // Default Template code for a Create account mail
            'resetPasswordTemplate' => 'passwordReset',

            // Supply a general site FROM address.
            'site' => null,

            /* When set to 1 all staff mails are not sent to the
            suplied TO address, but redirects them to
            the current users or the FROM address. This allows
            testing without altering staff e-mail addresses.
            When not set, bounce is used. */
            'staffBounce' => false,
        ];
    }

    protected function getLocaleSettings(): array
    {
        return [
            'availableLocales' => [
                'en',
                'nl',
                'de',
                'fr',
            ],
            'default' => 'en',
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
            'cronLog' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => LogLevel::NOTICE,
                        'options' => [
                            'stream' =>  'data/logs/cron.log',
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

    protected function getMonitorSettings(): array
    {
        /*
         * List is monitor based settings. Each setting has an own array of 3 possible entries:
         * period:  The default wait period for [name]. When string ending with
         *          'd', 'h' or 'm' in days, hours or minutes, otherwise
         *     	    seconds. When not set default.period is used. 'never'
         *          disables the job, 0 does not.
         * from:    The from e-mail address to use for [name]. When not specified, the default.from is used.
         * to:      The to e-mail address(es) to use for [name], multiple
         *          addresses separated by commas. When not specified,
         *          default.to is used.
         *
         * Add a default setting to fall back to those
         */
        return [
            'cron' => [
                'period' => '1h',
                'from' => null,
                'to' => null,
            ],
            'maintenancemode' => [
                'period' => '1h',
                'from' => null,
                'to' => null,
            ],
        ];
    }

    protected function getPasswordSettings(): array
    {
        /**
         *
         */
        return [
            'default' => [
                'notTheName' => 1,
                'inPasswordList' => '../library/Gems/docs/weak-lst',
            ],
            'guest' => [
                'capsCount' => 1,
            ],
            'staff' => [
                'capsCount' => 1,
                'lowerCount' => 1,
                'minLength' => 8,
                'numCount' => 0,
                'notAlphaCount' => 1,
                'notAlphaNumCount' => 0,
                'maxAge' => 365,
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
            ...$this->createBrowseRoutes('track-builder.source', '/track-builder/source', 'pr.track-builder.source', \Gems_Default_SourceAction::class),
        ];
    }

    protected function getSecuritySettings(): array
    {
        return [
            'headers' => [
                JsonResponse::class => [
                    'Cache-Control' => 'no-store',
                    'Content-Security-Policy' => 'frame-ancestors \'none\'',
                    'Referrer-Policy' => 'no-referrer',
                    'Strict-Transport-Security' => 'max-age=31536000',
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'deny',
                ],
                'default' => [
                    'Content-Security-Policy' => 'default-src \'self\'',
                    'Feature-Policy' => 'camera \'none\'; microphone \'none\'; autoplay \'none\'',
                    'Permissions-Policy' => 'camera=(), microphone=(), autoplay=()',
                    'Referrer-Policy' => 'no-referrer',
                    'Strict-Transport-Security' => 'max-age=31536000',
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'deny',
                ]
            ],
        ];
    }

    protected function getTemplates(): array
    {
        return [
            'gems' => [__DIR__ . '/../../templates/Auth'],
        ];
    }

    protected function getTranslationSettings(): array
    {
        return [
            'paths' => [
                'gems' => [__DIR__ . '/../../languages'],
            ],
        ];
    }


}
