<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
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
class CreateTrackRoundImportTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;
    
    protected $_conditions;
    protected $_fieldCodes;
    protected $_roundOrders;    

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

        $this->_fieldCodes  = isset($import['fieldCodes']) ? $import['fieldCodes'] : array();
        $this->_roundOrders = isset($import['roundOrders']) ? $import['roundOrders'] : array();
        $this->_conditions  = isset($import['importConditions']) ? $import['importConditions'] : array();
        $tracker            = $this->loader->getTracker();
        $trackEngine        = $tracker->getTrackEngine($import['trackId']);
        $model              = $trackEngine->getRoundModel(true, 'create');

        $roundData['gro_id_track']    = $import['trackId'];
        $roundData['gro_id_survey']   = $import['surveyCodes'][$roundData['survey_export_code']];

        $survey = $tracker->getSurvey($roundData['gro_id_survey']);
        if ($survey) {
            $roundData['gro_survey_name'] = $survey->getName();
        } else {
            $roundData['gro_survey_name'] = '';
        }

        $this->handleRelation($roundData);        
        $this->handleValid('valid_after', $roundData, $lineNr);        
        $this->handleValid('valid_for', $roundData, $lineNr);
        $this->handleCondition($roundData);
        
        $roundData = $model->save($roundData);

        $import['rounds'][$lineNr]['gro_id_round'] = $roundData['gro_id_round'];
        $import['roundOrders'][$roundData['gro_id_order']] = $roundData['gro_id_round'];
        $batch->setVariable('import', $import);
    }
    
    /**
     * Handle condition mapping
     * 
     * @param [] $roundData
     */
    protected function handleCondition(&$roundData)
    {
        if (isset($roundData['gro_condition']) && $roundData['gro_condition']) {
            if (isset($this->_conditions[$roundData['gro_condition']]) && $this->_conditions[$roundData['gro_condition']]) {
                $roundData['gro_condition'] = $this->_conditions[$roundData['gro_condition']];
            } else {
                // This should not happen!
                $roundData['gro_condition'] = null;
            }
        }
        
    }
    
    /**
     * Handle the relation mapping
     * 
     * @param [] $roundData
     */
    protected function handleRelation(&$roundData)
    {
        if (isset($roundData['gro_id_relationfield'], $this->_fieldCodes[$roundData['gro_id_relationfield']]) &&
                $roundData['gro_id_relationfield']) {
            if (! (is_integer($roundData['gro_id_relationfield']) && $roundData['gro_id_relationfield'] < 0)) {
                // -1 means the respondent itself, also gro_id_relationfield stores a "bare"
                // field id, not one with a table prefix
                $keys = FieldsDefinition::splitKey($this->_fieldCodes[$roundData['gro_id_relationfield']]);
                if (isset($keys['gtf_id_field'])) {
                    $roundData['gro_id_relationfield'] = $keys['gtf_id_field'];
                }
            }
        }
    }
    
    /**
     * Handle valid for / after fields
     * 
     * @param string $validType valid_for or valid_after
     * @param [] $roundData
     * @param int $lineNr
     */
    protected function handleValid($validType, &$roundData, $lineNr)
    {
        // Just for clarity and searchability we create the full names here
        if ($validType == 'valid_for') {
            $validId     = 'gro_valid_for_id';
            $validSource = 'gro_valid_for_source';
            $validField  = 'gro_valid_for_field';
        } else {
            $validId     = 'gro_valid_after_id';
            $validSource = 'gro_valid_after_source';
            $validField  = 'gro_valid_after_field';
        }
        
        if (isset($roundData[$validType]) && $roundData[$validType]) {
            if (isset($this->_roundOrders[$roundData[$validType]]) && $this->_roundOrders[$roundData[$validType]]) {
                $roundData[$validId] = $this->_roundOrders[$roundData[$validType]];
            } else {
                $this->getBatch()->addTask(
                        'Tracker\\Import\\UpdateRoundValidTask',
                        $lineNr,
                        $roundData['gro_id_order'],
                        $roundData[$validType],
                        $validId
                        );
            }
        }        
        if (isset($roundData[$validSource], $roundData[$validField])) {
            switch ($roundData[$validSource]) {
                case \Gems\Tracker\Engine\StepEngineAbstract::APPOINTMENT_TABLE:
                case \Gems\Tracker\Engine\StepEngineAbstract::RESPONDENT_TRACK_TABLE:
                    if (isset($this->_fieldCodes[$roundData[$validField]])) {
                        $roundData[$validField] = $this->_fieldCodes[$roundData[$validField]];
                    }
            }
        }
    }
}
