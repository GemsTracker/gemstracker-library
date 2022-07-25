<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5
 */
class ProcedureField  extends AppointmentDerivedFieldAbstract
{
    /**
     * Return the appropriate Id for the given appointment
     * 
     * @param \Gems\Agenda\Appointment $appointment
     * @return int
     */
    protected function getId(\Gems\Agenda\Appointment$appointment) {
        return $appointment->getProcedureId();
    }

    /**
     * Return the looup array for this field
     * 
     * @param int $organizationId Organization Id
     * @return array
     */
    protected function getLookup($organizationId = null) {
        return $this->getAgenda()->getProcedures($organizationId);
    }

}