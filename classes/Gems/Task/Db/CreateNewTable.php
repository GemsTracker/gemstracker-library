<?php
/**
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
class Gems_Task_Db_CreateNewTable extends \Gems_Task_TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems_Model_DbaModel
     */
    public $dbaModel;

    /**
     * @var GemsEscort
     */
    public $escort;

    /**
     * @var \Gems_Project_ProjectSettings
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
        $this->escort = \GemsEscort::getInstance();

        //Load the dbaModel
        $model = new \Gems_Model_DbaModel($this->db, $this->escort->getDatabasePaths());
        if ($this->project->databaseFileEncoding) {
            $model->setFileEncoding($this->project->databaseFileEncoding);
        }
        $this->dbaModel = $model;

        return true;
    }
}