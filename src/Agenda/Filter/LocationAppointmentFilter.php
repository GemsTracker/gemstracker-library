<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\Appointment;
use Gems\Db\ResultFetcher;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:02:33
 */
class LocationAppointmentFilter extends AppointmentFilterAbstract
{
    /**
     * The locations that this filter matches or true when not matching against any location
     *
     * @var array glo_id_location => glo_id_location
     */
    protected array|bool $_locations;

    public function __construct(
        int $id,
        string $calculatedName,
        int $order,
        bool $active,
        ?string $manualName,
        ?string $text1,
        ?string $text2,
        ?string $text3,
        ?string $text4,
        protected readonly ResultFetcher $resultFetcher,
    ) {
        parent::__construct($id, $calculatedName, $order, $active, $manualName, $text1, $text2, $text3, $text4);
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
        if (!isset($this->_locations)) {

            if ($this->text1) {
                $sqlActivites = "SELECT glo_id_location, glo_id_location
                    FROM gems__locations
                    WHERE glo_active = 1 AND glo_name LIKE '%s'
                    ORDER BY glo_id_location";

                $this->_locations = $this->resultFetcher->fetchPairs(sprintf(
                        $sqlActivites,
                        addslashes($this->text1))
                        );
            } else {
                $this->_locations = true;
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
        $where = '';
        if (isset($this->_locations) && ($this->_locations !== true) && !empty($this->_locations)) {
            $where = 'gap_id_location IN (' . implode(', ', $this->_locations) . ')';
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
        if (isset($this->_locations) && true !== $this->_locations) {
            if (isset($this->_locations[$appointment->getLocationId()])) {
                return true;
            }
        }
        return false;
    }
}
