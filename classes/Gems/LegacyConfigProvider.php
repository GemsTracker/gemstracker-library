<?php

declare(strict_types=1);

namespace Gems;

use Gems\Encryption\ValueEncryptor;
use Gems\Legacy\LegacyFactory;
use Gems\Legacy\LegacyZendDatabaseFactory;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterServiceFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

class LegacyConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
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
                \Gems_Loader::class => LegacyFactory::class,
                \Gems_Menu::class => LegacyFactory::class,
                \Gems_Project_ProjectSettings::class => LegacyFactory::class,
                \Gems_Util::class => LegacyFactory::class,
                \Zend_Locale::class => LegacyFactory::class,
                \Zend_Session_Namespace::class => LegacyFactory::class,
                \Zend_Translate::class => LegacyFactory::class,
                \Zend_View::class => LegacyFactory::class,
                Adapter::class => AdapterServiceFactory::class,
                \Zend_Db_Adapter_Abstract::class => LegacyZendDatabaseFactory::class,
                \Zend_Acl::class => LegacyFactory::class,
                \Gems_Util_BasePath::class => LegacyFactory::class,

                'LegacyCurrentUser' => LegacyFactory::class,
            ],
            'aliases' => [
                'LegacyAcl' => \Zend_Acl::class,
                'LegacyBasepath' => \Gems_Util_BasePath::class,
                'LegacyCache' => CacheItemPoolInterface::class,
                'LegacyEvent' => EventDispatcher::class,
                'LegacyLoader' => \Gems_Loader::class,
                'LegacyLocale' => \Zend_Locale::class,
                'LegacyMenu' => \Gems_Menu::class,
                'LegacyOverLoader' => ProjectOverloader::class,
                'LegacyProject' => \Gems_Project_ProjectSettings::class,
                'LegacySession' => \Zend_Session_Namespace::class,
                'LegacyUtil' => \Gems_Util::class,
                'LegacyTracker' => \Gems_Tracker::class,
                'LegacyTranslate' => TranslatorInterface::class,
                'LegacyView' => \Zend_View::class,
                'db' => Adapter::class,
                'LegacyDb' => \Zend_Db_Adapter_Abstract::class,
                'LegacyDb2' => Adapter::class,
                'LegacyValueEncryptor' => ValueEncryptor::class,
            ],
        ];
    }
}
