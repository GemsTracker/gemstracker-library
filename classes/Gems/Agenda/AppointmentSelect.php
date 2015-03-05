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
     * @var \gems\Agenda\AppointmentFilterInterface
     */
    protected $filter;

    /**
     *
     * @var \Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

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

        if ($this->filter instanceof AppointmentFilterInterface) {
            $this->forFilter($filter);
        }
        if ($this->respondentTrack instanceof \Gems_Tracker_RespondentTrack) {
            $this->forRespondentTrack($this->respondentTrack);
        }
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
        $this->sql_select->limit(1);

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
        $this->filter = $filter;

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
     * For a certain respondent track
     *
     * Add's the filter and remembers the respondent track
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function forRespondentTrack(\Gems_Tracker_RespondentTrack $respTrack)
    {
        $this->respondentTrack = $respTrack;

        return $this->forRespondent($respTrack->getRespondentId(), $respTrack->getOrganizationId());
    }

    public function forUniqueness($uniqueness = 0)
    {
        if ($uniqueness) {
            $fieldId     = intval($this->filter->getAppointmentFieldId());
            $respTrackId = intval($this->respTrack->getRespondentTrackId());
            switch ($uniqueness) {
                case 1:
                    $select->where(
                            "gap_id_appointment NOT IN
                                (SELECT gr2t2a_id_appointment FROM gems__respondent2track2appointment
                                    WHERE gr2t2a_id_appointment IS NOT NULL AND
                                        gr2t2a_id_respondent_track = $respTrackId AND
                                        gr2t2a_id_app_field != $fieldId)"
                            );
                    break;
                case 2:
                    $trackId = $this->respTrack->getTrackId();
                    $select->where(
                            "gap_id_appointment NOT IN
                                (SELECT gr2t2a_id_appointment FROM gems__respondent2track2appointment
                                    INNER JOIN gems__respondent2track
                                        ON gr2t2a_id_respondent_track = gr2t_id_respondent_track
                                    WHERE gr2t2a_id_appointment IS NOT NULL AND
                                        gr2t_id_track = $trackId AND
                                        NOT (gr2t2a_id_respondent_track = $respTrackId AND
                                            gr2t2a_id_app_field = $fieldId))"
                            );
                    break;
//                case 3:
//                    $this->_select->where(
//                            "gap_id_appointment NOT IN
//                                (SELECT gr2t2a_id_appointment FROM gems__respondent2track2appointment
//                                    WHERE gr2t2a_id_appointment IS NOT NULL AND
//                                        NOT (gr2t2a_id_respondent_track = $respTrackId AND
//                                            gr2t2a_id_app_field = $fieldId))"
//                            );
//                    break;
                // default:
            }
        }

        return $this;
    }

    /**
     *
     * @param mixed $from Optional date or appointment after which the appointment must occur
     * @param string $oper Comparison operator for the from date: <= < = > >=
     * @return \Gems\Agenda\AppointmentSelect
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
}
