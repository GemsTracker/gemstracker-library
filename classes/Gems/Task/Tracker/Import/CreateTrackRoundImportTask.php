<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CreateTrackRoundImportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

use Gems\Tracker\Engine\FieldsDefinition;

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

        $fieldCodes  = isset($import['fieldCodes'])  ? $import['fieldCodes']  : array();
        $roundOrders = isset($import['roundOrders']) ? $import['roundOrders'] : array();
        $conditions  = isset($import['conditions'])  ? $import['conditions']  : array();
        $tracker     = $this->loader->getTracker();
        $trackEngine = $tracker->getTrackEngine($import['trackId']);
        $model       = $trackEngine->getRoundModel(true, 'create');

        $roundData['gro_id_track']    = $import['trackId'];
        $roundData['gro_id_survey']   = $import['surveyCodes'][$roundData['survey_export_code']];

        $survey = $tracker->getSurvey($roundData['gro_id_survey']);
        if ($survey) {
            $roundData['gro_survey_name'] = $survey->getName();
        } else {
            $roundData['gro_survey_name'] = '';
        }

        if (isset($roundData['gro_id_relationfield'], $fieldCodes[$roundData['gro_id_relationfield']]) &&
                $roundData['gro_id_relationfield']) {
            if (! (is_integer($roundData['gro_id_relationfield']) && $roundData['gro_id_relationfield'] < 0)) {
                // -1 means the respondent itself, also gro_id_relationfield stores a "bare"
                // field id, not one with a table prefix
                $keys = FieldsDefinition::splitKey($fieldCodes[$roundData['gro_id_relationfield']]);
                if (isset($keys['gtf_id_field'])) {
                    $roundData['gro_id_relationfield'] = $keys['gtf_id_field'];
                }
            }
        }
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
        if (isset($roundData['gro_valid_after_source'], $roundData['gro_valid_after_field'])) {
            switch ($roundData['gro_valid_after_source']) {
                case \Gems_Tracker_Engine_StepEngineAbstract::APPOINTMENT_TABLE:
                case \Gems_Tracker_Engine_StepEngineAbstract::RESPONDENT_TRACK_TABLE:
                    if (isset($fieldCodes[$roundData['gro_valid_after_field']])) {
                        $roundData['gro_valid_after_field'] = $fieldCodes[$roundData['gro_valid_after_field']];
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
        if (isset($roundData['gro_valid_for_source'], $roundData['gro_valid_for_field'])) {
            switch ($roundData['gro_valid_for_source']) {
                case \Gems_Tracker_Engine_StepEngineAbstract::APPOINTMENT_TABLE:
                case \Gems_Tracker_Engine_StepEngineAbstract::RESPONDENT_TRACK_TABLE:
                    if (isset($fieldCodes[$roundData['gro_valid_for_field']])) {
                        $roundData['gro_valid_for_field'] = $fieldCodes[$roundData['gro_valid_for_field']];
                    }
            }
        }
        if (isset($roundData['gro_condition']) && $roundData['gro_condition']) {
            if (isset($conditions[$roundData['gro_condition']]) && $conditions[$roundData['gro_condition']]) {
                $roundData['gro_condition'] = $conditions[$conditionId];
            } else {
                // This should not happen!
                $roundData['gro_condition'] = null;
            }
        }
        
        $roundData = $model->save($roundData);

        $import['rounds'][$lineNr]['gro_id_round'] = $roundData['gro_id_round'];
        $import['roundOrders'][$roundData['gro_id_order']] = $roundData['gro_id_round'];
    }
}
