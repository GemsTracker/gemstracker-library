<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SqlLikeAppointmentFilter.php $
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
class FieldLikeAppointmentFilter extends AppointmentFilterAbstract
{
    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     * @var array id => id
     */
    protected $_fieldList2;

    /**
     * @var array id => id
     */
    protected $_fieldList4;

    /**
     * List of lookup tables for linked values
     *
     * @return array fieldname => array(tableName, tableId, tableLikeFilter)
     */
    protected $_lookupTables = array(
            'gap_id_organization' => array(
                'tableName' => 'gems__organizations',
                'tableId' => 'gor_id_organization',
                'tableLikeFilter' => "gor_active = 1 AND gor_name LIKE '%s'",
                ),
            'gap_id_attended_by' => array(
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
                ),
            'gap_id_referred_by' => array(
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
                ),
            'gap_id_activity' => array(
                'tableName' => 'gems__agenda_activities',
                'tableId' => 'gaa_id_activity',
                'tableLikeFilter' => "gaa_active = 1 AND gaa_name LIKE '%s'",
                ),
            'gap_id_procedure' => array(
                'tableName' => 'gems__agenda_procedures',
                'tableId' => 'gapr_id_procedure',
                'tableLikeFilter' => "gapr_active = 1 AND gapr_name LIKE '%s'",
                ),
            'gap_id_location' => array(
                'tableName' => 'gems__locations',
                'tableId' => 'glo_id_location',
                'tableLikeFilter' => "glo_active = 1 AND glo_name LIKE '%s'",
                ),
        );

    /*
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
                $this->db instanceof \Zend_Db_Adapter_Abstract) {

            if ($this->_data['gaf_filter_text1'] && $this->_data['gaf_filter_text2']) {
                if (isset($this->_lookupTables[$this->_data['gaf_filter_text1']])) {
                    $table = $this->_lookupTables[$this->_data['gaf_filter_text1']]['tableName'];
                    $id    = $this->_lookupTables[$this->_data['gaf_filter_text1']]['tableId'];
                    $like  = $this->_lookupTables[$this->_data['gaf_filter_text1']]['tableLikeFilter'];

                    $sql  = "SELECT $id, $id FROM $table WHERE $like ORDER BY $id";
                    $this->_fieldList2 = $this->db->fetchPairs(sprintf(
                            $sql,
                            addslashes($this->_data['gaf_filter_text2']))
                            );
                } else {
                    $this->_fieldList2 = null;
                }
            } else {
                $this->_fieldList2 = true;
            }

            if ($this->_data['gaf_filter_text3'] && $this->_data['gaf_filter_text4']) {
                if (isset($this->_lookupTables[$this->_data['gaf_filter_text3']])) {
                    $table = $this->_lookupTables[$this->_data['gaf_filter_text3']]['tableName'];
                    $id    = $this->_lookupTables[$this->_data['gaf_filter_text3']]['tableId'];
                    $like  = $this->_lookupTables[$this->_data['gaf_filter_text3']]['tableLikeFilter'];

                    $sql   = "SELECT $id, $id FROM $table WHERE $like ORDER BY $id";
                    $this->_fieldList4 = $this->db->fetchPairs(sprintf(
                            $sql,
                            addslashes($this->_data['gaf_filter_text4']))
                            );
                } else {
                    $this->_fieldList4 = null;
                }
            } else {
                $this->_fieldList4 = true;
            }
        }
    }

    /**
     * Get the field value from an appointment object
     *
     * @param \Gems_Agenda_Appointment $appointment
     * @param string $field
     * @return mixed
     */
    public function getAppointmentFieldVale(\Gems_Agenda_Appointment $appointment, $field)
    {
        switch ($field) {
            case 'gap_id_organization':
                return $appointment->getOrganizationId();

            case 'gap_source':
                return $appointment->getSource();

            case 'gap_id_attended_by':
                return $appointment->getAttendedById();

            case 'gap_id_referred_by':
                return $appointment->getReferredById();

            case 'gap_id_activity':
                return $appointment->getActivityId();

            case 'gap_id_procedure':
                return $appointment->getProcedureId();

            case 'gap_id_location':
                return $appointment->getLocationId();

            case 'gap_subject':
                return $appointment->getSubject();
        }
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlWhere()
    {
        if ($this->_data['gaf_filter_text1'] && $this->_data['gaf_filter_text2']) {
            if (isset($this->_lookupTables[$this->_data['gaf_filter_text1']])) {
                if ($this->_fieldList2 && $this->_fieldList2 !== true) {
                    $where = $this->_data['gaf_filter_text1']. ' IN (' . implode(', ', $this->_fieldList2) . ')';
                } else {
                    return parent::NO_MATCH_SQL;
                }
            } else {
                $text  = $this->_data['gaf_filter_text2'];
                $where = $this->_data['gaf_filter_text1'] . " LIKE '$text'";
            }
        } else {
            $where = '';
        }

        if ($this->_data['gaf_filter_text3'] && $this->_data['gaf_filter_text4']) {
            if ($where) {
                $where .= ' AND ';
            }
            if (isset($this->_lookupTables[$this->_data['gaf_filter_text3']])) {
                if ($this->_fieldList4 && $this->_fieldList4 !== true) {
                    $where .= $this->_data['gaf_filter_text3']. ' IN (' . implode(', ', $this->_fieldList4) . ')';
                } else {
                    return parent::NO_MATCH_SQL;
                }
            } else {
                $text  = $this->_data['gaf_filter_text4'];
                $where .= $this->_data['gaf_filter_text3'] . " LIKE '$text'";
            }
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
        if ($this->_data['gaf_filter_text1'] && $this->_data['gaf_filter_text2']) {
            $value = $this->getAppointmentFieldVale($appointment, $this->_data['gaf_filter_text1']);
            if (isset($this->_lookupTables[$this->_data['gaf_filter_text1']])) {
                if ($this->_fieldList2 && $this->_fieldList2 !== true) {
                    if (! isset($this->_fieldList2[$value])) {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                $regex = '/' . str_replace(array('%', '_'), array('.*', '.{1,1}'),$this->_data['gaf_filter_text2']) . '/i';

                if (! (boolean) preg_match($regex, $value)) {
                    return false;
                }
            }
        }

        if ($this->_data['gaf_filter_text3'] && $this->_data['gaf_filter_text4']) {
            $value = $this->getAppointmentFieldVale($appointment, $this->_data['gaf_filter_text3']);
            if (isset($this->_lookupTables[$this->_data['gaf_filter_text3']])) {
                if ($this->_fieldList4 && $this->_fieldList4 !== true) {
                    if (! isset($this->_fieldList4[$value])) {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                $regex = '/' . str_replace(array('%', '_'), array('.*', '.{1,1}'),$this->_data['gaf_filter_text2']) . '/i';

                if (! (boolean) preg_match($regex, $value)) {
                    return false;
                }
            }
        }

        return true;
    }
}
