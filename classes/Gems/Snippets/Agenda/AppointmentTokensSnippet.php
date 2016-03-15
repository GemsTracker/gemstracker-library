<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage AppointmentTokensSnippet
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentTokensSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Agenda;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage AppointmentTokensSnippet
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 14, 2016 3:19:57 PM
 */
class AppointmentTokensSnippet extends \Gems_Snippets_RespondentTokenSnippet
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->caption = $this->_("Tokens set by this appointment");
        $this->onEmpty = $this->_("No tokens are set by this appointment");
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        parent::processFilterAndSort($model);

        $appId = $this->request->getParam(\Gems_Model::APPOINTMENT_ID);

        if ($appId) {
            $appKeyPrefix = $this->db->quote(FieldsDefinition::makeKey(FieldMaintenanceModel::APPOINTMENTS_NAME, ''));
            $appSource    = $this->db->quote(\Gems_Tracker_Engine_StepEngineAbstract::APPOINTMENT_TABLE);

            $or[] = $this->db->quoteInto(
                    "gro_valid_after_source = $appSource AND
                        (gto_id_respondent_track, gro_valid_after_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT($appKeyPrefix, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = ?)",
                    $appId
                    );
            $or[] = $this->db->quoteInto(
                    "gro_valid_for_source = $appSource AND
                        (gto_id_respondent_track, gro_valid_for_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT($appKeyPrefix, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = ?)",
                    $appId
                    );
        }

        $model->addFilter(array('(' . implode(') OR (', $or) . ')'));
    }
}
