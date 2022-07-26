<?php

/**
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task;

/**
 * Abstract class for easier implementation of the \Gems_Task for usage with
 * \Gems\Task\TaskRunnerBatch providing some convenience methods to loading and
 * translation.
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @deprecated since version 1.6.2 Moved to \MUtil\Task\TaskAbstract (that uses $this->batch, not $this->_batch)
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
abstract class TaskAbstract extends \MUtil\Registry\TargetAbstract implements \MUtil\Task\TaskInterface
{
    /**
     * @var \MUtil\Task\TaskBatch
     */
    protected $_batch;

    /**
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * @var \Zend_Translate_Adapter
     */
    public $translate;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute()
    {

    }

    /**
     * Return true when the task has finished.
     *
     * @return boolean
     */
    public function isFinished()
    {
        return true;
    }

    /**
     * Sets the batch this task belongs to
     *
     * This method will be called from the \Gems\Task\TaskRunnerBatch upon execution of the
     * task. It allows the task to communicate with the batch queue.
     *
     * @param \MUtil\Task\TaskBatch $batch
     */
    public function setBatch(\MUtil\Task\TaskBatch $batch)
    {
        $this->_batch = $batch;
    }
}