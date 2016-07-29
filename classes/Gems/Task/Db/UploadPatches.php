<?php
/**
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

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
class Gems_Task_Db_UploadPatches extends \Gems_Task_Db_PatchAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute()
    {
        //Now load all patches, and save the resulting changed patches for later (not used yet)
        $changed  = $this->patcher->uploadPatches($this->loader->getVersions()->getBuild());


        if ($changed) {
            $this->getBatch()->addMessage(sprintf($this->_('%d new or changed patch(es).'), $changed));
        }
    }
}