<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * ParameterSource is a central class for setting menu parameters.
 *
 * As using $request to set menu items currently does not perform parameter tests,
 * this is the place to set variables that are used in a parameter filter.
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Menu_ParameterSource extends ArrayObject implements Gems_Menu_ParameterSourceInterface
{
    /**
     * Helper function to set more than one array key to the same value
     *
     * @param mixed $value The value to set
     * @param string $name1 The first key to set
     * @param string $name2 Optional, second key, number of keys is unlimited
     */
    private function _setMulti($value, $name1, $name2 = null)
    {
        $args = func_get_args();
        array_shift($args);
        foreach ($args as $key) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Returns a value to use as parameter for $name or
     * $default if this object does not contain the value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getMenuParameter($name, $default)
    {
        // MUtil_Echo::track($name, MUtil_Lazy::raise($this->offsetGet($name)));
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        } else {
            return $default;
        }
    }

    public function setAppointmentId($appointmentId)
    {
        $this->_setMulti($appointmentId, Gems_Model::APPOINTMENT_ID, 'gap_id_appointment');

        return $this;
    }

    public function setPatient($patientNumber, $organizationId)
    {
        $this->_setMulti($patientNumber,  MUtil_Model::REQUEST_ID1, 'gr2o_patient_nr');
        $this->_setMulti($organizationId, MUtil_Model::REQUEST_ID2, 'gr2o_id_organization');

        return $this;
    }

    public function setRequestId($requestId)
    {
        $this->_setMulti($requestId, MUtil_Model::REQUEST_ID);

        return $this;
    }

    public function setRespondentTrackId($respTrackId)
    {
        $this->_setMulti($respTrackId, Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track');

        return $this;
    }

    public function setRoundId($roundId)
    {
        $this->_setMulti($roundId, Gems_Model::ROUND_ID, 'gro_id_round');

        return $this;
    }

    public function setTokenId($tokenId)
    {
        $this->_setMulti($tokenId, MUtil_Model::REQUEST_ID, 'gto_id_token');

        // Signal type of MUtil_Model::REQUEST_ID
        $this->offsetSet(Gems_Model::ID_TYPE, 'appointment');

        return $this;
    }

    public function setTrackId($trackId)
    {
        $this->_setMulti($trackId, Gems_Model::TRACK_ID, 'gtr_id_track');

        return $this;
    }

    public function setTrackType($trackType)
    {
        $this->_setMulti($trackType, 'gtr_track_type');

        return $this;
    }
}
