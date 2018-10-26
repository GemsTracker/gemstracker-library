<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SubjectAppointmentFilter.php $
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\AppointmentFilterAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:02:33
 */
class SubjectAppointmentFilter extends AppointmentFilterAbstract
{
    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere()
    {
        $text = $this->_data['gaf_filter_text1'];
        if ($text) {
            return "gap_subject LIKE '$text'";
        } else {
            return "(gap_subject IS NULL OR gap_subject = '')";
        }
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(\Gems_Agenda_Appointment $appointment)
    {
        if (! $this->_data['gaf_filter_text1']) {
            return ! $appointment->getSubject();
        }

        $regex = '/' . str_replace(array('%', '_'), array('.*', '.{1,1}'),$this->_data['gaf_filter_text1']) . '/i';

        return (boolean) preg_match($regex, $appointment->getSubject());
    }
}
