<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:00:03
 */
interface AppointmentFilterInterface
{
    /**
     * Load the object from a data array
     *
     * @param array $data
     */
    public function exchangeArray(array $data): void;

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
