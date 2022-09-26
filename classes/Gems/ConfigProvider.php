<?php

namespace Gems;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Gems\Auth\Acl\AclFactory;
use Gems\Auth\Acl\ConfigRoleAdapter;
use Gems\Auth\Acl\RoleAdapterInterface;
use Gems\Cache\CacheFactory;
use Gems\Command\ClearConfigCache;
use Gems\Command\ConsumeMessageCommandFactory;
use Gems\Command\DebugMessageCommandFactory;
use Gems\Config\App;
use Gems\Config\Messenger;
use Gems\Config\Route;
use Gems\Config\Survey;
use Gems\Factory\DoctrineDbalFactory;
use Gems\Factory\EventDispatcherFactory;
use Gems\Factory\MonologFactory;
use Gems\Factory\ProjectOverloaderFactory;
use Gems\Command\GenerateApplicationKey;
use Gems\Factory\ReflectionAbstractFactory;
use Gems\Messenger\MessengerFactory;
use Gems\Factory\DoctrineOrmFactory;
use Gems\Route\ModelSnippetActionRouteHelpers;
use Gems\Translate\TranslationFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Csrf\FlashCsrfGuardFactory;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Session\Cache\CacheSessionPersistence;
use Mezzio\Session\Cache\CacheSessionPersistenceFactory;
use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\Ext\PhpSessionPersistenceFactory;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionMiddlewareFactory;
use Mezzio\Session\SessionPersistenceInterface;
use MUtil\Model;
use MUtil\Translate\Translator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\DebugCommand;
use Symfony\Component\Messenger\Command\StopWorkersCommand;
use Symfony\Component\Messenger\MessageBusInterface;
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
        $appSettings = new App();
        $messengerSettings = new Messenger();
        $routeSettings = new Route();
        $surveySettings = new Survey();

        return [
            'app'           => $appSettings(),
            'cache'         => $this->getCacheSettings(),
            'contact'       => $this->getContactSettings(),
            'console'       => $this->getConsoleSettings(),
            'db'            => $this->getDbSettings(),
            'dependencies'  => $this->getDependencies(),
            'email'         => $this->getEmailSettings(),
            'events'        => $this->getEventSubscribers(),
            'locale'        => $this->getLocaleSettings(),
            'log'           => $this->getLoggers(),
            'messenger'     => $messengerSettings(),
            'monitor'       => $this->getMonitorSettings(),
            'survey'        => $surveySettings(),
            'migrations'    => $this->getMigrations(),
            'password'      => $this->getPasswordSettings(),
            'permissions'   => $this->getPermissions(),
            'roles'         => $this->getRoles(),
            'routes'        => $routeSettings(),
            'security'      => $this->getSecuritySettings(),
            'session'       => $this->getSession(),
            'sites'         => $this->getSitesSettings(),
            'templates'     => $this->getTemplates(),
            'twofactor'     => $this->getTwoFactor(),
            'tokens'        => $this->getTokenSettings(),
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

    /**
     * @return mixed[]
     */
    public function getConsoleSettings(): array
    {
        return [
            'commands' => [
                ClearConfigCache::class,
                GenerateApplicationKey::class,

                // Messenger
                ConsumeMessagesCommand::class,
                StopWorkersCommand::class,
                DebugCommand::class,
            ],
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
                Acl::class => AclFactory::class,

                // Logs
                'LegacyLogger' => MonologFactory::class,
                'embeddedLoginLog' => MonologFactory::class,

                // Cache
                \Symfony\Component\Cache\Adapter\AdapterInterface::class => CacheFactory::class,

                // Doctrine
                Connection::class => DoctrineDbalFactory::class,
                EntityManagerInterface::class => DoctrineOrmFactory::class,

                // Session
                SessionMiddleware::class => SessionMiddlewareFactory::class,
                CacheSessionPersistence::class => CacheSessionPersistenceFactory::class,
                PhpSessionPersistence::class => PhpSessionPersistenceFactory::class,
                FlashMessageMiddleware::class => FlashMessageMiddleware::class,
                CsrfMiddleware::class => CsrfMiddlewareFactory::class,

                // Translation
                TranslatorInterface::class => TranslationFactory::class,

                // Messenger
                MessageBusInterface::class => MessengerFactory::class,
                // message.bus.other => [MessengerFactory::class, 'message.bus.other'],

                // message.transport.name => TransportFactory::class,

                ConsumeMessagesCommand::class => ConsumeMessageCommandFactory::class,
                DebugCommand::class => DebugMessageCommandFactory::class,

            ],
            'abstract_factories' => [
                ReflectionAbstractFactory::class,
            ],
            'aliases' => [
                EventDispatcherInterface::class => EventDispatcher::class,
                \Symfony\Component\EventDispatcher\EventDispatcherInterface::class => EventDispatcher::class,

                // Cache
                \Psr\Cache\CacheItemPoolInterface::class => \Symfony\Component\Cache\Adapter\AdapterInterface::class,

                // Session
                //SessionPersistenceInterface::class => CacheSessionPersistence::class,
                SessionPersistenceInterface::class => PhpSessionPersistence::class,
                CsrfGuardFactoryInterface::class => FlashCsrfGuardFactory::class,

                RoleAdapterInterface::class => ConfigRoleAdapter::class,

                // Translation
                Translator::class => TranslatorInterface::class,
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

    protected function getEventSubscribers(): array
    {
        return [
            EventSubscriber::class,
            \Gems\Communication\EventSubscriber::class,
            \Gems\AuthNew\EventSubscriber::class,
        ];
    }

    protected function getLocaleSettings(): array
    {
//        $jstUrl = $this->basepath->getBasePath() . '/gems/js';

        $dateFormat = [
            'dateFormat'   => 'd-m-Y',
            'description'  => 'dd-mm-yyyy',
            'jQueryParams' => [
                'changeMonth' => true, 
                'changeYear'  => true, 
                'duration'    => 'fast',
                ],
            'size'         => 10,
            'storageFormat' => 'Y-m-d',
            ];

        $timeFormat = [
            'dateFormat'   => 'H:i',
            'description'  => 'hh:mm',
            'jQueryParams' => [
                'duration'    => 'fast', 
                'stepMinute'  => 5, 
//                'timeJsUrl'   => $jstUrl,
                ],
            'size'        => 6,
            'storageFormat' => 'H:i:s',
            ];

        $dateTimeFormat = [
            'dateFormat'   => 'd-m-Y H:i',
            'description'  => 'dd-mm-yyyy hh:mm',
            'jQueryParams' => [
                'changeMonth' => true,
                'changeYear'  => true,
                'duration'    => 'fast',
                'stepMinute'  => 5,
                'size'        => 8,
//                'timeJsUrl'   => $jstUrl,
            ],
            'size'         => 16,
            'storageFormat' => 'Y-m-d H:i:s',
        ];

        return [
            'availableLocales' => [
                'en',
                'nl',
                'de',
                'fr',
            ],
            'default' => 'en',
            'defaultTypes' => [
                Model::TYPE_DATE     => $dateFormat,
                Model::TYPE_DATETIME => $dateTimeFormat,
                Model::TYPE_TIME     => $timeFormat,
            ],
            'localeTypes' => [
                'nl' => [
                    Model::TYPE_DATE     => ['description' => 'tt-mm-jjjj'],
                    Model::TYPE_DATETIME => ['description' => 'tt-mm-jjjj uu:mm'],
                    Model::TYPE_TIME     => ['description' => 'uu:mm'],
                ],
                'de' => [
                    Model::TYPE_DATE     => ['description' => 'dd-mm-jjjj'],
                    Model::TYPE_DATETIME => ['description' => 'dd-mm-jjjj ss:mm'],
                    Model::TYPE_TIME     => ['description' => 'ss:mm'],
                ],
                'fr' => [
                    Model::TYPE_DATE     => ['description' => 'jj-mm-aaaa'],
                    Model::TYPE_DATETIME => ['description' => 'jj-mm-aaaa hh:mm'],
                ],
            ],
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

    protected function getSession(): array
    {
        return [
            'max_away_time' => 15 * 60,
            'max_total_time' => 10 * 60 * 60,
        ];
    }

    protected function getSitesSettings(): array
    {
        return [
            //'useDatabase' => true,
            //'allow' => []
        ];
    }

    protected function getTemplates(): array
    {
        return [
            'gems' => [__DIR__ . '/../../templates/Auth'],
            'paths' => [
                'gems' => [__DIR__ . '/../../templates/gems'],
                'layout' => [__DIR__ . '/../../templates/layout'],
                'menu' => [__DIR__ . '/../../templates/menu'],
            ],
        ];
    }

    protected function getTwoFactor(): array
    {
        return [
            'methods' => [
                'SmsHotp' => [
                    'codeLength' => 6,
                    'codeValidSeconds' => 300,
                    'maxSendOtpAttempts' => 2,
                    'maxSendOtpAttemptsPerPeriod' => 3600,
                    'maxVerifyOtpAttempts' => 5,
                ],
                'MailHotp' => [
                    'codeLength' => 6,
                    'codeValidSeconds' => 300,
                    'maxSendOtpAttempts' => 2,
                    'maxSendOtpAttemptsPerPeriod' => 3600,
                    'maxVerifyOtpAttempts' => 5,
                ],
                'AppTotp' => [
                    'codeLength' => 6,
                    'codeValidSeconds' => 300,
                    'maxVerifyOtpAttempts' => 5,
                ],
            ],
        ];
    }

    protected function getTokenSettings(): array
    {
        /**
         * chars:  characters allowed in a token.
         * format: format string to show to user for input of
         *         token. The \ backslash is used as escape
         *         character for characters that are fixed.
         * from:   commonly mistaken input characters to correct.
         * to:     characters to translate from characters to.
         * case:   optional: 1|0. If the token should be
         *         treated case-sensitive. If missing the token
         *         is case-sensitive when chars contain
         *         uppercase characters.
         * reuse:  days tokens can be used:
         *         -1 = not at all
         *         0 = only today (default and required for looping)
         *         1 = only up to yesterday's tokens
         */
        return [
            'chars'  => '23456789abcdefghijklmnopqrstuvwxyz',
            'format' => 'XXXX\-XXXX',
            'from'   => '01',
            'to'     => 'ol',
        ];
    }

    protected function getTranslationSettings(): array
    {
        return [
            'databaseFields' => false,
            'paths' => [
                'gems' => [__DIR__ . '/../../languages'],
            ],
        ];
    }

    /**
     * Returns the permissions defined by this module
     *
     * @return mixed[]
     */
    protected function getPermissions(): array
    {
        return [
        ];
    }

    /**
     * Returns the roles defined by this project
     *
     * @return mixed[]
     */
    public function getRoles(): array
    {
        return [
            'staff' => [],
            'super' => [],
        ];
    }
}
