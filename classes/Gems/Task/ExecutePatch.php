<?php
/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CheckTokenCompletion.php 502 2012-02-20 14:13:20Z mennodekker $
 */

/**
 * Execute a certain patchlevel
 *
 * Cleans the cache when patches where executed
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Task_ExecutePatch extends Gems_Task_TaskAbstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var GemsEscort
     */
    public $escort;

    /**
     *
     * @var Gems_Util_DatabasePatcher
     */
    public $patcher;

    public function execute($patchLevel = null, $ignoreCompleted = true, $ignoreExecuted = false)
    {
        $this->_batch->addMessage(sprintf($this->translate->_('Executing patchlevel %d'), $patchLevel));
        $result = $this->patcher->executePatch($patchLevel, $ignoreCompleted, $ignoreExecuted);
        $this->_batch->addMessage($this->translate->_(sprintf('Executed %s patches', $result)));

        if ($result>0) {
            //Perform a clean cache only when needed
            $this->_batch->setTask('CleanCache', 'cleancache'); //If already scheduled, don't reschedule
        }
    }

    /**
     * Now we have the requests answered, add the DatabasePatcher as it needs the db object
     *
     * @return boolean
     */
    public function checkRegistryRequestsAnswers() {
        $this->escort = GemsEscort::getInstance();
        
        //As an upgrade almost always includes executing db patches, make a DatabasePatcher object available
        $this->patcher = new Gems_Util_DatabasePatcher($this->db, 'patches.sql', $this->escort->getDatabasePaths());

        //Now load all patches, and save the resulting changed patches for later (not used yet)
        $changed  = $this->patcher->uploadPatches($this->loader->getVersions()->getBuild());

        return true;
    }
}