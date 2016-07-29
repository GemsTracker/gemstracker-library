<?php
/**
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Schedules creation of new tables
 *
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_Db_CreateNewTables extends \Gems_Task_TaskAbstract
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

    public function execute()
    {
        //Now create all new tables
        $todo    = $this->dbaModel->load(array('state'=>  \Gems_Model_DbaModel::STATE_DEFINED));

        foreach($todo as $tableData) {
            $this->_batch->addToCounter('NewTableCount');
            unset($tableData['db']);
            $this->_batch->setTask('Db_CreateNewTable', 'create-tbl-' . $tableData['name'], $tableData);
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