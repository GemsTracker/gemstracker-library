<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentFilterInterface.php $
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
// interface Gems_Agenda_AppointmentFilterInterface
interface AppointmentFilterInterface
{
    /**
     * Load the object from a data array
     *
     * @param array $data
     */
    public function exchangeArray(array $data);

    /**
     * The appointment field id from gtap_id_app_field
     *
     * @return int
     */
    public function getAppointmentFieldId();
    
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
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlWhere();

    /**
     * The track field id for the filter
     *
     * @return int
     */
    public function getTrackAppointmentFieldId();

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
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(\Gems_Agenda_Appointment $appointment);
}
