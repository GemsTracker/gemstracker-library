<?php

/**
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task;

use MUtil\Task\TaskBatch;

/**
 * Handles running tasks independent on the kind of task
 *
 * Continues on the \MUtil\Batch\BatchAbstract, exposing some methods to allow the task
 * to interact with the batch queue.
 *
 * Tasks added to the queue should be loadable via \Gems\Loader and implement the \MUtil\Task\TaskInterface
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class TaskRunnerBatch extends TaskBatch
{

    /**
     * The number of bytes to pad during push communication in Kilobytes.
     *
     * This is needed as many servers need extra output passing to avoid buffering.
     *
     * Also this allows you to keep the server buffer high while using this JsPush.
     *
     * @var int
     */
    public int $extraPushPaddingKb = 5;

    /**
     * The number of bytes to pad for the first push communication in Kilobytes. If zero
     * $extraPushPaddingKb is used.
     *
     * This is needed as many servers need extra output passing to avoid buffering.
     *
     * Also this allows you to keep the server buffer high while using this JsPush.
     *
     * @var int
     */
    public int $initialPushPaddingKb = 10;
}
