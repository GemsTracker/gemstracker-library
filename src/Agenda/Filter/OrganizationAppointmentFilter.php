<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Equipe Zorgbedrijven and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\Appointment;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2018, Equipe Zorgbedrijven and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 22-Oct-2018 12:19:53
 */
class OrganizationAppointmentFilter extends AppointmentFilterAbstract
{
    /**
     * The organizations that this filter matches
     *
     * @var array gor_id_organization => gor_id_organization
     */
    protected array $_organizations;

    protected function afterLoad(): void
    {
        foreach (['text1', 'text2', 'text3', 'text4'] as $field) {
            if ($this->$field) {
                $this->_organizations[$this->$field] = $this->$field;
            }
        }
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere(): string
    {
        if ($this->_organizations) {
            $where = 'gap_id_organization IN (' . implode(', ', $this->_organizations) . ')';
        } else {
            $where = '';
        }
        if ($where) {
            return "($where)";
        } else {
            return 'gap_id_organization IS NULL';
        }
    }

    /**
     * Check a filter for a match
     *
     * @param Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(Appointment $appointment): bool
    {
        if ($this->_organizations) {
            return isset($this->_organizations[$appointment->getOrganizationId()]);
        }
        return ! $appointment->getOrganizationId();
    }
}
