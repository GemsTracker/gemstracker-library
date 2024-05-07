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
class FieldLikeAppointmentFilter extends AppointmentFilterAbstract
{
    /**
     * @var array id => id
     */
    protected array|null $_fieldList2 = null;

    /**
     * @var array id => id
     */
    protected array|null $_fieldList4 = null;

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

        $this->_fieldList2 = $this->loadFieldList($this->text1, $this->text2);
        $this->_fieldList4 = $this->loadFieldList($this->text3, $this->text4);
    }

    /**
     * List of lookup tables for linked values
     *
     * @return array fieldname => array(tableName, tableId, tableLikeFilter)
     */
    protected $_lookupTables = [
            'gap_id_organization' => [
                'tableName' => 'gems__organizations',
                'tableId' => 'gor_id_organization',
                'tableLikeFilter' => "gor_active = 1 AND gor_name LIKE '%s'",
            ],
            'gap_id_attended_by' => [
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
            ],
            'gap_id_referred_by' => [
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
            ],
            'gap_id_activity' => [
                'tableName' => 'gems__agenda_activities',
                'tableId' => 'gaa_id_activity',
                'tableLikeFilter' => "gaa_active = 1 AND gaa_name LIKE '%s'",
            ],
            'gap_id_procedure' => [
                'tableName' => 'gems__agenda_procedures',
                'tableId' => 'gapr_id_procedure',
                'tableLikeFilter' => "gapr_active = 1 AND gapr_name LIKE '%s'",
            ],
            'gap_id_location' => [
                'tableName' => 'gems__locations',
                'tableId' => 'glo_id_location',
                'tableLikeFilter' => "glo_active = 1 AND glo_name LIKE '%s'",
            ],
    ];

    /**
     * Get the field value from an appointment object
     *
     * @param Appointment $appointment
     * @param string $field
     * @return string|int|null
     */
    public function getAppointmentFieldValue(Appointment $appointment, string $field): string|int|null
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

        return null;
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere(): string
    {
        $wheres = [];
        if ($this->text1 && $this->text2) {
            $wheres[] = $this->getSqlWhereSingle($this->text1, $this->text2, $this->_fieldList2);
        }
        if ($this->text3 && $this->text4) {
            $wheres[] = $this->getSqlWhereSingle($this->text3, $this->text4, $this->_fieldList4);
        }
        foreach ($wheres as $key => $where) {
            if ($where == parent::NO_MATCH_SQL) {
                return parent::NO_MATCH_SQL;
            } elseif ($where == parent::MATCH_ALL_SQL) {
                unset($wheres[$key]);
            }
        }

        if ($wheres) {
            return '(' . join(' AND ', $wheres) . ')';
        } else {
            return parent::MATCH_ALL_SQL;
        }
    }

    protected function getSqlWhereSingle(string|null $field, string|null $searchTxt, array|null $fieldList): string
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

    protected function loadFieldList(string|null $field, string|null $searchTxt): array|null
    {
        $result = null;

        if ($field && $searchTxt) {
            if (isset($this->_lookupTables[$field])) {
                $table = $this->_lookupTables[$field]['tableName'];
                $id    = $this->_lookupTables[$field]['tableId'];
                $like  = $this->_lookupTables[$field]['tableLikeFilter'];

                $sql    = "SELECT $id, $id FROM $table WHERE $like ORDER BY $id";
                $result = $this->resultFetcher->fetchPairs(sprintf($sql, addslashes($searchTxt)));
            }
        }

        return $result;
    }

    /**
     * Check a filter for a match
     *
     * @param Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(Appointment $appointment): bool
    {
        $result1 = $this->matchSingle(
                $this->text1,
                $this->text2,
                $this->_fieldList2,
                $appointment);

        $result2 = $this->matchSingle(
                $this->text3,
                $this->text4,
                $this->_fieldList4,
                $appointment);

        return $result1 && $result2;
    }

    /**
     * Check a filter for a match
     *
     * @param string|null $field
     * @param string|null $searchTxt
     * @param array|bool|null $fieldList
     * @param Appointment $appointment
     * @return boolean
     */
    protected function matchSingle(string|null $field, string|null $searchTxt, array|bool|null $fieldList, Appointment $appointment): bool
    {
        $result = true;

        if ($field && $searchTxt) {
            $value = $this->getAppointmentFieldValue($appointment, $field);
            if (isset($this->_lookupTables[$field])) {
                if (is_null($fieldList) || $fieldList === false || empty($fieldList)) {
                    $result = false;
                }
            } else {
                $regex = '/' . str_replace(array('%', '_'), array('.*', '.{1,1}'), $searchTxt) . '/i';

                if (! (bool) preg_match($regex, $value)) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}
