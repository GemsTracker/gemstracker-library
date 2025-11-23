<?php

namespace Gems\Repository;

use Gems\Config\ConfigAccessor;
use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\User\Organization;
use Gems\User\UserLoader;
use Gems\Util\UtilDbHelper;
use Laminas\Db\Sql\Predicate\Predicate;
use Zalt\Loader\ProjectOverloader;

class OrganizationRepository
{
    protected array $cacheTags = ['organization', 'organizations'];

    /**
     * The org ID for no organization
     */
    const SYSTEM_NO_ORG  = -1;

    protected Organization|null $noOrgOrganization = null;

    protected array $organizations = [];

    /**
     * @return string[] default array for when no organizations have been created
     */
    public static function getNotOrganizationArray()
    {
        return [static::SYSTEM_NO_ORG => 'create db first'];
    }

    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        protected readonly ConfigAccessor $configAccessor,
        protected readonly ProjectOverloader $projectOverloader,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly UtilDbHelper $utilDbHelper,
        protected readonly UserLoader $userLoader,
    )
    {}

    /**
     * An array orgId => orgId of the organizations the respondent / orgId combo is allowed to see
     * the tracks and respondents of.
     *
     * Allowes projects to add extra tracks to a respodnent screen
     *
     * @param int $respondentId
     * @param int $organizationId
     * @return array<int, int>
     */
    public function getExtraTokenOrgsFor(int $respondentId, int $organizationId): array
    {
        return [$organizationId => $organizationId];
    }

    public function getNoOrganizationOrganization(): Organization
    {
        if (!$this->noOrgOrganization) {
            $this->noOrgOrganization = $this->projectOverloader->create(
                'User\\Organization',
                static::SYSTEM_NO_ORG,
                $this->getSiteUrls(),
                $this->userLoader->getAvailableStaffDefinitions(),
                $this->configAccessor->getAfterTrackChangeDefaultRoute(),
            );
        }
        return $this->noOrgOrganization;
    }

    public function getOrganization(int $organizationId): Organization
    {
        if ($organizationId === static::SYSTEM_NO_ORG) {
            return $this->getNoOrganizationOrganization();
        }
        if (!isset($this->organizations[$organizationId])) {
            $this->organizations[$organizationId] = $this->projectOverloader->create(
                'User\\Organization',
                $organizationId,
                $this->getSiteUrls(),
                $this->userLoader->getAvailableStaffDefinitions(),
                $this->configAccessor->getAfterTrackChangeDefaultRoute(),
            );
        }
        return $this->organizations[$organizationId];
    }

    public function getOrganizationForStaffId(int $staffId): ?Organization
    {
        $orgId = $this->getOrganizationIdForStaffId($staffId);
        if ($orgId) {
            return $this->getOrganization($orgId);
        }
        return null;
    }

    public function getOrganizationIdForStaffId(int $staffId): false|int
    {
        return $this->resultFetcher->fetchOne("SELECT gsf_id_organization FROM gems__staff WHERE gsf_id_user = ?", [$staffId]);
    }

    /**
     * Get all active organizations
     *
     * @return array List of the active organizations
     */
    public function getOrganizations(): array
    {
        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            ['organizations'],
            ['gor_active' => 1],
            'natsort'
        );
    }

    /**
     * Get all organizations that share a given code
     *
     * On empty this will return all organizations
     *
     * @param string $code
     * @return array key = gor_id_organization, value = gor_name
     */
    public function getOrganizationsByCode(string $code = null): array
    {
        if (is_null($code)) {
            return $this->getOrganizations();
        }

        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            ['organizations'],
            [
                'gor_active' => 1,
                'gor_has_respondents' => 1,
                'gor_code' => $code,
            ],
            'natsort'
        );
    }

    /**
     * Return all organizations this organization is allowed access to
     *
     * @param int $orgId
     * @return array
     */
    public function getAllowedOrganizationsFor(int $orgId): array
    {
        $key = static::class . 'allowedOrganizationsFor_' . $orgId;

        $select = $this->cachedResultFetcher->getSelect();
        $select->from('gems__organizations')
            ->columns(['gor_id_organization', 'gor_name'])
            ->where
                ->nest()
                    ->equalTo('gor_id_organization', $orgId)
                    ->or
                    ->like('gor_accessible_by', "%:$orgId:%")
                ->unnest()
                ->equalTo('gor_active', 1);
        $select->order('gor_name');

        return $this->cachedResultFetcher->fetchPairs($key, $select, null, $this->cacheTags);
    }

    public function getAllowedUrl(string $url): ? string
    {
        foreach($this->getSiteUrls() as $siteUrl) {
            if (str_starts_with($url, $siteUrl)) {
                return $siteUrl;
            }
        }
        return null;
    }

    /**
     * Returns a list of the organizations where users can login.
     *
     * @return array List of the active organizations
     */
    public function getOrganizationsForLogin(): array
    {
        $result = $this->utilDbHelper->getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            ['organizations'],
            [
                'gor_active' => 1,
                'gor_has_login' => 1,
            ],
            'natsort'
        );
        if ($result) {
            return $result;
        }
        return static::getNotOrganizationArray();
    }

    public function getOrganizationsImportTranslations()
    {
        $result = $this->utilDbHelper->getSelectPairsCached(
        __FUNCTION__ . '_1',
            "SELECT gor_provider_id, gor_id_organization
                FROM gems__organizations
                WHERE gor_provider_id IS NOT NULL",
            null,
            ['organizations'],
        );
        $result += $this->utilDbHelper->getSelectPairsCached(
            __FUNCTION__ . '_2',
            "SELECT gor_code, gor_id_organization
                FROM gems__organizations
                WHERE gor_code IS NOT NULL",
            null,
            ['organizations'],
        );
        if ($result) {
            return $result;
        }
        return static::getNotOrganizationArray();
    }

    public function getOrganizationsPerSite(): array
    {
        $select = $this->cachedResultFetcher->getSelect();
        $select->from('gems__organizations')
            ->columns(['gor_id_organization', 'gor_sites'])
            ->where(['gor_active' => 1]);

        $key = static::class . 'organizationSites';

        $result = $this->cachedResultFetcher->fetchPairs($key, $select, null, $this->cacheTags);

        $organizationsPerSite = [];
        foreach($result as $organizationId => $sites) {
            if ($sites === null) {
                continue;
            }
            if (is_string($sites)) {
                $sites = explode('|', $sites);
            }
            foreach($sites as $site) {
                $organizationsPerSite[$site][] = $organizationId;
            }
        }

        return $organizationsPerSite;
    }

    /**
     * Returns a list of the organizations that (can) have respondents.
     *
     * @return array List of the active organizations
     */
    public function getOrganizationsWithRespondents()
    {
        $canAddRespondentsPredicate = new Predicate();
        $canAddRespondentsPredicate->equalTo('gor_has_respondents', 1)->or->equalTo('gor_add_respondents', 1);
        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            ['organizations'],
            [
                'gor_active' => 1,
                $canAddRespondentsPredicate,
            ],
            'natsort'
        );
    }

    /**
     * Returns a list of the organizations to which respondents can be added.
     *
     * @return array The list organizations
     */
    public function getOrganizationsOpenToRespondents()
    {
        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            ['organizations'],
            [
                'gor_active' => 1,
                'gor_add_respondents' => 1,
            ],
            'natsort'
        );
    }

    public function getSiteUrls(): array
    {
        return $this->configAccessor->getAllowedSites();
    }

    public function isAllowedUrl(string $url): bool
    {
        foreach($this->getSiteUrls() as $siteUrl) {
            if (str_starts_with($url, $siteUrl)) {
                return true;
            }
        }
        return false;
    }
}
