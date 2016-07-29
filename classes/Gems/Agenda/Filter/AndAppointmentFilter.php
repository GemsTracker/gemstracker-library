<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AndAppointmentFilter.php $
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\AppointmentFilterInterface;
use Gems\Agenda\AppointmentSubFilterAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-okt-2014 16:56:07
 */
class AndAppointmentFilter extends AppointmentSubFilterAbstract
{
    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlWhere()
    {
        $wheres = array();

        foreach ($this->_subFilters as $filterObject) {
            if ($filterObject instanceof AppointmentFilterInterface) {
                $where = $filterObject->getSqlWhere();
                if ($where && ($where !== parent::NO_MATCH_SQL)) {
                    $wheres[] = $where;
                }
            }
        }

        if ($wheres) {
            return implode(' AND ', $wheres);
        } else {
            return parent::NO_MATCH_SQL;
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
        foreach ($this->_subFilters as $filterObject) {
            if ($filterObject instanceof AppointmentFilterInterface) {
                if (! $filterObject->matchAppointment($appointment)) {
                    return false;
                }
            }
        }
        return true;
    }
}
