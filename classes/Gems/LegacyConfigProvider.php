<?php

declare(strict_types=1);

namespace Gems;

use Gems\AccessLog\AccesslogRepository;
use Gems\Encryption\ValueEncryptor;
use Gems\Legacy\LegacyFactory;
use Gems\Legacy\LegacyZendDatabaseFactory;
use Gems\Locale\Locale;
use Gems\MenuNew\RouteHelper;
use Gems\Util\Localized;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterServiceFactory;
use Laminas\Permissions\Acl\Acl;
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
        \MUtil\Model::addNameSpace('Gems');
        
        return [
            'factories'  => [
                \Gems\Loader::class => LegacyFactory::class,
                \Gems\Menu::class => LegacyFactory::class,
                \Gems\Tracker::class => LegacyFactory::class,
                \Gems\Util::class => LegacyFactory::class,
                \Zend_Locale::class => LegacyFactory::class,
                \Zend_Session_Namespace::class => LegacyFactory::class,
                \Zend_Translate::class => LegacyFactory::class,
                \Zend_View::class => LegacyFactory::class,
                Adapter::class => AdapterServiceFactory::class,
                \Zend_Db_Adapter_Abstract::class => LegacyZendDatabaseFactory::class,
                \Zend_Acl::class => LegacyFactory::class,
                \Gems\Util\BasePath::class => LegacyFactory::class,

                'LegacyCurrentUser' => LegacyFactory::class,
            ],
            'aliases' => [
                'LegacyAccesslog' => AccesslogRepository::class,
                'LegacyAcl' => Acl::class,
                'LegacyBasepath' => \Gems\Util\BasePath::class,
                'LegacyCache' => CacheItemPoolInterface::class,
                'LegacyEvent' => EventDispatcher::class,
                'LegacyLoader' => \Gems\Loader::class,
                'LegacyLocale' => Locale::class,
                'LegacyLocalized' => Localized::class,
                'LegacyOverLoader' => ProjectOverloader::class,
                'LegacyRouteHelper' => RouteHelper::class,
                'LegacyProject' => \Gems\Project\ProjectSettings::class,
                //'LegacySession' => \Zend_Session_Namespace::class,
                'LegacyUtil' => \Gems\Util::class,
                'LegacyTracker' => \Gems\Tracker::class,
                'LegacyTranslate' => TranslatorInterface::class,
                'LegacyTranslatedUtil' => \Gems\Util\Translated::class,
                'LegacyView' => \Zend_View::class,
                'db' => Adapter::class,
                'LegacyDb' => \Zend_Db_Adapter_Abstract::class,
                'LegacyDb2' => Adapter::class,
                'LegacyValueEncryptor' => ValueEncryptor::class,
            ],
        ];
    }
}
