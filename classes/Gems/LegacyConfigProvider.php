<?php

declare(strict_types=1);

namespace Gems;

use Gems\Audit\AccesslogRepository;
use Gems\Agenda\Agenda;
use Gems\Batch\BatchRunnerLoader;
use Gems\Communication\CommunicationRepository;
use Gems\Condition\ConditionLoader;
use Gems\Db\ResultFetcher;
use Gems\Encryption\ValueEncryptor;
use Gems\Layout\LayoutRenderer;
use Gems\Legacy\LegacyFactory;
use Gems\Legacy\LegacyZendDatabaseFactory;
use Gems\Locale\Locale;
use Gems\Menu\RouteHelper;
use Gems\Repository\AccessRepository;
use Gems\Repository\CommJobRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\SourceRepository;
use Gems\Repository\StaffRepository;
use Gems\Repository\SurveyRepository;
use Gems\Repository\TokenRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Screens\ScreenLoader;
use Gems\Site\SiteUtil;
use Gems\Tracker\TrackEvents;
use Gems\User\Embed\EmbedLoader;
use Gems\User\Mask\MaskRepository;
use Gems\User\PasswordChecker;
use Gems\User\UserLoader;
use Gems\Util\ConsentUtil;
use Gems\Util\Localized;
use Laminas\Db\Adapter\Adapter;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;
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
                'LegacyCommJobRepository' => CommJobRepository::class,
                'LegacyConditionLoader' => ConditionLoader::class,
                'LegacyConfig' => 'config',
                'LegacyConsentUtil' => ConsentUtil::class,
                'LegacyEmbedLoader' => EmbedLoader::class,
                'LegacyEvent' => EventDispatcher::class,
                'LegacyLoader' => \Gems\Loader::class,
                'LegacyLocale' => Locale::class,
                'LegacyLocalized' => Localized::class,
                'LegacyMaskRepository' => MaskRepository::class,
                'LegacyModelLoader' => Model::class,
                'LegacyOverLoader' => ProjectOverloader::class,
                'LegacyOrganizationRepository' => OrganizationRepository::class,
                'LegacyPasswordChecker' => PasswordChecker::class,
                'LegacyRespondentRepository' => RespondentRepository::class,
                'LegacyRouteHelper' => RouteHelper::class,
                'LegacyResultFetcher' => ResultFetcher::class,
                'LegacyPdf' => Pdf::class,
                'LegacyProject' => \Gems\Project\ProjectSettings::class,
                'LegacyScreenLoader' => ScreenLoader::class,
                'LegacySiteUtil' => SiteUtil::class,
                'LegacySourceRepository' => SourceRepository::class,
                'LegacyStaffRepository' => StaffRepository::class,
                'LegacySurveyRepository' => SurveyRepository::class,
                'LegacyTokenRepository' => TokenRepository::class,
                'LegacyTrackDataRepository' => TrackDataRepository::class,
                'LegacyTrackEvents' => TrackEvents::class,
                'LegacyTracker' => \Gems\Tracker::class,
                'LegacyTranslate' => TranslatorInterface::class,
                'LegacyTranslatedUtil' => \Gems\Util\Translated::class,
                'LegacyUrlHelper' => UrlHelper::class,
                'LegacyUserLoader' => UserLoader::class,
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
