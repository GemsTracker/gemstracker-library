<?php

namespace Gems\Agenda\Repository;

use Carbon\CarbonImmutable;
use Gems\Agenda\AppointmentFilterInterface;
use Gems\Db\CachedResultFetcher;
use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\ProjectOverloader;

class FilterRepository
{
    protected array $cacheTags = [
        'appointment_filters',
        'tracks',
    ];
    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        protected readonly ProjectOverloader $projectOverloader,
    )
    {}


    public function getFilterFromData(array $data): AppointmentFilterInterface|null
    {
        if ($data['gaf_class']) {
            try {
                $filter = $this->projectOverloader->create('Agenda\\Filter\\' . $data['gaf_class'], $data);
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

    public function getAllActivelyUsedFilters(): array
    {
        $sql = 'SELECT * FROM gems__appointment_filters
                    INNER JOIN gems__track_appointments ON gaf_id = gtap_filter_id
                    INNER JOIN gems__tracks ON gtap_id_track = gtr_id_track
                    WHERE gaf_active = 1 
                      AND gtr_active = 1 
                      AND gtr_date_start <= CURRENT_DATE 
                      AND (gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE)
                    ORDER BY gaf_id_order, gtap_id_order, gtap_id_track';

        $filterData = $this->cachedResultFetcher->fetchAll('allActivelyUsedAppointmentFilters', $sql, null, $this->cacheTags);

        $filters = [];
        foreach($filterData as $filter) {
            $filters[] = $this->getFilter($filter);
        }

        return $filters;
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
        $sql = 'SELECT * FROM gems__appointment_filters 
                    LEFT JOIN gems__track_appointments ON gaf_id = gtap_filter_id
                    ORDER BY gaf_id_order';

        return $this->cachedResultFetcher->fetchAll('allAppointmentFilters', $sql, null, $this->cacheTags);
    }
}