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
use Gems\Agenda\AppointmentFilterAbstract;
use Gems\Db\ResultFetcher;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2018, Equipe Zorgbedrijven and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 22-Oct-2018 12:19:53
 */
class WithAppointmentFilter extends AppointmentFilterAbstract
{
    /**
     * The staff that this filter matches or true when not matching against staff
     *
     * @var array gas_id_staff => gas_id_staff
     */
    protected array|bool $_staff = false;

    public function __construct(
        array $_data,
        protected readonly ResultFetcher $resultFetcher,
    )
    {
        parent::__construct($_data);
    }

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
    protected function afterLoad(): void
    {
        if ($this->_data && !$this->_staff) {

            if ($this->_data['gaf_filter_text1']) {
                $sqlActivites = "SELECT gas_id_staff, gas_id_staff
                    FROM gems__agenda_staff
                    WHERE gas_active = 1 AND gas_name LIKE '%s'
                    ORDER BY gas_id_staff";

                $this->_staff = $this->resultFetcher->fetchPairs(sprintf(
                        $sqlActivites,
                        addslashes($this->_data['gaf_filter_text1']))
                        );
            } else {
                $this->_staff = true;
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
        if ($this->_staff && ($this->_staff !== true)) {
            $where = 'gap_id_attended_by IN (' . implode(', ', $this->_staff) . ')';
        } else {
            $where = '';
        }
        if ($where) {
            return "($where)";
        } else {
            return parent::NO_MATCH_SQL;
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
        if (true !== $this->_staff) {
            if (isset($this->_staff[$appointment->getAttendedById()])) {
                return true;
            }
        }
        return false;
    }
}
