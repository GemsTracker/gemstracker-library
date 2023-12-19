<?php

namespace Gems\Agenda\Repository;

use Gems\Agenda\Filter\AppointmentFilterInterface;
use Gems\Cache\HelperAdapter;
use Gems\Db\CachedResultFetcher;
use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\ProjectOverloader;

class FilterRepository
{
    protected array $cacheTags = [
        'appointment_filters',
    ];
    public function __construct(
        protected readonly HelperAdapter $cache,
        protected readonly CachedResultFetcher $cachedResultFetcher,
        protected readonly ProjectOverloader $projectOverloader,
    )
    {
    }

    public function getFilterFromData(array $data): AppointmentFilterInterface|null
    {
        $cacheKey = 'appointmentFilter.' . $data['gaf_id'];
        if ($this->cache->hasItem($cacheKey)) {
            return $this->cache->getCacheItem($cacheKey);
        }

        if ($data['gaf_class']) {
            try {
                $filter = $this->projectOverloader->create('Agenda\\Filter\\' . $data['gaf_class'],
                    $data['gaf_id'],
                    $data['gaf_calc_name'],
                    $data['gaf_id_order'],
                    (bool)$data['gaf_active'],
                    $data['gaf_manual_name'],
                    $data['gaf_filter_text1'],
                    $data['gaf_filter_text2'],
                    $data['gaf_filter_text3'],
                    $data['gaf_filter_text4']
                );

                $this->cache->setCacheItem($cacheKey, $filter);

                return $filter;
            } catch(LoadException) {
            }
        }

        return null;
    }

    public function getFilter(int $filterId): AppointmentFilterInterface|null
    {
        $filters = $this->getAllFilterData();
        foreach($filters as $filter) {
            if ($filter['gaf_id'] === $filterId) {
                return $this->getFilterFromData($filter);
            }
        }
        return null;
    }

    public function getAllActiveFilterData(int|null $organizationId = null): array
    {
        $allFilters = $this->getAllFilterData();

        return array_filter($allFilters, function($filter) use ($organizationId) {
            if ($organizationId) {
                return ($filter['gaf_active']) && $filter['gaf_id_organization'] == $organizationId;
            }
            return (bool)$filter['gaf_active'];
        });
    }

    public function getAllActiveFilters(int|null $organizationId = null): array
    {
        $allActiveFilterData = $this->getAllActiveFilterData($organizationId);

        $filters = [];
        foreach($allActiveFilterData as $filter) {
            $filters[] = $this->getFilter($filter);
        }

        return $filters;
    }

    public function getAllFilterData(): array
    {
        $sql = 'SELECT * FROM gems__appointment_filters ORDER BY gaf_id_order';

        return $this->cachedResultFetcher->fetchAll('allAppointmentFilters', $sql, null, $this->cacheTags);
    }

    public function getAllFilterOptions(int|null $organizationId = null): array
    {
        $allActiveFilterData = $this->getAllActiveFilterData($organizationId);

        $filterOptions = [];
        foreach($allActiveFilterData as $filterData) {
            $filterOptions[$filterData['gaf_id']] = $filterData['gaf_manual_name'] ?? $filterData['gaf_calc_name'];
        }

        return $filterOptions;
    }
}
