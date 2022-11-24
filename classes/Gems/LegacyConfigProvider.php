<?php

declare(strict_types=1);

namespace Gems;

use Gems\AccessLog\AccesslogRepository;
use Gems\Batch\BatchRunnerLoader;
use Gems\Communication\CommunicationRepository;
use Gems\Condition\ConditionLoader;
use Gems\Db\ResultFetcher;
use Gems\Encryption\ValueEncryptor;
use Gems\Layout\LayoutRenderer;
use Gems\Legacy\LegacyFactory;
use Gems\Legacy\LegacyZendDatabaseFactory;
use Gems\Locale\Locale;
use Gems\MenuNew\RouteHelper;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\SourceRepository;
use Gems\Repository\SurveyRepository;
use Gems\Repository\TokenRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Tracker\TrackEvents;
use Gems\Util\ConsentUtil;
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
                \Gems\User\UserLoader::class => LegacyFactory::class,
                \Gems\Menu::class => LegacyFactory::class,
                \Gems\Tracker::class => LegacyFactory::class,
                \Gems\Util::class => LegacyFactory::class,
                \Zend_Locale::class => LegacyFactory::class,
                \Zend_Translate::class => LegacyFactory::class,
                \Zend_View::class => LegacyFactory::class,
                \Zend_Db_Adapter_Abstract::class => LegacyZendDatabaseFactory::class,
                \Zend_Acl::class => LegacyFactory::class,
                \Gems\Util\BasePath::class => LegacyFactory::class,
                Agenda::class => LegacyFactory::class,

                'LegacyCurrentUser' => LegacyFactory::class,
            ],
            'aliases' => [
                'LegacyAccesslog' => AccesslogRepository::class,
                'LegacyAccessRepository' => AccessRepository::class,
                'LegacyAcl' => Acl::class,
                'LegacyAgenda' => Agenda::class,
                'LegacyBasepath' => \Gems\Util\BasePath::class,
                'LegacyBatchRunnerLoader' => BatchRunnerLoader::class,
                'LegacyCache' => CacheItemPoolInterface::class,
                'LegacyCommunicationRepository' => CommunicationRepository::class,
                'LegacyConditionLoader' => ConditionLoader::class,
                'LegacyConfig' => 'config',
                'LegacyConsentUtil' => ConsentUtil::class,
                'LegacyEvent' => EventDispatcher::class,
                'LegacyLoader' => \Gems\Loader::class,
                'LegacyLocale' => Locale::class,
                'LegacyLocalized' => Localized::class,
                'LegacyModelLoader' => Model::class,
                'LegacyOverLoader' => ProjectOverloader::class,
                'LegacyOrganizationRepository' => OrganizationRepository::class,
                'LegacyRouteHelper' => RouteHelper::class,
                'LegacyResultFetcher' => ResultFetcher::class,
                'LegacyPdf' => Pdf::class,
                'LegacyProject' => \Gems\Project\ProjectSettings::class,
                'LegacySourceRepository' => SourceRepository::class,
                'LegacySurveyRepository' => SurveyRepository::class,
                'LegacyTokenRepository' => TokenRepository::class,
                'LegacyTrackDataRepository' => TrackDataRepository::class,
                'LegacyTrackEvents' => TrackEvents::class,
                'LegacyTracker' => \Gems\Tracker::class,
                'LegacyTranslate' => TranslatorInterface::class,
                'LegacyTranslatedUtil' => \Gems\Util\Translated::class,
                'LegacyUtil' => \Gems\Util::class,
                'LegacyView' => \Zend_View::class,
                'db' => Adapter::class,
                'LegacyDb' => \Zend_Db_Adapter_Abstract::class,
                'LegacyDb2' => Adapter::class,
                'LegacyLayoutRenderer' => LayoutRenderer::class,
                'LegacyValueEncryptor' => ValueEncryptor::class,
                'LegacyVersions' => Versions::class,
            ],
        ];
    }
}
