<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

use Gems\Tracker\Engine\FieldsDefinition;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class CreateTrackRoundConditionImportTask extends \MUtil_Task_TaskAbstract
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
    public function execute($lineNr = null, $conditionData = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');
        
        $conditionId = $conditionData['gcon_id'];

        if (! (isset($import['trackId']) && $import['trackId'])) {
            // Do nothing
            return;
        }

        $conditions  = $this->loader->getConditions();
        $model       = $this->loader->getModels()->getConditionModel()->applyEditSettings(true);
        
        // Try to find by classname and options
        $filter = [
            'gcon_class' => $conditionData['gcon_class'],
            'gcon_condition_text1' => $conditionData['gcon_condition_text_1'],
            'gcon_condition_text2' => $conditionData['gcon_condition_text_2'],
            'gcon_condition_text3' => $conditionData['gcon_condition_text_3'],
            'gcon_condition_text4' => $conditionData['gcon_condition_text_4']
                ];        
        $found  = $model->loadFirst($filter);
        
        if (!$found) {
            // Insert
            $conditionData['gcon_id'] = null;
        } else {
            $conditionData['gcon_id'] = $found['gcon_id'];
        }
                
        $conditionSaved = $model->save($conditionData);

        $import['conditions'][$conditionData['gcon_id']] = $conditionSaved['gcon_id'];
    }
}
