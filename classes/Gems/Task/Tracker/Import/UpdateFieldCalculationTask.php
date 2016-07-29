<?php

/**
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
