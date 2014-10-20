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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Tracker_Model_FieldDataModel extends \MUtil_Model_UnionModel
{
    /**
     * The last active appointment in any round
     *
     * @var \Gems_Agenda_Appointment
     */
    protected $_lastActiveAppointment;

    /**
     * The format string for outputting appointments
     *
     * @var string
     */
    protected $appointmentTimeFormat = 'dd MMM yyyy HH:MM';

    /**
     * The format string for outputting dates
     *
     * @var string
     */
    protected $dateTimeFormat = 'dd MMM yyyy';

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @param string $modelName Hopefully unique model name
     * @param string $modelField The name of the field used to store the sub model
     */
    public function __construct($modelName = 'fields_maintenance', $modelField = 'sub')
    {
        parent::__construct($modelName, $modelField);

        $model = new MUtil_Model_TableModel('gems__respondent2track2field');
        Gems_Model::setChangeFieldsByPrefix($model, 'gr2t2f');
        $this->addUnionModel($model, null, Gems_Tracker_Model_FieldMaintenanceModel::FIELDS_NAME);

        $model = new MUtil_Model_TableModel('gems__respondent2track2appointment');
        Gems_Model::setChangeFieldsByPrefix($model, 'gr2t2a');

        $map = $model->getItemsOrdered();
        $map = array_combine($map, str_replace('gr2t2a_', 'gr2t2f_', $map));
        $map['gr2t2a_id_app_field'] = 'gr2t2f_id_field';
        $map['gr2t2a_id_appointment'] = 'gr2t2f_value';

        $this->addUnionModel($model, $map, Gems_Tracker_Model_FieldMaintenanceModel::APPOINTMENTS_NAME);
    }

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param string $name The name of the field
     * @param array $context The other values loaded so far
     * @param array $fieldData The field definition for this data item
     * @param int $respTrackId Gems respondent track id
     * @return mixed the new value
     */
    public function calculateFieldInfoAppointment($currentValue, $name, array $context, array $fieldData, $respTrackId)
    {
        if (! $currentValue) {
            return $currentValue;
        }

        $agenda = $this->loader->getAgenda();
        $appointment = $agenda->getAppointment($currentValue);

        if ($appointment->isActive()) {
            $time = $appointment->getAdmissionTime();

            if ($time) {
                return array($fieldData['gtf_field_name'], ' ', $time->toString($this->appointmentTimeFormat));
            }
        }

        return null;
    }

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param string $name The name of the field
     * @param array $context The other values loaded so far
     * @param array $fieldData The field definition for this data item
     * @param int $respTrackId Gems respondent track id
     * @return mixed the new value
     */
    public function calculateFieldInfoDate($currentValue, $name, array $context, array $fieldData, $respTrackId)
    {
        if (! $currentValue) {
            return $currentValue;
        }

        if ($currentValue instanceof Zend_Date) {
            $value = $currentValue->toString($this->dateTimeFormat);
        } elseif ($currentValue instanceof DateTime) {
            $value = date('j M Y', $currentValue->getTimestamp());
        } else {
            $value = $currentValue;
        }

        return array($fieldData['gtf_field_name'], ' ', $value);
    }

    /**
     * On save calculation function
     *
     * @param array $currentValue The current value
     * @param array $context The other values loaded so far
     * @param int $respTrackId Gems respondent track id
     * @return mixed the new value
     */
    public function calculateOnLoadDate($currentValue, array $context, $respTrackId)
    {
        if (empty($currentValue)) {
            return null;
        }

        return new MUtil_Date($currentValue, Zend_Date::ISO_8601);
    }

    /**
     * On save calculation function
     *
     * @param array $currentValue The current value
     * @param array $values Containing filter => int
     * @param array $context The other values being saved
     * @param int $respTrackId Gems respondent track id
     * @return mixed the new value
     */
    public function calculateOnSaveAppointment($currentValue, array $values, array $context, $respTrackId)
    {
        if ($currentValue || $values) {
            $agenda = $this->loader->getAgenda();

            if (isset($values['filter'])) {
                if ($this->_lastActiveAppointment) {
                    $appointment = $this->_lastActiveAppointment;
                    $orEqual     = false;
                } else {
                    // MUtil_Echo::track($currentValue);
                    /* No longer fixing first appointment
                    if ($currentValue) {
                        $appointment = $agenda->getAppointment($currentValue);
                    } else {
                        $appointment = null;
                    } //*/
                    $appointment = null;
                    $orEqual = true;
                }
                if ($appointment && $appointment->isActive()) {
                    // MUtil_Echo::track($appointment->getAdmissionTime()->toString());
                    $respId   = $appointment->getRespondentId();
                    $orgId    = $appointment->getOrganizationId();
                    $fromDate = $appointment;
                } else {
                    $respTrack = $this->tracker->getRespondentTrack($respTrackId);
                    $respId    = $respTrack->getRespondentId();
                    $orgId     = $respTrack->getOrganizationId();
                    $fromDate  = $respTrack->getStartDate();
                }
                // MUtil_Echo::track($respId, $orgId, get_class($fromDate));
                $newValue = $agenda->findFirstAppointmentId($values['filter'], $respId, $orgId, $fromDate, $orEqual);
                // MUtil_Echo::track($newValue);

                if ($newValue) {
                    $currentValue = $newValue;
                }
            }

            if ($currentValue) {
                $appointment = $agenda->getAppointment($currentValue);

                if ($appointment->isActive()) {
                    $this->_lastActiveAppointment = $appointment;
                }
            }
        }

        return $currentValue;
    }

    /**
     * On save calculation function
     *
     * @param array $currentValue The current value
     * @param array $values The values for the checked calculate from fields
     * @param array $context The other values being saved
     * @param int $respTrackId Gems respondent track id
     * @return mixed the new value
     */
    public function calculateOnSaveCaretaker($currentValue, array $values, array $context, $respTrackId)
    {
        if ($values) {
            $agenda = $this->loader->getAgenda();

            foreach (array_reverse(array_filter($values)) as $value) {
                $appointment = $agenda->getAppointment($value);

                if ($appointment->exists) {
                    return $appointment->getAttendedById();
                }
            }
        }

        return $currentValue;
    }

    /**
     * On save calculation function
     *
     * @param array $currentValue The current value
     * @param array $values The values for the checked calculate from fields
     * @param array $context The other values being saved
     * @param int $respTrackId Gems respondent track id
     * @return mixed the new value
     */
    public function calculateOnSaveLocation($currentValue, array $values, array $context, $respTrackId)
    {
        if ($values) {
            $agenda = $this->loader->getAgenda();

            foreach (array_reverse(array_filter($values)) as $value) {
                $appointment = $agenda->getAppointment($value);

                if ($appointment->exists) {
                    return $appointment->getLocationId();
                }
            }
        }

        return $currentValue;
    }

    /**
     * Calls $this->save() multiple times for each element
     * in the input array and returns the number of saved rows.
     *
     * @param array $newValues A nested array
     * @return int The number of changed rows
     */
    public function saveAll(array $newValues)
    {
        // Clear before the next run
        $this->_lastActiveAppointment = null;

        return parent::saveAll($newValues);
    }
}
