<?php

/**
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Handles running tasks independent on the kind of task
 *
 * Continues on the \MUtil_Batch_BatchAbstract, exposing some methods to allow the task
 * to interact with the batch queue.
 *
 * Tasks added to the queue should be loadable via \Gems_Loader and implement the \MUtil_Task_TaskInterface
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_TaskRunnerBatch extends \MUtil_Task_TaskBatch
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
    public $extraPushPaddingKb = 5;

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
    public $initialPushPaddingKb = 10;

    /**
     *
     * @var array containing the classPrefix => classPath for task laoder
     */
    protected $taskLoaderDirs = array(
        'Gems_Task'  => 'Gems/Task',
        'MUtil_Task' => 'MUtil/Task',
        );
}