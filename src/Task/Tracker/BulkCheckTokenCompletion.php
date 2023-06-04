<?php
/**
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

/**
 * Check token completion in a batch job
 *
 * This task handles the token completion check, but tries to limit the amount of
 * database queries by using a bulk check per survey.
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class BulkCheckTokenCompletion extends \MUtil\Task\TaskAbstract
{
    /**
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($tokenData = null, $userId = null)
    {
        $tracker = $this->loader->getTracker();

        $token  = $tracker->getToken($tokenData[0]);
        $survey = $token->getSurvey();
        $source = $survey->getSource();
        if (method_exists($source, 'getCompletedTokens')) {
            $completed = $source->getCompletedTokens($tokenData, $survey->getSourceSurveyId());
        } else {
            // No bulk method, check all individually
            $completed = $tokenData;
        }

        if ($completed) {
            $batch   = $this->getBatch();
            foreach($completed as $tokenId) {
                $batch->setTask('Tracker\\CheckTokenCompletion', 'tokchk-' . $tokenId, $tokenId, $userId);
            }
        }
    }
}