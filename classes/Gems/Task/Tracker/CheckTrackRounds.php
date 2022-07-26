<?php

/**
 * @package    Gems
 * @subpackage TaskTracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

/**
 * Check token completion in a batch job
 *
 * This task handles the token completion check, adding tasks to the queue
 * when needed.
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class CheckTrackRounds extends \MUtil\Task\TaskAbstract
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
    public function execute($respTrackData = null, $userId = null)
    {
        $batch     = $this->getBatch();
        $tracker   = $this->loader->getTracker();
        $respTrack = $tracker->getRespondentTrack($respTrackData);
        $engine    = $respTrack->getTrackEngine();

        $engine->checkRoundsFor($respTrack, $userId, $batch);
    }
}