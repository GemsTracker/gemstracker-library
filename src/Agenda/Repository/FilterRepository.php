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
        'activity',
        'activities',
    ];

    protected array $subFilterClasses = [
        'AndAppointmentFilter',
        'OrAppointmentFilter',
        'XandAppointmentFilter',
        'XorAppointmentFilter',
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

                $this->cache->setCacheItem($cacheKey, $filter, $this->cacheTags);

                return $filter;
            } catch(LoadException) {
            }
        }

        return null;
    }

    public function getFilter(int $filterId): AppointmentFilterInterface|null
    {
        $filterData = $this->getFilterData($filterId);
        if ($filterData) {
            return $this->getFilterFromData($filterData);
        }
        return null;
    }

    public function getFilterData(int $filterId): array|null
    {
        $filters = array_column($this->getAllFilterData(), null, 'gaf_id');
        if (isset($filters[$filterId])) {
            return $filters[$filterId];
        }
        return null;
    }

    public function getAllActiveFilterData(): array
    {
        $allFilters = $this->getAllFilterData();

        return array_filter($allFilters, function($filter) {
            return (bool)$filter['gaf_active'];
        });
    }

    public function getAllActiveFilters(): array
    {
        $allActiveFilterData = $this->getAllActiveFilterData();

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

    public function getAllFilterOptions(): array
    {
        $allActiveFilterData = $this->getAllActiveFilterData();

        $filterOptions = [];
        foreach($allActiveFilterData as $filterData) {
            $filterOptions[$filterData['gaf_id']] = $filterData['gaf_manual_name'] ?? $filterData['gaf_calc_name'];
        }

        return $filterOptions;
    }

    public function hasFilterAsSub(int $filterId, int $subFilterId): bool
    {
        $allFilterData = array_column($this->getAllFilterData(), null, 'gaf_id');
        if (isset($allFilterData[$filterId])) {
            $targetFilterData = $allFilterData[$filterId];
            if (in_array($targetFilterData['gaf_class'], $this->subFilterClasses)) {
                for ($i = 1; $i <= 4; $i++) {
                    $targetSubFilterId = $targetFilterData['gaf_filter_text'.$i] ?? null;
                    if ($targetSubFilterId === $subFilterId) {
                        return true;
                    }
                    return $this->hasFilterAsSub($targetSubFilterId, $subFilterId);
                }
            }
        }
        return false;
    }
}
