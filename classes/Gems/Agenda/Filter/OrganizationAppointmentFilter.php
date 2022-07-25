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

use Gems\Agenda\AppointmentFilterAbstract;

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
    protected $_organizations;

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
    {
        if ($this->_data && ! $this->_organizations) {

            foreach (['gaf_filter_text1', 'gaf_filter_text2', 'gaf_filter_text3', 'gaf_filter_text4'] as $field) {
                if ($this->_data[$field]) {
                    $this->_organizations[$this->_data[$field]] = $this->_data[$field];
                }
            }
        }
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere()
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
     * @param \Gems\Agenda\Gems\Agenda\Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(\Gems\Agenda\Appointment $appointment)
    {
        if ($this->_organizations) {
            return isset($this->_organizations[$appointment->getOrganizationId()]);
        }
        return ! $appointment->getOrganizationId();
    }
}
