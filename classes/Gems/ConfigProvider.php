<?php

namespace Gems;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Gems\Agenda\Agenda;
use Gems\Agenda\AgendaFactory;
use Gems\Auth\Acl\AclFactory;
use Gems\Auth\Acl\DbGroupAdapter;
use Gems\Auth\Acl\DbRoleAdapter;
use Gems\Auth\Acl\GroupAdapterInterface;
use Gems\Auth\Acl\RoleAdapterInterface;
use Gems\Cache\CacheFactory;
use Gems\Command\ClearConfigCache;
use Gems\Command\ConsumeMessageCommandFactory;
use Gems\Command\DebugMessageCommandFactory;
use Gems\Condition\Comparator\ComparatorAbstract;
use Gems\Condition\RoundConditionInterface;
use Gems\Condition\TrackConditionInterface;
use Gems\Config\App;
use Gems\Config\AutoConfig\MessageHandlers;
use Gems\Config\Messenger;
use Gems\Config\Route;
use Gems\Config\Survey;
use Gems\Error\ErrorLogEventListenerDelegatorFactory;
use Gems\Factory\DoctrineDbalFactory;
use Gems\Factory\DoctrineOrmFactory;
use Gems\Factory\EventDispatcherFactory;
use Gems\Factory\MonologFactory;
use Gems\Factory\PdoFactory;
use Gems\Factory\ProjectOverloaderFactory;
use Gems\Command\GenerateApplicationKey;
use Gems\Factory\ReflectionAbstractFactory;
use Gems\Log\ErrorLogger;
use Gems\Messenger\MessengerFactory;
use Gems\Model\MetaModelLoader as GemsMetaModelLoader;
use Gems\Model\MetaModelLoaderFactory;
use Gems\Messenger\TransportFactory;
use Gems\Route\ModelSnippetActionRouteHelpers;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\SnippetsLoader\GemsSnippetResponderFactory;
use Gems\Tracker\TrackEvent\RespondentChangedEventInterface;
use Gems\Tracker\TrackEvent\RoundChangedEventInterface;
use Gems\Tracker\TrackEvent\SurveyBeforeAnsweringEventInterface;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use Gems\Tracker\TrackEvent\SurveyDisplayEventInterface;
use Gems\Tracker\TrackEvent\TrackBeforeFieldUpdateEventInterface;
use Gems\Tracker\TrackEvent\TrackCalculationEventInterface;
use Gems\Tracker\TrackEvent\TrackCompletedEventInterface;
use Gems\Tracker\TrackEvent\TrackFieldUpdateEventInterface;
use Gems\Translate\TranslationFactory;
use Gems\Twig\Csrf;
use Gems\Twig\Trans;
use Gems\Twig\Vite;
use Gems\Util\Lock\LockFactory;
use Gems\Util\Lock\MaintenanceLock;
use Gems\Util\Lock\Storage\FileLock;
use Gems\Util\Lock\Storage\LockStorageAbstract;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterServiceFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Csrf\FlashCsrfGuardFactory;
use Gems\Middleware\FlashMessageMiddleware;
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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\DebugCommand;
use Symfony\Component\Messenger\Command\StopWorkersCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\StringLoaderExtension;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelConfigProvider;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Sql\Laminas\LaminasRunner;
use Zalt\Model\Sql\Laminas\LaminasRunnerFactory;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetLoaderFactory;
use Zalt\SnippetsLoader\SnippetResponderInterface;
use Zalt\SnippetsLoader\SnippetMiddleware;
use Zalt\SnippetsLoader\SnippetMiddlewareFactory;

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
            'temp_config' => [ // TODO: Temporary
                'disable_privileges' => true,
            ],
            'app'           => $appSettings(),
            'autoconfig'    => $this->getAutoConfigSettings(),
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
            'model'         => $this->getModelSettings(),
            'monitor'       => $this->getMonitorSettings(),
            'migrations'    => $this->getMigrations(),
            'password'      => $this->getPasswordSettings(),
            'supplementary_privileges'   => $this->getSupplementaryPrivileges(),
            'routes'        => $routeSettings(),
            'security'      => $this->getSecuritySettings(),
            'session'       => $this->getSession(),
            'sites'         => $this->getSitesSettings(),
            'style'         => 'gems.scss',
            'survey'        => $surveySettings(),
            'templates'     => $this->getTemplates(),
            'twig'          => $this->getTwigSettings(),
            'twofactor'     => $this->getTwoFactor(),
            'tokens'        => $this->getTokenSettings(),
            'translations'  => $this->getTranslationSettings(),
        ];
    }

    public function getAutoConfigSettings(): array
    {
        return [
            'settings' => [
                'implements' => [
                    RoundConditionInterface::class => ['config' => 'tracker.conditions.round'],
                    TrackConditionInterface::class => ['config' => 'tracker.conditions.track'],
                    ExtensionInterface::class => ['config' => 'twig.extensions'],
                    RespondentChangedEventInterface::class => ['config' => 'tracker.trackEvents.Respondent/Change'],
                    TrackCalculationEventInterface::class => ['config' => 'tracker.trackEvents.Track/Calculate'],
                    TrackCompletedEventInterface::class => ['config' => 'tracker.trackEvents.Track/Completed'],
                    TrackBeforeFieldUpdateEventInterface::class => ['config' => 'tracker.trackEvents.Track/BeforeFieldUpdate'],
                    TrackFieldUpdateEventInterface::class => ['config' => 'tracker.trackEvents.Track/FieldUpdate'],
                    RoundChangedEventInterface::class => ['config' => 'tracker.trackEvents.Round/Changed'],
                    SurveyBeforeAnsweringEventInterface::class => ['config' => 'tracker.trackEvents.Survey/BeforeAnswering'],
                    SurveyCompletedEventInterface::class => ['config' => 'tracker.trackEvents.Survey/Completed'],
                    SurveyDisplayEventInterface::class => ['config' => 'tracker.trackEvents.Survey/Display'],
                    EventSubscriberInterface::class => ['config' => 'events.subscribers'],
                ],
                'extends' => [
                    ComparatorAbstract::class => ['config' => 'tracker.conditions.comparators'],
                ],
                'attribute' => [
                    AsCommand::class => ['config' => 'console.commands'],
                    AsMessageHandler::class => MessageHandlers::class,
                ]
            ],
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
            'allow' => true,
            'role' => 'super',
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
            'driver'    => 'pdo_mysql',
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
            'delegators' => [
                \Laminas\Stratigility\Middleware\ErrorHandler::class => [
                    ErrorLogEventListenerDelegatorFactory::class,
                ],
            ],
            'factories'  => [
                EventDispatcher::class => EventDispatcherFactory::class,
                ProjectOverloader::class => ProjectOverloaderFactory::class,
                Acl::class => AclFactory::class,
                Agenda::class => AgendaFactory::class,

                // Logs
                'LegacyLogger' => MonologFactory::class,
                'embeddedLoginLog' => MonologFactory::class,
                ErrorLogger::class => MonologFactory::class,

                // Cache
                \Symfony\Component\Cache\Adapter\AdapterInterface::class => CacheFactory::class,

                // Database
                \PDO::class => PdoFactory::class,

                // Laminas DB
                Adapter::class => AdapterServiceFactory::class,

                // Doctrine
                Connection::class => DoctrineDbalFactory::class,
                EntityManagerInterface::class => DoctrineOrmFactory::class,

                // Session
                SessionMiddleware::class => SessionMiddlewareFactory::class,
                CacheSessionPersistence::class => CacheSessionPersistenceFactory::class,
                PhpSessionPersistence::class => PhpSessionPersistenceFactory::class,
                FlashMessageMiddleware::class => fn () => new FlashMessageMiddleware(DecoratedFlashMessages::class),
                CsrfMiddleware::class => CsrfMiddlewareFactory::class,

                // Translation
                TranslatorInterface::class => TranslationFactory::class,

                // Messenger
                MessageBusInterface::class => MessengerFactory::class,
                // messenger.bus.other => [MessengerFactory::class, 'message.bus.other'],

                'messenger.transport.default' => TransportFactory::class,
                // messenger.transport.name => TransportFactory::class,

                ConsumeMessagesCommand::class => ConsumeMessageCommandFactory::class,
                DebugCommand::class => DebugMessageCommandFactory::class,

                // Locks
                MaintenanceLock::class => [LockFactory::class, FileLock::class],

                LaminasRunner::class => LaminasRunnerFactory::class,
                GemsMetaModelLoader::class => MetaModelLoaderFactory::class,
                
                SnippetLoader::class => SnippetLoaderFactory::class,
                SnippetMiddleware::class => SnippetMiddlewareFactory::class,
                GemsSnippetResponder::class => GemsSnippetResponderFactory::class, 
            ],
            'abstract_factories' => [
                ReflectionAbstractFactory::class,
            ],
            'aliases' => [
                EventDispatcherInterface::class => EventDispatcher::class,
                \Symfony\Component\EventDispatcher\EventDispatcherInterface::class => EventDispatcher::class,

                // Cache
                \Psr\Cache\CacheItemPoolInterface::class => \Symfony\Component\Cache\Adapter\AdapterInterface::class,

                // Messenger
                'messenger.bus.default' => MessageBusInterface::class,

                // Session
                //SessionPersistenceInterface::class => CacheSessionPersistence::class,
                SessionPersistenceInterface::class => PhpSessionPersistence::class,
                CsrfGuardFactoryInterface::class => FlashCsrfGuardFactory::class,

                RoleAdapterInterface::class => DbRoleAdapter::class,
                GroupAdapterInterface::class => DbGroupAdapter::class,

                MetaModelLoader::class => GemsMetaModelLoader::class,

                // Default lock storage
                LockStorageAbstract::class => FileLock::class,
                
                // Translation
                Translator::class => TranslatorInterface::class,
                SnippetResponderInterface::class => GemsSnippetResponder::class,
                \MUtil\Snippets\SnippetLoaderInterface::class => SnippetLoader::class,
                
                SqlRunnerInterface::class => LaminasRunner::class,
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

            // Default Template code for a Create account mail
            'createAccountTemplate' => 'accountCreate',

            // Have the mail depend on the user's language setting
            'multiLanguage' => true,

            // Default Template code for a Reset password mail
            'resetPasswordTemplate' => 'passwordReset',

            // Default Template code for a Reset tfa mail
            'resetTfaTemplate' => 'tfaReset',

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
            'subscribers' => [],
            'listeners' => [],
        ];
    }

    protected function getLocaleSettings(): array
    {
//        $jstUrl = $this->basepath->getBasePath() . '/gems/js';

        $dateFormat = [
            'dateFormat'   => 'd-m-Y',
            'description'  => 'dd-mm-yyyy',
            'datePickerSettings' => [],
            'size'         => 10,
            'storageFormat' => 'Y-m-d',
            ];

        $timeFormat = [
            'dateFormat'   => 'H:i',
            'description'  => 'hh:mm',
            'datePickerSettings' => [
                'minutesStep'  => 5,
            ],
            'size'        => 6,
            'storageFormat' => 'H:i:s',
            ];

        $dateTimeFormat = [
            'dateFormat'   => 'd-m-Y H:i',
            'description'  => 'dd-mm-yyyy hh:mm',
            'datePickerSettings' => [
                'minutesStep'  => 5,
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
            ErrorLogger::class => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => LogLevel::DEBUG,
                        'options' => [
                            'stream' => 'data/logs/php-error.log',
                        ],
                    ],
                ],
            ],
            'LegacyLogger' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => LogLevel::NOTICE,
                        'options' => [
                            'stream' => 'data/logs/errors.log',
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
                            'stream' => 'data/logs/cron.log',
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
                            'stream' => 'data/logs/embed-logging.log',
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

    protected function getModelSettings(): array
    {
        $settings = MetaModelConfigProvider::getConfig();
        $settings['translateDatabaseFields'] = true;
        
        return $settings;
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
            'max_away_time' => 30 * 60,
            'max_total_time' => 10 * 60 * 60,
            'max_idle_time' => 60 * 60,
            'auth_poll_interval' => 60,
            'idle_warning_before_logout' => 28 * 60,
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
            'paths' => [
                'gems' => [__DIR__ . '/../../templates/gems'],
                'layout' => [__DIR__ . '/../../templates/layout'],
                'mail' => [__DIR__ . '/../../templates/mail'],
                'menu' => [__DIR__ . '/../../templates/menu'],
            ],
        ];
    }

    protected function getTwigSettings(): array
    {
        return [
            'extensions' => [
                Trans::class,
                Vite::class,
                Csrf::class,
                StringLoaderExtension::class,
            ]
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
                    'codeValidSeconds' => 30,
                    'maxVerifyOtpAttempts' => 5,
                ],
            ],
            'requireAppTotp' => true, // TODO: Only `true` has been implemented
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
     * Returns the supplementary privileges defined by this module
     *
     * @return mixed[]
     */
    protected function getSupplementaryPrivileges(): array
    {
        return [
            'pr.organization-switch' => new UntranslatedString('Grant access to all organization.'),
            'pr.plan.mail-as-application' => new UntranslatedString('Grant right to impersonate the site when mailing.'),
            'pr.respondent.multiorg' => new UntranslatedString('Display multiple organizations in respondent overview.'),
            'pr.episodes.rawdata' => new UntranslatedString('Display raw data in Episodes of Care.'),
            'pr.respondent.result' => new UntranslatedString('Display results in token overviews.'),
            'pr.respondent.select-on-track' => new UntranslatedString('Grant checkboxes to select respondents on track status in respondent overview.'),
            'pr.respondent.show-deleted' => new UntranslatedString('Grant checkbox to view deleted respondents in respondent overview.'),
            'pr.respondent.who' => new UntranslatedString('Display staff member name in token overviews.'),
            'pr.staff.edit.all' => new UntranslatedString('Grant right to edit staff members from all organizations.'),
            'pr.export.add-resp-nr' => new UntranslatedString('Grant right to export respondent numbers with survey answers.'),
            'pr.export.gender-age' => new UntranslatedString('Grant right to export gender and age information with survey answers.'),
            'pr.staff.see.all' => new UntranslatedString('Display all organizations in staff overview.'),
            'pr.group.switch' => new UntranslatedString('Grant right to switch groups.'),
            'pr.token.mail.freetext' => new UntranslatedString('Grant right to send free text (i.e. non-template) email messages.'),
            'pr.systemuser.seepwd' => new UntranslatedString('Grant right to see password of system users (without editing right).'),
            'pr.embed.login' => new UntranslatedString('Grant right for access to embedded login page.'),
            'pr.survey-maintenance.answer-groups' => new UntranslatedString('Grant right to set answer access to surveys.')
        ];
    }
}
