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

use Gems\Agenda\AppointmentFilterAbstract;

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
     * @var array activity_id => activity_id
     */
    protected $_activities;

    /**
     * The procdures that this filter matches or true when not matching against procdures
     *
     * @var array procedure_id => procedure_id
     */
    protected $_procedures;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

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
        if ($this->_data &&
                $this->db instanceof \Zend_Db_Adapter_Abstract &&
                !($this->_activities || $this->_procedures)) {

            if ($this->_data['gaf_filter_text1'] || $this->_data['gaf_filter_text2']) {
                $sqlActivites = "SELECT gaa_id_activity, gaa_id_activity
                    FROM gems__agenda_activities
                    WHERE gaa_active = 1 ";

                if ($this->_data['gaf_filter_text1']) {
                    $sqlActivites .= sprintf(
                            " AND gaa_name LIKE '%s' ",
                             addslashes($this->_data['gaf_filter_text1'])
                            );
                }
                if ($this->_data['gaf_filter_text2']) {
                    $sqlActivites .= sprintf(
                            " AND gaa_name NOT LIKE '%s' ",
                             addslashes($this->_data['gaf_filter_text2'])
                            );
                }
                $sqlActivites .= "ORDER BY gaa_id_activity";

                $this->_activities = $this->db->fetchPairs($sqlActivites);
            } else {
                $this->_activities = true;
            }

            if ($this->_data['gaf_filter_text3'] || $this->_data['gaf_filter_text4']) {
                $sqlProcedures = "SELECT gapr_id_procedure, gapr_id_procedure
                    FROM gems__agenda_procedures
                    WHERE gapr_active = 1 ";


                if ($this->_data['gaf_filter_text3']) {
                    $sqlProcedures .= sprintf(
                            " AND gapr_name LIKE '%s' ",
                             addslashes($this->_data['gaf_filter_text3'])
                            );
                }
                if ($this->_data['gaf_filter_text4']) {
                    $sqlProcedures .= sprintf(
                            " AND gapr_name NOT LIKE '%s' ",
                             addslashes($this->_data['gaf_filter_text4'])
                            );
                }

                $sqlProcedures .= "ORDER BY gapr_id_procedure";

                $this->_procedures = $this->db->fetchPairs($sqlProcedures);
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
    public function getSqlAppointmentsWhere()
    {
        if (is_array($this->_activities)) {
            if (! $this->_activities) {
                return parent::NO_MATCH_SQL;
            }
            $where = 'gap_id_activity IN (' . implode(', ', $this->_activities) . ')';

            if ($this->_procedures !== true) {
                $where .= ' AND ';
            }
        } else {
            $where = '';
        }
        if (is_array($this->_procedures)) {
            if (! $this->_procedures) {
                return parent::NO_MATCH_SQL;
            }
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
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(\Gems_Agenda_Appointment $appointment)
    {
        if (true !== $this->_activities) {
            if (! isset($this->_activities[$appointment->getActivityId()])) {
                return false;
            }
        }
        return isset($this->_procedures[$appointment->getProcedureId()]) || (true === $this->_procedures);
    }
}
