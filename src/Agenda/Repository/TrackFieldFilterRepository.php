<?php

namespace Gems\Agenda\Repository;

use Gems\Agenda\Appointment;
use Gems\Agenda\EpisodeOfCare;
use Gems\Agenda\Filter\TrackFieldFilterCalculation;
use Gems\Agenda\Filter\TrackFieldFilterCalculationInterface;
use Gems\Agenda\FilterTracer;
use Gems\Db\CachedResultFetcher;
use Gems\Tracker\RespondentTrack;

class TrackFieldFilterRepository
{
    protected array $cacheTags = [
        'appointment_filters',
        'tracks',
    ];
    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        protected readonly FilterRepository $filterRepository,
        protected readonly FilterCreateTrackChecker $createTrackChecker,
    )
    {}

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

        $filterDataList = $this->cachedResultFetcher->fetchAll('allActivelyUsedAppointmentFilters', $sql, null, $this->cacheTags);

        $filters = [];
        foreach ($filterDataList as $filterData) {
            $appointmentFilter = $this->filterRepository->getFilterFromData($filterData);
            $filters[] = new TrackFieldFilterCalculation(
                $filterData['gtap_id_app_field'],
                $filterData['gtap_id_track'],
                $appointmentFilter,
                $filterData['gtap_create_track'],
                $filterData['gtap_create_wait_days'],
            );
        }

        return $filters;
    }

    /**
     *
     * @param Appointment|EpisodeOfCare $to
     * @return TrackFieldFilterCalculationInterface[]
     */
    public function matchFilters(Appointment|EpisodeOfCare $to): array
    {
        $filters = $this->getAllActivelyUsedFilters();
        $output  = [];

        if ($to instanceof Appointment) {
            foreach ($filters as $filter) {
                if ($filter instanceof TrackFieldFilterCalculationInterface) {
                    if ($filter->getAppointmentFilter()->matchAppointment($to)) {
                        $output[] = $filter;
                    }
                }
            }
        } elseif ($to instanceof EpisodeOfCare) {
            foreach ($filters as $filter) {
                if ($filter instanceof TrackFieldFilterCalculationInterface) {
                    if ($filter->getAppointmentFilter()->matchEpisode($to)) {
                        $output[] = $filter;
                    }
                }
            }
        }

        return $output;
    }

    public function shouldCreateTrack(
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        return $this->createTrackChecker->shouldCreateTrack($appointment, $filter, $respTrack, $filterTracer);
    }
}
