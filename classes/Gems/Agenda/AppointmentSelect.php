<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentSelect.php $
 */

namespace Gems\Agenda;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 5-mrt-2015 11:29:20
 */
class AppointmentSelect extends \MUtil_Registry_TargetAbstract
{
    /**
     *
     * @var \Zend_Db_Select
     */
    protected $_select;

    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The fields returned by the query
     *
     * @var string|array
     */
    protected $fields = "*";

    /**
     *
     * @param string|array $fields Optional select fieldlist
     */
    public function __construct($fields = null)
    {
        if (null !== $fields) {
            $this->fields = $fields;
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->_select = $this->db->select();
        $this->_select->from('gems__appointments', $this->fields);
    }

    /**
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->_select->query()->fetchAll();
    }

    /**
     *
     * @return mixed
     */
    public function fetchOne()
    {
        $this->_select->limit(1);

        return $this->_select->query()->fetchColumn(0);
    }

    /**
     *
     * @return array
     */
    public function fetchRow()
    {
        $this->_select->limit(1);

        return $this->_select->query()->fetch();
    }

    /**
     * For a certain appointment filter
     *
     * Add's the filter sql where and remembers the filter
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function forFilter(AppointmentFilterInterface $filter)
    {
        $this->_select->where($filter->getSqlWhere());

        return $this;
    }

    /**
     * For a certain appointment filter
     *
     * @param int $filterId
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function forFilterId($filterId)
    {
        $filter = $this->agenda->getFilter($filterId);
        if ($filter) {
            return $this->forFilter($filter);
        }

        return $this;
    }

    /**
     *
     * @param \MUtil_Date $from Optional date after which the appointment must occur
     * @param \MUtil_Date $until Optional date before which the appointment must occur
     * @param boolean $sortAsc Retrieve first or last appointment first
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function forPeriod(\MUtil_Date $from = null, \MUtil_Date $until = null, $sortAsc = true)
    {
        if ($from) {
            $this->_select->where("gap_admission_time >= ?", $from->toString('yyyy-MM-dd HH:mm:ss'));
        }
        if ($until) {
            $this->_select->where("gap_admission_time <= ?", $until->toString('yyyy-MM-dd HH:mm:ss'));
        }
        if ($sortAsc) {
            $this->order('gap_admission_time ASC');
        } else {
            $this->order('gap_admission_time DESC');
        }

        return $this;
    }

    /**
     * For a certain respondent / organization
     *
     * @param int $respondentId
     * @param int $organizationId
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function forRespondent($respondentId, $organizationId)
    {
       $this->_select->where('gap_id_user = ?', $respondentId)
               ->where('gap_id_organization = ?', $organizationId);

       return $this;
    }

    /**
     *
     * @param mixed $from Optional date or appointment after which the appointment must occur
     * @param string $oper Comparison operator for the from date: <= < = > >=
     * @return \Gems\Agenda\AppointmentSelect
     * @deprecated since 1.7.1 replace by forPeriod()
     */
    public function fromDate($from = null, $oper = '>=')
    {
        if ($from) {
            if ($from instanceof \Gems_Agenda_Appointment) {
                $from = $from->getAdmissionTime();
            }
            if ($from instanceof \Zend_Date) {
                $from = $from->toString('yyyy-MM-dd HH:mm:ss');
            }
            $this->_select->where("gap_admission_time $oper ?", $from);
        }
        if ('<' === $oper[0]) {
            $this->order('gap_admission_time DESC');
        } else {
            $this->order('gap_admission_time ASC');
        }

        return $this;
    }

    /**
     * Get the constructed select statement
     *
     * @return \Zend_Db_Select
     */
    public function getSelect()
    {
        return $this->_select;
    }

    /**
     * Select only active agenda items
     *
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function onlyActive()
    {
        $this->_select->where('gap_status IN (?)', $this->agenda->getStatusKeysActiveDbQuoted());

        return $this;
    }

    /**
     *
     * @param mixed $spec The column(s) and direction to order by.
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function order($spec)
    {
        $this->_select->order($spec);

        return $this;
    }

    /**
     * Add a filter for the appointment id's currently used in tracks with this track id
     *
     * @param int $trackId The current trrack id
     * @param int $respTrackId The current respondent track id or null for new tracks
     * @param array $previousAppIds array of gap_id_appointment
     */
    public function uniqueForTrackId($trackId, $respTrackId, $previousAppIds)
    {
        if ($previousAppIds) {
            // When unique for all tracks of this type the current track
            // appointment id's should also be excluded.
            $this->uniqueInTrackInstance($previousAppIds);
        }

        $sql = "gap_id_appointment NOT IN
                    (SELECT gr2t2a_id_appointment FROM gems__respondent2track2appointment
                        INNER JOIN gems__respondent2track
                            ON gr2t2a_id_respondent_track = gr2t_id_respondent_track
                        WHERE gr2t2a_id_appointment IS NOT NULL AND
                            gr2t_id_track = $trackId";

        if ($respTrackId) {
            // Exclude all fields of the current respondent track as it is being recalculated
            // and therefore they may have changed.
            //
            // Instead we filter here on $previousAppIds
            $sql .= " AND NOT (gr2t2a_id_respondent_track = $respTrackId)";
        }

        $sql .= ")";

        $this->_select->where($sql);
    }

    /**
     * Add a filter for the appointment id's currently used in this track
     *
     * @param array $previousAppIds array of gap_id_appointment
     */
    public function uniqueInTrackInstance($previousAppIds)
    {
        if ($previousAppIds) {
            // Exclude the current app id's in the track
            $this->_select->where("gap_id_appointment NOT IN (" . implode(", ", $previousAppIds) . ")");
        }
    }
}
