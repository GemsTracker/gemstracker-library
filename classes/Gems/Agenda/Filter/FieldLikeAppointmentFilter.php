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
            
            $this->_fieldList2 = $this->loadFieldList($this->_data['gaf_filter_text1'], $this->_data['gaf_filter_text2']);
            $this->_fieldList4 = $this->loadFieldList($this->_data['gaf_filter_text3'], $this->_data['gaf_filter_text4']);
        }
    }

    /**
     * Get the field value from an appointment object
     *
     * @param \Gems_Agenda_Appointment $appointment
     * @param string $field
     * @return mixed
     */
    public function getAppointmentFieldValue(\Gems_Agenda_Appointment $appointment, $field)
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
            $wheres[] = $this->getSqlWhereSingle($this->_data['gaf_filter_text1'], $this->_data['gaf_filter_text2'], $this->_fieldList2);
        }
        if ($this->_data['gaf_filter_text3'] && $this->_data['gaf_filter_text4']) {
            $wheres[] = $this->getSqlWhereSingle($this->_data['gaf_filter_text3'], $this->_data['gaf_filter_text4'], $this->_fieldList4);
        }
        
        $where = join(' AND ', $wheres);

        if ($where) {
            return "($where)";
        } else {
            return parent::NO_MATCH_SQL;
        }
    }
    
    protected function getSqlWhereSingle($field, $searchTxt, $fieldList)
    {
        $where = '';
        
        if ($field && $searchTxt) {
            if(isset($this->_lookupTables[$field])) {
                if ($fieldList && $fieldList !== true) {
                    $where .= $field. ' IN (' . implode(', ', $fieldList) . ')';
                } else {
                    $where = parent::NO_MATCH_SQL;
                }
            } else {
                $where .= $field . " LIKE '$searchTxt'";
            }
        }
        
        return $where;
    }
    
    protected function loadFieldList($field, $searchTxt)
    {
        $result = null;
        
        if ($field && $searchTxt) {
            if (isset($this->_lookupTables[$field])) {
                $table = $this->_lookupTables[$field]['tableName'];
                $id    = $this->_lookupTables[$field]['tableId'];
                $like  = $this->_lookupTables[$field]['tableLikeFilter'];

                $sql    = "SELECT $id, $id FROM $table WHERE $like ORDER BY $id";
                $result = $this->db->fetchPairs(sprintf($sql, addslashes($searchTxt)));
            }
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(\Gems_Agenda_Appointment $appointment)
    {
        $result1 = $this->matchSingle(
                $this->_data['gaf_filter_text1'], 
                $this->_data['gaf_filter_text2'], 
                $this->_fieldList2, 
                $appointment);
        
        $result2 = $this->matchSingle(
                $this->_data['gaf_filter_text3'], 
                $this->_data['gaf_filter_text4'], 
                $this->_fieldList4, 
                $appointment);
        
        return $result1 && $result2;
    }
    
    /**
     * Check a filter for a match
     *
     * @param $field
     * @param $searchTxt
     * @param $fieldList
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    protected function matchSingle($field, $searchTxt, $fieldList, $appointment)
    {
        $result = true;
        
        if ($field && $searchTxt) {
            $value = $this->getAppointmentFieldValue($appointment, $field);
            if (isset($this->_lookupTables[$field])) {
                if ($fieldList && $fieldList !== true) {
                    if (! isset($fieldList)) {
                        $result = false;
                    }
                } else {
                    $result = false;
                }
            } else {
                $regex = '/' . str_replace(array('%', '_'), array('.*', '.{1,1}'), $searchTxt) . '/i';

                if (! (boolean) preg_match($regex, $value)) {
                    $result = false;
                }
            }
        }
        
        return $result;
    }
}
