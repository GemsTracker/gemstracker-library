<?php

namespace Gems\Repository;

use Gems\Cache\HelperAdapter;
use Gems\Db\ResultFetcher;

class OrganizationRepository
{
    public function __construct(protected ResultFetcher $resultFetcher, protected HelperAdapter $cache)
    {}

    /**
     * Return all organizations this arganization is allowed acces to
     *
     * @param int $orgId
     * @return array
     */
    public function getAllowedOrganizationsFor(int $orgId)
    {
        $key = static::class . 'allowedOrganizationsFor_' . $orgId;
        if ($this->cache->hasItem($key)) {
            return $this->cache->getCacheItem($key);
        }

        $select = $this->resultFetcher->getSelect();
        $select->from('gems__organizations')
            ->columns(['gor_id_organization', 'gor_name'])
            ->where
                ->nest()
                    ->equalTo('gor_id_organization', $orgId)
                    ->or
                    ->like('gor_accessible_by', "%:$orgId:%")
                ->unnest()
                ->equalTo('gor_active', 1);

        $result = $this->resultFetcher->fetchPairs($select);
        $this->cache->setCacheItem($key, $result);
        return $result;
    }

    /**
     * Get all active organizations
     *
     * @return array List of the active organizations
     */
    public function getOrganizations()
    {
        $key = static::class . 'activeOrganizations';
        if ($this->cache->hasItem($key)) {
            return $this->cache->getCacheItem($key);
        }

        $select = $this->resultFetcher->getSelect();
        $select->from('gems__organizations')
            ->columns(['gor_id_organization', 'gor_name'])
            ->where(['gor_active' => 1]);

        $result = $this->resultFetcher->fetchPairs($select);
        $this->cache->setCacheItem($key, $result);
        return $result;
    }

}