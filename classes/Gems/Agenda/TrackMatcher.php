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
 * @version    $Id: TrackMatcher.php $
 */

namespace Gems\Agenda;

use Gems\Agenda\AppointmentFilterInterface;

/**
 *
 *
 * @package    Agenda
 * @subpackage TrackMatcher
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 18-okt-2014 12:46:37
 */
class TrackMatcher extends \MUtil_Translate_TranslateableAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @param \Gems_Agenda_Appointment $appointment
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     */
    public function processFilter(\Gems_Agenda_Appointment $appointment, AppointmentFilterInterface $filter)
    {
        \MUtil_Echo::track($filter->getName());
        $select = $this->db->select();
        $select->from('gems__respondent2track2appointment')
                ->joinInner('gems__respondent2track', 'gr2t2a_id_respondent_track = gr2t_id_respondent_track')
                ->where('gr2t_end_date IS NULL OR gr2t_end_date > CURRENT_TIMESTAMP')
                ->where('gr2t2a_id_app_field = ?', $filter->getTrackAppointmentFieldId())
                ->where('gr2t_id_user = ?')
                ->where('gr2t_id_organization = ?')
                ->order(new \Zend_Db_Expr());


    }

    /**
     *
     * @param \Gems_Agenda_Appointment $appointment
     * @param array $filters of AppointmentFilterInterface
     */
    public function processFilters(\Gems_Agenda_Appointment $appointment, array $filters)
    {

        \MUtil_Echo::track(count($filters));
        foreach ($filters as $filter) {
            $this->processFilter($appointment, $filter);
        }
    }
}
