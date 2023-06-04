<?php

/**
 * Handles replacing a survey in all unanswered tokens
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
class TokenReplaceTask extends \MUtil\Task\TaskAbstract
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
            'gto_id_survey' => $this->targetSurveyId,
        ];

        $where = [
            'gto_id_survey = ?' => $this->sourceSurveyId,
            ];
        
        // Only if not started yet
        $where[] = 'gto_start_time is NULL';
        $where[] = 'gto_completion_time is NULL';

        $count = $this->db->update('gems__tokens', $data, $where);
                
        $this->getBatch()->addMessage(
                sprintf($this->plural(
                            'For %d token survey \'%s\'  has been updated to \'%s\'',
                            'For %d tokens survey \'%s\'  has been updated to \'%s\'',
                            $count),
                        $count,
                        $this->sourceSurveyName,
                        $this->targetSurveyName));
    }
    
}