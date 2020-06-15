<?php

/**
 * Handles moving answers from one survey to another
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
class MoveanswersTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems_Loader
     */
    public $loader;

    protected $_finished = false;

    protected $movedCode = 'moved';

    public $sourceSurveyId;
    public $sourceSurveyName;
    public $targetSurveyId;
    public $targetSurveyName;

    /**
     *
     * @var [] targetfield => sourcefield
     */
    public $targetFields;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($userId = 0)
    {
        $batch = $this->getBatch();
        if ($batch->getCounter('movestarted') === 0) {
            $batch->addToCounter('movestarted');
            $select = $this->loader->getTracker()->getTokenSelect(['total' => new \Zend_Db_Expr('count(*)')])->andReceptionCodes([])->onlyCompleted()->onlySucces()->forSurveyId($this->sourceSurveyId)->getSelect();
            $count = $this->db->fetchOne($select);
            $batch->addStepCount($count - 1);   // For progressbar
            $batch->resetCounter('movesteps');
            $batch->addToCounter('movesteps', $count);
        }
        $this->executeIteration($userId);
    }

    /**
     * Execute a single iteration of the task.
     *
     * @param array $params The parameters to the execute function
     */
    public function executeIteration($userId = 0)
    {
        $tracker = $this->loader->getTracker();
        $select  = $tracker->getTokenSelect()->andReceptionCodes([])->onlyCompleted()->onlySucces()->forSurveyId($this->sourceSurveyId)->getSelect()->limit(1);

        $results = $this->db->fetchAll($select);
        $batch   = $this->getBatch();
        $batch->addToCounter('movesteps', -1);
        if (count($results) == 0) {
            $this->_finished = true;

            return;
        }

        /**
         * The real work:
         *   - load the token
         *   - retrieve answers
         *   - change token to point to new survey
         *   - inject answers (based on question_code mappings
         */
        // Load token and retrieve current answers
        $tokenData        = reset($results);
        $token            = $tracker->getToken($tokenData);
        $answers          = $token->getRawAnswers();

        // Change answer codes
        if (!empty($answers)) {
            $convertedAnswers = [];
            foreach ($this->targetFields as $target => $source) {
                if (!empty($source) && array_key_exists($source, $answers)) {
                    $convertedAnswers[$target] = $answers[$source];
                }
            }

            // Create a new token
            $values = [
                'gto_id_survey'       => $this->targetSurveyId,
                ] + $tokenData;

            unset($values['token_status']);

            $newTokenId = $token->createReplacement(sprintf($this->_('Copied from old survey: %s'), $this->sourceSurveyName), $userId, $values);
            $newToken = $tracker->getToken($newTokenId);
            $newToken->getSurvey()->copyTokenToSource($newToken, ''); // Take no language, so we get the default
            $newToken->setRawAnswers($convertedAnswers);
            
            // Set the completion time, so it will also be set in the answer table
            // When this is skipped LimeSurvey won't see the completed answers
            $newToken->setCompletionTime($token->getCompletionTime(), $userId);
            
            $batch->addToCounter('moved');
            
        } else {
            $batch->addToCounter('notfound');
             
        }

        $token->setReceptionCode($this->movedCode, sprintf($this->_('Copied to new survey: %s'), $this->targetSurveyName), $userId);
    }

    /**
     * Return true when the task has finished.
     *
     * @return boolean
     */
    public function isFinished()
    {
        $batch = $this->getBatch();
        if ($this->_finished) {
            $batch->resetCounter('movestarted');
            $moved    = $batch->getCounter('moved');
            $notFound = $batch->getCounter('notfound');
            
            $this->getBatch()->addMessage(
                    sprintf($this->_('%d \'%s\' survey answers have been copied to \'%s\'.'),
                            $moved,
                            $this->sourceSurveyName,
                            $this->targetSurveyName));
            if ($notFound > 0) {
                $this->getBatch()->addMessage(sprintf($this->_('For %d tokens no answers were found.'), $notFound));
            }
        } else {
            if ($this->getBatch()->getCounter('movesteps') === 0) {
                // Add 1 to the counter to keep going
                $this->getBatch()->addStepCount(1);
            }
        }
        return $this->_finished;
    }

}
