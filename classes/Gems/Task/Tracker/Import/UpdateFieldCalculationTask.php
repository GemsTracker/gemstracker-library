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
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: UpdateFieldCalculationTask.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 21, 2016 2:14:29 PM
 */
class UpdateFieldCalculationTask extends \MUtil_Task_TaskAbstract
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
    public function execute($lineNr = null, $fieldId = null, $fieldSub = null, $fieldCalc = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (! (isset($import['trackId']) && $import['trackId'] && $fieldId)) {
            // Do nothing
            return;
        }

        $tracker     = $this->loader->getTracker();
        $trackEngine = $tracker->getTrackEngine($import['trackId']);
        $fieldCodes  = $import['fieldCodes'];
        $fieldModel  = $trackEngine->getFieldsMaintenanceModel(true, 'edit');
        $roundOrders = $import['roundOrders'];

        $saveData['gtf_id_field'] = $fieldId;
        $saveData['sub']          = $fieldSub;
        $saveData['gtf_id_track'] = $import['trackId'];
        $calcFields = is_array($fieldCalc) ? $fieldCalc : explode('|', trim($fieldCalc, '|'));

        if (! $calcFields) {
            return;
        }

        foreach ($calcFields as $field) {
            if (isset($fieldCodes[$field]) && $fieldCodes[$field]) {
                $saveData['gtf_calculate_using'][] = $fieldCodes[$field];
            } else {
                // Actually this code currently is PULSE specific
                if (\MUtil_String::startsWith($field, '{r')) {
                    $roundOrder = substr($field, 2, -1);

                    if (isset($roundOrders[$roundOrder]) && $roundOrders[$roundOrder]) {
                        $saveData['gtf_calculate_using'][] = $roundOrders[$roundOrder];
                    } else {
                        $saveData['gtf_calculate_using'][] = $field;
                    }
                }
            }
        }
        \MUtil_Echo::track($saveData, $fieldId);
        $fieldModel->save($saveData);
    }
}
