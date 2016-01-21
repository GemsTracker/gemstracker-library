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
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CreateTrackRoundImportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 18, 2016 7:34:00 PM
 */
class CreateTrackRoundImportTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($lineNr = null, $roundData = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (! (isset($import['trackId']) && $import['trackId'])) {
            // Do nothing
            return;
        }

        // Only save when export code is known and set
        if (! isset($roundData['survey_export_code'], $import['surveyCodes'][$roundData['survey_export_code']])) {
            // Do nothing, no survey export code or no known export code
            return;
        }

        if (! $import['surveyCodes'][$roundData['survey_export_code']]) {
            // Export code not set to skip import of round
            return;
        }

        $fieldCodes  = $import['fieldCodes'];
        $roundOrders = isset($import['roundOrders']) ? $import['roundOrders'] : array();
        $tracker     = $this->loader->getTracker();
        $trackEngine = $tracker->getTrackEngine($import['trackId']);
        $model       = $trackEngine->getRoundModel(true, 'create');

        $roundData['gro_id_track']  = $import['trackId'];
        $roundData['gro_id_survey'] = $import['surveyCodes'][$roundData['survey_export_code']];

        if (isset($roundData['valid_after']) && $roundData['valid_after']) {
            if (isset($roundOrders[$roundData['valid_after']]) && $roundOrders[$roundData['valid_after']]) {
                $roundData['gro_valid_after_id'] = $roundOrders[$roundData['valid_after']];
            } else {
                $batch->addTask(
                        'Tracker\\Import\\UpdateRoundValidTask',
                        $lineNr,
                        $roundData['gro_id_order'],
                        $roundData['valid_after'],
                        'gro_valid_after_id'
                        );
            }
        }
        if (isset($roundData['gro_valid_after_source'], $fieldData['gro_valid_after_field'])) {
            switch ($roundData['gro_valid_after_source']) {
                case self::APPOINTMENT_TABLE:
                case self::RESPONDENT_TRACK_TABLE:
                    if (isset($fieldCodes[$fieldData['gro_valid_after_field']])) {
                        $fieldData['gro_valid_after_field'] = $fieldCodes[$fieldData['gro_valid_after_field']];
                    }
            }
        }
        if (isset($roundData['valid_for']) && $roundData['valid_for']) {
            if (isset($roundOrders[$roundData['valid_for']]) && $roundOrders[$roundData['valid_for']]) {
                $roundData['gro_valid_for_id'] = $roundOrders[$roundData['valid_for']];
            } else {
                $batch->addTask(
                        'Tracker\\Import\\UpdateRoundValidTask',
                        $lineNr,
                        $roundData['gro_id_order'],
                        $roundData['valid_for'],
                        'gro_valid_for_id'
                        );
            }
        }
        if (isset($roundData['gro_valid_for_source'], $fieldData['gro_valid_for_field'])) {
            switch ($roundData['gro_valid_for_source']) {
                case self::APPOINTMENT_TABLE:
                case self::RESPONDENT_TRACK_TABLE:
                    if (isset($fieldCodes[$fieldData['gro_valid_for_field']])) {
                        $fieldData['gro_valid_for_field'] = $fieldCodes[$fieldData['gro_valid_for_field']];
                    }
            }
        }
        $roundData = $model->save($roundData);

        $import['rounds'][$lineNr]['gro_id_round'] = $roundData['gro_id_round'];
        $import['roundOrders'][$roundData['gro_id_order']] = $roundData['gro_id_round'];
    }
}
