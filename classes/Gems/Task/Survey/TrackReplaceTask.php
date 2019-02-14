<?php

/**
 * Handles replacing a survey in all rounds
 * 
 * @package    Gems
 * @subpackage Task\survey
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Survey;

/**
 *
 * @package    Gems
 * @subpackage Task\Survey
 * @copyright  Copyright (c) 2019, Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class TrackReplaceTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;
    
    public $sourceSurveyId;
    public $sourceSurveyName;
    public $targetSurveyId;    
    public $targetSurveyName;

    public function execute()
    {
        $data = [
            'gro_id_survey' => $this->targetSurveyId,
        ];

        $where = [
            'gro_id_survey = ?' => $this->sourceSurveyId,
        ];

        $count = $this->db->update('gems__rounds', $data, $where);
                
        $this->getBatch()->addMessage(
                sprintf($this->plural(
                            '%d track round has been updated to use \'%s\' instead of \'%s\'',
                            '%d track rounds have been updated to use \'%s\' instead of \'%s\'',
                            $count),
                        $count,
                        $this->targetSurveyName,
                        $this->sourceSurveyName));
    }
    
}