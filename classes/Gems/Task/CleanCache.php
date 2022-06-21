<?php
/**
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Cleans the cache during a batch job
 *
 * Normally when performing certain upgrades you need to clean the cache. When you use
 * this task you can schedule this too. Normally using ->setTask('CleanCache', 'clean')
 * will be sufficient as we only need to run the cache cleaning once. for immidiate cache
 * cleaning, for example when the next task depends on it, perform the actions below
 * in your own task.
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_CleanCache extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Gems\Cache\HelperAdapter
     */
    protected $cache;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($text = null)
    {
        if ($this->cache instanceof \Psr\Cache\CacheItemPoolInterface) {
            $this->cache->clear();
            $this->getBatch()->addMessage($this->_('Cache cleaned'));
        }
    }
}
