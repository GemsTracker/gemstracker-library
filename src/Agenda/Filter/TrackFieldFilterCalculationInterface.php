<?php

namespace Gems\Agenda\Filter;

interface TrackFieldFilterCalculationInterface
{
    /**
     * @return AppointmentFilterInterface
     */
    public function getAppointmentFilter(): AppointmentFilterInterface;

    /**
     * Return the type of track creator this filter is
     *
     * @return int
     */
    public function getCreatorType(): int;

    /**
     * The field id as it is recognized be the track engine
     *
     * @return string
     */
    public function getFieldId(): string|null;

    /**
     * The track id for the filter
     *
     * @return int
     */
    public function getTrackId(): int|null;

    /**
     * The number of days to wait between track creation
     *
     * @return int or null when no track creation or no wait days
     */
    public function getWaitDays(): int|null;

    /**
     * Should this track be created when it does not exist?
     *
     * @return boolean
     */
    public function isCreator(): bool;

}