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
 * @version    $Id: AppointmentFilterAbstract.php $
 */

namespace Gems\Agenda;

use Gems\Tracker\Engine\FieldsDefinition;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:13:01
 */
abstract class AppointmentFilterAbstract extends \MUtil_Translate_TranslateableAbstract
    implements AppointmentFilterInterface, \Serializable
{
    /**
     * Constant for filters that should never trigger
     */
    const NO_MATCH_SQL = '1=0';

    /**
     * Initial data settings
     *
     * @var array
     */
    protected $_data;

    /**
     * Override this function when you need to perform any actions when the data is loaded.
     *
     * Test for the availability of variables as these objects can be loaded data first after
     * deserialization or registry variables first after normal instantiation.
     *
     * That is why this function called both at the end of afterRegistry() and after exchangeArray(),
     * but NOT after unserialize().
     *
     * After this the object should be ready for serialization
     */
    protected function afterLoad()
    { }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->afterLoad();
    }

    /**
     * Load the object from a data array
     *
     * @param array $data
     */
    public function exchangeArray(array $data)
    {
        $this->_data = $data;
        $this->afterLoad();
    }

    /**
     * The appointment field id from gtap_id_app_field
     *
     * @return int
     */
    public function getAppointmentFieldId()
    {
        if (isset($this->_data['gtap_id_app_field']) && $this->_data['gtap_id_app_field']) {
            return $this->_data['gtap_id_app_field'];
        }
    }

    /**
     * The field id as it is recognized be the track engine
     *
     * @return string
     */
    public function getFieldId()
    {
        if (isset($this->_data['gtap_id_app_field']) && $this->_data['gtap_id_app_field']) {
            return FieldsDefinition::makeKey(
                    \Gems_Tracker_Model_FieldMaintenanceModel::APPOINTMENTS_NAME,
                    $this->_data['gtap_id_app_field']
                    );
        }
    }

    /**
     * The filter id
     *
     * @return int
     */
    public function getFilterId()
    {
        return $this->_data['gaf_id'];
    }

    /**
     * The name of the filter
     *
     * @return string
     */
    public function getName()
    {
        return $this->_data['gaf_manual_name'] ? $this->_data['gaf_manual_name'] : $this->_data['gaf_calc_name'];
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    // public function getSqlWhere();

    /**
     * The track field id for the filter
     *
     * @return int
     */
    public function getTrackAppointmentFieldId()
    {
        if (isset($this->_data['gtap_id_app_field']) && $this->_data['gtap_id_app_field']) {
            return $this->_data['gtap_id_app_field'];
        }
    }

    /**
     * The track id for the filter
     *
     * @return int
     */
    public function getTrackId()
    {
        if (isset($this->_data['gtap_id_track']) && $this->_data['gtap_id_track']) {
            return $this->_data['gtap_id_track'];
        }
    }

    /**
     * The number of days to wait between track creation
     *
     * @return int or null when no track creation or no wait days
     */
    public function getWaitDays()
    {
        if (isset($this->_data['gtap_create_wait_days'], $this->_data['gtap_create_track']) &&
                $this->_data['gtap_create_track']) {
            return intval($this->_data['gtap_create_wait_days']);
        }
    }

    /**
     * Should this track be created when it does not exist?
     *
     * @return boolean
     */
    public function isCreator()
    {
        return isset($this->_data['gtap_create_track']) && $this->_data['gtap_create_track'];
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    // public function matchAppointment(\Gems_Agenda_Appointment $appointment);

    /**
     * By default only object variables starting with '_' are serialized in order to
     * avoid serializing any resource types loaded by
     * \MUtil_Translate_TranslateableAbstract
     *
     * @return string
     */
    public function serialize() {
        $data = array();
        foreach (get_object_vars($this) as $name => $value) {
            if (! $this->filterRequestNames($name)) {
                $data[$name] = $value;
            }
        }
        return serialize($data);
    }

    /**
     * Restore parameter values
     *
     * @param string $data
     */
    public function unserialize($data) {

        foreach ((array) unserialize($data) as $name => $value) {
            $this->$name = $value;
        }
    }
}
