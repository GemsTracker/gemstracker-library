<?php
/**
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

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
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($sourceId = null, $userId = null)
    {
        $batch  = $this->getBatch();
        $source = $this->loader->getTracker()->getSource($sourceId);

        if (is_null($userId)) {
            $userId = $this->loader->getCurrentUser()->getUserId();
        }

        $surveyCount = $batch->addToCounter('sourceSyncSources');
        $batch->setMessage('sourceSyncSources', sprintf(
                $this->plural('Check %s source', 'Check %s sources', $surveyCount),
                $surveyCount
                ));

        $source->synchronizeSurveyBatch($batch, $userId);
    }
}