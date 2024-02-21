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
 * @since      Class available since version 1.6.5 13-okt-2014 20:01:38
 */
class ActProcAppointmentFilter extends AppointmentFilterAbstract
{
    /**
     * The activities that this filter matches or true when not matching against activities
     *
     * @var array|bool activity_id => activity_id
     */
    protected array|bool $_activities = false;

    /**
     * The procedures that this filter matches or true when not matching against procedures
     *
     * @var array|bool procedure_id => procedure_id
     */
    protected array|bool $_procedures = false;

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
        if (!($this->_activities || $this->_procedures)) {

            if ($this->text1 || $this->text2) {
                $sqlActivities = "SELECT gaa_id_activity, gaa_id_activity
                    FROM gems__agenda_activities
                    WHERE gaa_active = 1 ";

                if ($this->text1) {
                    $sqlActivities .= sprintf(
                            " AND gaa_name LIKE '%s' ",
                             addslashes($this->text1)
                            );
                }
                if ($this->text2) {
                    $sqlActivities .= sprintf(
                            " AND gaa_name NOT LIKE '%s' ",
                             addslashes($this->text2)
                            );
                }
                $sqlActivities .= "ORDER BY gaa_id_activity";

                $this->_activities = $this->resultFetcher->fetchPairs($sqlActivities);
            } else {
                $this->_activities = true;
            }

            if ($this->text3 || $this->text4) {
                $sqlProcedures = "SELECT gapr_id_procedure, gapr_id_procedure
                    FROM gems__agenda_procedures
                    WHERE gapr_active = 1 ";


                if ($this->text3) {
                    $sqlProcedures .= sprintf(
                            " AND gapr_name LIKE '%s' ",
                             addslashes($this->text3)
                            );
                }
                if ($this->text4) {
                    $sqlProcedures .= sprintf(
                            " AND gapr_name NOT LIKE '%s' ",
                             addslashes($this->text4)
                            );
                }

                $sqlProcedures .= "ORDER BY gapr_id_procedure";

                $this->_procedures = $this->resultFetcher->fetchPairs($sqlProcedures);
            } else {
                $this->_procedures = true;
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
        if ($this->_activities && ($this->_activities !== true)) {
            $where = 'gap_id_activity IN (' . implode(', ', $this->_activities) . ')';

            if ($this->_procedures !== true) {
                $where .= ' AND ';
            }
        } else {
            $where = '';
        }
        if ($this->_procedures && ($this->_procedures !== true)) {
            $where .= 'gap_id_procedure IN (' . implode(', ', $this->_procedures) . ')';
        } elseif ($where && (! $this->_procedures)) {
            $where .= parent::NO_MATCH_SQL;
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
     * @return bool
     */
    public function matchAppointment(Appointment $appointment): bool
    {
        if (true !== $this->_activities) {
            if (! isset($this->_activities[$appointment->getActivityId()])) {
                return false;
            }
        }
        return isset($this->_procedures[$appointment->getProcedureId()]) || (true === $this->_procedures);
    }
}