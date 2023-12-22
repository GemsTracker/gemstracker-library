<?php

namespace Gems;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Gems\Auth\Acl\AclFactory;
use Gems\Auth\Acl\DbGroupAdapter;
use Gems\Auth\Acl\DbRoleAdapter;
use Gems\Auth\Acl\GroupAdapterInterface;
use Gems\Auth\Acl\RoleAdapterInterface;
use Gems\Cache\CacheFactory;
use Gems\Cache\HelperAdapter;
use Gems\Command\ClearConfigCache;
use Gems\Command\ConsumeMessageCommandFactory;
use Gems\Command\DebugMessageCommandFactory;
use Gems\Command\GenerateApplicationKey;
use Gems\Condition\Comparator\ComparatorAbstract;
use Gems\Condition\RoundConditionInterface;
use Gems\Condition\TrackConditionInterface;
use Gems\Config\App;
use Gems\Config\AutoConfig\MessageHandlers;
use Gems\Config\Messenger;
use Gems\Config\Route;
use Gems\Config\Survey;
use Gems\Db\Migration\PatchAbstract;
use Gems\Csrf\GemsCsrfGuardFactory;
use Gems\Error\ErrorLogEventListenerDelegatorFactory;
use Gems\Factory\DoctrineDbalFactory;
use Gems\Factory\DoctrineOrmFactory;
use Gems\Factory\EventDispatcherFactory;
use Gems\Factory\LaminasDbAdapterFactory;
use Gems\Factory\MailTransportFactory;
use Gems\Factory\MonologFactory;
use Gems\Factory\PdoFactory;
use Gems\Factory\ProjectOverloaderFactory;
use Gems\Factory\ReflectionAbstractFactory;
use Gems\Log\ErrorLogger;
use Gems\Menu\RouteHelper;
use Gems\Menu\RouteHelperFactory;
use Gems\Messenger\MessengerFactory;
use Gems\Messenger\TransportFactory;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Model\Bridge\GemsFormBridge;
use Gems\Model\Bridge\GemsValidatorBridge;
use Gems\Model\MetaModelLoader as GemsMetaModelLoader;
use Gems\Model\MetaModelLoaderFactory;
use Gems\Model\Respondent\RespondentModel;
use Gems\Model\Respondent\RespondentNlModel;
use Gems\Model\Type\GemsDateTimeType;
use Gems\Model\Type\GemsDateType;
use Gems\Model\Type\GemsTimeType;
use Gems\Route\ModelSnippetActionRouteHelpers;
use Gems\Screens\AskScreenInterface;
use Gems\Screens\BrowseScreenInterface;
use Gems\Screens\EditScreenInterface;
use Gems\Screens\ShowScreenInterface;
use Gems\Screens\SubscribeScreenInterface;
use Gems\Screens\UnsubscribeScreenInterface;
use Gems\Session\PhpSessionPersistenceFactory;
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
use Gems\User\Embed\DeferredUserLoaderInterface;
use Gems\User\Embed\EmbeddedAuthInterface;
use Gems\User\Embed\RedirectInterface;
use Gems\Util\Lock\CommJobLock;
use Gems\Util\Lock\LockFactory;
use Gems\Util\Lock\MaintenanceLock;
use Gems\Util\Lock\Storage\FileLock;
use Gems\Util\Lock\Storage\LockStorageAbstract;
use Gems\Util\Monitor\Monitor;
use Gems\Util\Monitor\MonitorFactory;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Session\Cache\CacheSessionPersistence;
use Mezzio\Session\Cache\CacheSessionPersistenceFactory;
use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionMiddlewareFactory;
use Mezzio\Session\SessionPersistenceInterface;
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
use Twig\Extension\ExtensionInterface;
use Twig\Extension\StringLoaderExtension;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelConfigProvider;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Sql\Laminas\LaminasRunner;
use Zalt\Model\Sql\Laminas\LaminasRunnerFactory;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\SubModelType;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetLoaderFactory;
use Zalt\SnippetsLoader\SnippetMiddleware;
use Zalt\SnippetsLoader\SnippetMiddlewareFactory;
use Zalt\SnippetsLoader\SnippetResponderInterface;

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
            'account'       => $this->getAccountSettings(),
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
            'overLoaderPaths' => $this->getOverloaderPaths(),
            'password'      => $this->getPasswordSettings(),
            'supplementary_privileges'   => $this->getSupplementaryPrivileges(),
            'routes'        => $routeSettings(),
            'ratelimit'     => $this->getRatelimitSettings(),
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
            'vue'           => $this->getVueSettings(),
        ];
    }

    public function getAccountSettings(): array
    {
        return [
            'edit-auth' => [
                'enabled' => true,
                'throttle-sms' => [
                    'maxAttempts' => 3,
                    'maxAttemptsPerPeriod' => 86400,
                ],
                'throttle-email' => [
                    'maxAttempts' => 3,
                    'maxAttemptsPerPeriod' => 86400,
                ],
                'defaultRegion' => 'NL',
            ],
        ];
    }

    public function getAutoConfigSettings(): array
    {
        return [
            'settings' => [
                'implements' => [
                    AskScreenInterface::class => ['config' => 'screens.Token/Ask'],
                    BrowseScreenInterface::class => ['config' => 'screens.Respondent/Browse'],
                    DeferredUserLoaderInterface::class => ['config' => 'embed.deferredUserLoader'],
                    EditScreenInterface::class => ['config' => 'screens.Respondent/Edit'],
                    EmbeddedAuthInterface::class => ['config' => 'embed.auth'],
                    EventSubscriberInterface::class => ['config' => 'events.subscribers'],
                    ExtensionInterface::class => ['config' => 'twig.extensions'],
                    RedirectInterface::class => ['config' => 'embed.redirect'],
                    RespondentChangedEventInterface::class => ['config' => 'tracker.trackEvents.Respondent/Change'],
                    RoundChangedEventInterface::class => ['config' => 'tracker.trackEvents.Round/Changed'],
                    RoundConditionInterface::class => ['config' => 'tracker.conditions.round'],
                    ShowScreenInterface::class => ['config' => 'screens.Respondent/Show'],
                    SubscribeScreenInterface::class => ['config' => 'screens.Respondent/Subscribe'],
                    SurveyBeforeAnsweringEventInterface::class => ['config' => 'tracker.trackEvents.Survey/BeforeAnswering'],
                    SurveyCompletedEventInterface::class => ['config' => 'tracker.trackEvents.Survey/Completed'],
                    SurveyDisplayEventInterface::class => ['config' => 'tracker.trackEvents.Survey/Display'],
                    TrackBeforeFieldUpdateEventInterface::class => ['config' => 'tracker.trackEvents.Track/BeforeFieldUpdate'],
                    TrackCalculationEventInterface::class => ['config' => 'tracker.trackEvents.Track/Calculate'],
                    TrackCompletedEventInterface::class => ['config' => 'tracker.trackEvents.Track/Completed'],
                    TrackConditionInterface::class => ['config' => 'tracker.conditions.track'],
                    TrackFieldUpdateEventInterface::class => ['config' => 'tracker.trackEvents.Track/FieldUpdate'],
                    UnsubscribeScreenInterface::class => ['config' => 'screens.Respondent/Unsubscribe'],
                ],
                'extends' => [
                    ComparatorAbstract::class => ['config' => 'tracker.conditions.comparators'],
                    PatchAbstract::class => ['config' => 'migrations.patches'],
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
        if (isset($_ENV['CACHE_ADAPTER'])) {
            $cacheAdapter = $_ENV['CACHE_ADAPTER'];
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
            'host'      => getenv('DB_HOST') ?? null,
            'username'  => getenv('DB_USER') ?? null,
            'password'  => getenv('DB_PASS') ?? null,
            'database'  => getenv('DB_NAME') ?? null,
        ];
    }

    public function getDatabaseSettings(): array
    {
        return [
            'gemsData' => [

            ],
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
                //Agenda::class => AgendaFactory::class,

                // Logs
                'LegacyLogger' => MonologFactory::class,
                'embeddedLoginLog' => MonologFactory::class,
                ErrorLogger::class => MonologFactory::class,

                // Cache
                \Symfony\Component\Cache\Adapter\AdapterInterface::class => CacheFactory::class,
                HelperAdapter::class => CacheFactory::class,

                // Database
                \PDO::class => PdoFactory::class,
                Adapter::class => LaminasDbAdapterFactory::class,
                'databaseAdapterGemsData' => LaminasDbAdapterFactory::class,

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

                \Symfony\Component\Mailer\Transport\TransportInterface::class => MailTransportFactory::class,

                ConsumeMessagesCommand::class => ConsumeMessageCommandFactory::class,
                DebugCommand::class => DebugMessageCommandFactory::class,

                // Locks
                MaintenanceLock::class => [LockFactory::class, FileLock::class],
                CommJobLock::class => [LockFactory::class, FileLock::class],
                Monitor::class => MonitorFactory::class,

                // Route / Menu
                RouteHelper::class => RouteHelperFactory::class,

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

                // Databases
                AdapterInterface::class => Adapter::class,
                'databaseAdapterGems' => Adapter::class,

                // Messenger
                'messenger.bus.default' => MessageBusInterface::class,

                // Session
                //SessionPersistenceInterface::class => CacheSessionPersistence::class,
                SessionPersistenceInterface::class => PhpSessionPersistence::class,
                CsrfGuardFactoryInterface::class => GemsCsrfGuardFactory::class,

                RoleAdapterInterface::class => DbRoleAdapter::class,
                GroupAdapterInterface::class => DbGroupAdapter::class,

                MetaModelLoader::class => GemsMetaModelLoader::class,

                // Default lock storage
                LockStorageAbstract::class => FileLock::class,

                RespondentModel::class => RespondentNlModel::class,

                // Translation
                \Symfony\Contracts\Translation\TranslatorInterface::class => TranslatorInterface::class,

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

            // Default Template code for a change email confirmation mail
            'confirmChangeEmailTemplate' => 'confirmChangeEmail',

            // Default Template code for a change phone confirmation sms
            'confirmChangePhoneTemplate' => 'confirmChangePhone',

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
        return [
            'availableLocales' => [
                // Set in project
            ],
            'default' => 'en',
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
            'tables' => [
                __DIR__ . '/../configs/db/tables',
            ],
            'seeds' => [
                __DIR__ . '/../configs/db/seeds',
            ],
            'patches' => [
                __DIR__ . '/../configs/db/patches',
            ],

        ];
    }

    protected function getModelSettings(): array
    {
        $settings = MetaModelConfigProvider::getConfig();
        $settings['bridges']['form'] = GemsFormBridge::class;
        $settings['bridges']['validator'] = GemsValidatorBridge::class;
        $settings['modelTypes'] = [
            MetaModelInterface::TYPE_CHILD_MODEL => SubModelType::class,
            MetaModelInterface::TYPE_DATE => GemsDateType::class,
            MetaModelInterface::TYPE_DATETIME => GemsDateTimeType::class,
            MetaModelInterface::TYPE_TIME => GemsTimeType::class,
        ];
        $settings['translateDatabaseFields'] = true;

        foreach ($settings['bridges'] as $name => $className) {
            \MUtil\Model::setDefaultBridge($name, $className);
        }

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

    protected function getOverloaderPaths()
    {
        // Highest priority last.
        return ['MUtil', 'Gems'];
    }

    protected function getPasswordSettings(): array
    {
        /**
         *
         */
        return [
            'default' => [
                'notTheName' => 1,
                'inPasswordList' => 'docs/weak-passwords.lst',
                'historyLength' => 5,
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

    /**
     * The rate limit settings.
     * This is a one-dimensional array. The keys are route names, with or
     * without the uppercase request method appended. The value can be false
     * or a string like '10/60', indicating the rate limit allows 10 requests
     * per 60 seconds for this route.
     * A match is applied to the route and all its children, so configuring
     * 'contact.GET' => '5/60' will apply a rate limit of 5 requests per minute
     * for GET requests to 'contact.index', 'contact.about', etc.
     * Rate limits are applied per user or, if the request is not authenticated,
     * per IP address.
     *
     * Default rate limits can be configured with the 'default.GET',
     * 'default.POST' or 'default' keys.
     *
     * Note that a cache must be configured for rate limiting to work!
     *
     * @return array<string>
     */
    protected function getRatelimitSettings(): array
    {
        return [
            'default.GET' => false, // No rate limiting
            'default.POST' => '60/60',
            'participate.subscribe.POST' => '10/60',
            'participate.unsubscribe.POST' => '10/60',
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
                'gems' => [__DIR__ . '/../templates/gems'],
                'layout' => [__DIR__ . '/../templates/layout'],
                'mail' => [__DIR__ . '/../templates/mail'],
                'menu' => [__DIR__ . '/../templates/menu'],
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
                'AuthenticatorTotp' => [
                    'codeLength' => 6,
                    'codeValidSeconds' => 30,
                    'maxVerifyOtpAttempts' => 5,
                ],
            ],
            'requireAuthenticatorTotp' => true, // TODO: Only `true` has been implemented
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
                'gems' => [__DIR__ . '/../languages'],
                'gems-male' => [__DIR__ . '/../languages/gender/male'],
                'gems-female' => [__DIR__ . '/../languages/gender/female'],
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
            'pr.survey-maintenance.answer-groups' => new UntranslatedString('Grant right to set answer access to surveys.'),
            'pr.maintenance.maintenance-mode' => new UntranslatedString('Enable, disable and stay online during maintenance mode'),
        ];
    }

    public function getVueSettings(): array
    {
        return [
            'template' => 'gems::vue',
            'resource' => 'resource/js/gems-vue.js',
            'style' => 'resource/js/gems-vue.css',
        ];
    }
}
