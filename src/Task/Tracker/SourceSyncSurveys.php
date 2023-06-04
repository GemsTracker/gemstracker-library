<?php
/**
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

use Gems\Tracker;
use Gems\User\User;

/**
 * Executes the syncSurveys method for a given sourceId
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class SourceSyncSurveys extends \MUtil\Task\TaskAbstract
{
    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($sourceId = null, $userId = null)
    {
        $batch  = $this->getBatch();
        $source = $this->tracker->getSource($sourceId);

        if (is_null($userId)) {
            $userId = $this->currentUser->getUserId();
        }

        $surveyCount = $batch->addToCounter('sourceSyncSources');
        $batch->setMessage('sourceSyncSources', sprintf(
                $this->plural('Check %s source', 'Check %s sources', $surveyCount),
                $surveyCount
                ));

        $source->synchronizeSurveyBatch($batch, $userId);
    }
}