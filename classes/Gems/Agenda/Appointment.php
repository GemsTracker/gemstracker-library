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
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Appointment .php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Agenda_Appointment extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var int The id of the appointment
     */
    protected $_appointmentId;

    /**
     *
     * @var array The gems appointment data
     */
    protected $_gemsData = array();

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * True when the token does exist.
     *
     * @var boolean
     */
    public $exists = true;

    /**
     * Creates the appointments object
     *
     * @param mixed $appointmentData Appointment Id or array containing appointment record
     */
    public function __construct($appointmentData)
    {
        if (is_array($gemsTokenData)) {
            $this->_gemsData       = $appointmentData;
            $this->_appointmentId  = $appointmentData['gap_id_appointment'];
        } else {
            $this->_appointmentId  = $appointmentData;
            // loading occurs in checkRegistryRequestAnswers
        }
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db && (! $this->_gemsData)) {
            $this->refresh();
        }

        return $this->exists;
    }

    /**
     *
     * @return MUtil_Date Admission time as a date or null
     */
    public function getAdmissionTime()
    {
        if (isset($this->_gemsData['gap_admission_time']) && $this->_gemsData['gap_admission_time']) {
            if ($this->_gemsData['gap_admission_time'] instanceof MUtil_Date) {
                return $this->_gemsData['gap_admission_time'];
            }
            return new MUtil_Date($this->_gemsData['gap_admission_time'], Gems_Tracker::DB_DATETIME_FORMAT);
        }
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return \Gems_Agenda_Appointment (continuation pattern)
     */
    public function refresh(array $gemsData = null)
    {
        if (is_array($gemsData)) {
            $this->_gemsData = $gemsData + $this->_gemsData;
        } else {
            $select = $this->db->select();
            $select->from('gems__appointments')
                    ->where('gap_id_appointment = ?', $this->_appointmentId);

            $this->_gemsData = $this->db->fetchRow($select);
            if (false == $this->_gemsData) {
                // on failure, reset to empty array
                $this->_gemsData = array();
            }
        }
        $this->exists = isset($this->_gemsData['gap_id_appointment']);

        return $this;
    }
}
