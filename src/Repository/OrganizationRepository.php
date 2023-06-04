<?php

namespace Gems\Repository;

use Gems\Cache\HelperAdapter;
use Gems\Db\CachedResultFetcher;
use Gems\Util\UtilDbHelper;
use Laminas\Db\Sql\Predicate\Predicate;

class OrganizationRepository
{
    /**
     * The org ID for no organization
     */
    const SYSTEM_NO_ORG  = -1;

    /**
     * @return string[] default array for when no organizations have been created
     */
    public static function getNotOrganizationArray()
    {
        return [static::SYSTEM_NO_ORG => 'create db first'];
    }

    public function __construct(protected CachedResultFetcher $cachedResultFetcher, protected UtilDbHelper $utilDbHelper)
    {}

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
    public function getAllowedOrganizationsFor(int $orgId): ?array
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

        $result = $this->cachedResultFetcher->fetchPairs($key, $select);
        return $result;
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

    public function getOrganizationsPerSite()
    {
        $select = $this->cachedResultFetcher->getSelect();
        $select->from('gems__organizations')
            ->columns(['gor_id_organization', 'gor_sites'])
            ->where(['gor_active' => 1]);

        $key = static::class . 'organizationSites';

        $result = $this->cachedResultFetcher->fetchPairs($key, $select);

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
}