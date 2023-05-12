<?php
/**
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Db;

/**
 * Execute a certain patchlevel
 *
 * Cleans the cache when patches where executed
 *
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AddPatches extends \Gems\Task\Db\PatchAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param int $patchLevel Only execute patches for this patchlevel
     * @param boolean $ignoreCompleted Set to yes to skip patches that where already completed
     * @param boolean $ignoreExecuted Set to yes to skip patches that where already executed
     *                                (this includes the ones that are executed but not completed)
     */
    public function execute($patchLevel = null, $ignoreCompleted = true, $ignoreExecuted = false)
    {
        $batch = $this->getBatch();

        $batch->addMessage(sprintf($this->translate->_('Adding patchlevel %d'), $patchLevel));
        $this->patcher->uploadPatches($patchLevel);
        $this->patcher->loadPatchBatch($patchLevel, $ignoreCompleted, $ignoreExecuted, $batch);
    }
}