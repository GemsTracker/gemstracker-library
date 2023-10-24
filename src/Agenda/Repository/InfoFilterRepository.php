<?php

namespace Gems\Agenda\Repository;

use Gems\Agenda\Appointment;
use Gems\Agenda\EpisodeOfCare;
use Gems\Agenda\Filter\LinkFilterContainer;
use Gems\Db\CachedResultFetcher;
use Laminas\Db\TableGateway\TableGateway;

class InfoFilterRepository
{
    protected array $cacheTags = [
        'appointment_filters',
        'appointment-info-filters',
    ];

    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        protected readonly FilterRepository $filterRepository,
    )
    {
    }

    public function getAllActivelyUsedFilters(): array
    {
        $sql = 'SELECT * FROM gems__appointment_filters
                    INNER JOIN gems__appointment_info ON gaf_id = gai_id_filter
                    WHERE gaf_active = 1 
                      AND gai_active = 1 
                    ORDER BY gaf_id_order';

        $filterDataList = $this->cachedResultFetcher->fetchAll('allAppointmentInfoFilters', $sql, null, $this->cacheTags);

        $filters = [];
        if ($filterDataList) {
            foreach ($filterDataList as $filterData) {
                $appointmentFilter = $this->filterRepository->getFilterFromData($filterData);
                $filters[] = new LinkFilterContainer(
                    $appointmentFilter,
                    $filterData['gai_id'],
                    $filterData['gai_field_key'],
                    $filterData['gai_field_value'],
                );
            }
        }

        return $filters;
    }

    /**
     *
     * @param Appointment|EpisodeOfCare $to
     * @return LinkFilterContainer[]
     */
    public function matchFilters(Appointment|EpisodeOfCare $to): array
    {
        $filters = $this->getAllActivelyUsedFilters();
        $output  = [];

        if ($to instanceof Appointment) {
            foreach ($filters as $filter) {
                if ($filter instanceof LinkFilterContainer) {
                    if ($filter->getAppointmentFilter()->matchAppointment($to)) {
                        $output[] = $filter;
                    }
                }
            }
        } elseif ($to instanceof EpisodeOfCare) {
            foreach ($filters as $filter) {
                if ($filter instanceof LinkFilterContainer) {
                    if ($filter->getAppointmentFilter()->matchEpisode($to)) {
                        $output[] = $filter;
                    }
                }
            }
        }

        return $output;
    }

    public function addInfoToAppointment(Appointment $appointment, LinkFilterContainer $linkFilterContainer): void
    {
        if ($linkFilterContainer->getKeyField()) {
            $info = $appointment->getInfo();
            $info[$linkFilterContainer->getKeyField()] = $linkFilterContainer->getValue();
            $table = new TableGateway('gems__appointments', $this->cachedResultFetcher->getAdapter());
            $table->update([
                'gap_info' => json_encode($info)
            ], 'gap_id_appointment = ' . $appointment->getId());
        }
    }
}