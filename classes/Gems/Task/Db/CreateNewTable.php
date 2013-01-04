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
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Create a single new table
 *
 * Cleans the cache when a new tables was created
 *
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_Db_CreateNewTable extends Gems_Task_TaskAbstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var Gems_Model_DbaModel
     */
    public $dbaModel;

    /**
     * @var GemsEscort
     */
    public $escort;

    /**
     * @var Gems_Project_ProjectSettings
     */
    public $project;

    public function execute($tableData = array())
    {
        $this->_batch->addToCounter('createTableStep');

        $result = $this->dbaModel->runScript($tableData);
        $result[] = sprintf($this->translate->_('Finished %s creation script for object %d of %d'), $this->translate->_(strtolower($tableData['type'])), $this->_batch->getCounter('createTableStep'), $this->_batch->getCounter('NewTableCount')) . '<br/>';

        if (count($result)>0) {
            foreach ($result as $result)
            {
                $this->_batch->addMessage($result);
            }
            //Perform a clean cache only when needed
            $this->_batch->setTask('CleanCache', 'cleancache'); //If already scheduled, don't reschedule
        }
    }

    /**
     * Now we have the requests answered, add the DatabasePatcher as it needs the db object
     *
     * @return boolean
     */
    public function checkRegistryRequestsAnswers()
    {
        $this->escort = GemsEscort::getInstance();

        //Load the dbaModel
        $paths = $this->escort->getDatabasePaths();
        $model = new Gems_Model_DbaModel($this->db, array_values($paths));
        $model->setLocations(array_keys($paths));
        if ($this->project->databaseFileEncoding) {
            $model->setFileEncoding($this->project->databaseFileEncoding);
        }
        $this->dbaModel = $model;

        return true;
    }
}