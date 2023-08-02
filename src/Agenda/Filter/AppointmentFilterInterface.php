<?php

namespace Gems\Agenda\Filter;

use Gems\Agenda\Appointment;
use Gems\Agenda\EpisodeOfCare;

interface AppointmentFilterInterface
{
    /**
     * The filter id
     *
     * @return int
     */
    public function getFilterId(): int;

    /**
     * The name of the filter
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Generate a where statement to filter an appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere(): string;

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere(): string;

    /**
     * Check a filter for a match
     *
     * @param Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(Appointment $appointment): bool;

    /**
     * Check a filter for a match
     *
     * @param EpisodeOfCare $episode
     * @return boolean
     */
    public function matchEpisode(EpisodeOfCare $episode): bool;

    /**
     *
     * @return boolean When true prefer SQL statements filtering appointments
     */
    public function preferAppointmentSql(): bool;
}