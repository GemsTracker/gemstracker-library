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
    public function exchangeArray(array $data);

    /**
     * Return the type of track creator this filter is
     *
     * @return int
     */
    public function getCreatorType();

    /**
     * The field id as it is recognized be the track engine
     *
     * @return string
     */
    public function getFieldId();

    /**
     * The filter id
     *
     * @return int
     */
    public function getFilterId();

    /**
     * The name of the filter
     *
     * @return string
     */
    public function getName();

    /**
     * Generate a where statement to filter an appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere();

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere();

    /**
     * The track id for the filter
     *
     * @return int
     */
    public function getTrackId();

    /**
     * The number of days to wait between track creation
     *
     * @return int or null when no track creation or no wait days
     */
    public function getWaitDays();

    /**
     * Should this track be created when it does not exist?
     *
     * @return boolean
     */
    public function isCreator();

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems\Agenda\Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(\Gems\Agenda\Appointment $appointment);

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\EpisodeOfCare $episode
     * @return boolean
     */
    public function matchEpisode(EpisodeOfCare $episode);

    /**
     *
     * @return boolean When true prefer SQL statements filtering appointments
     */
    public function preferAppointmentSql();
}
