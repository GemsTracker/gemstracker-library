<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
