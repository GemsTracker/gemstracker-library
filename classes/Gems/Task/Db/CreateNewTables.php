<?php
/**
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Db;

/**
 * Schedules creation of new tables
 *
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class CreateNewTables extends \Gems\Task\TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems\Model\DbaModel
     */
    public $dbaModel;

    /**
     * @var \Gems\Escort
     */
    public $escort;

    /**
     * @var \Gems\Project\ProjectSettings
     */
    public $project;

    public function execute()
    {
        //Now create all new tables
        $todo    = $this->dbaModel->load(array('state'=>  \Gems\Model\DbaModel::STATE_DEFINED));

        foreach($todo as $tableData) {
            $this->_batch->addToCounter('NewTableCount');
            unset($tableData['db']);
            $this->_batch->setTask('Db\\CreateNewTable', 'create-tbl-' . $tableData['name'], $tableData);
        }
    }

    /**
     * Now we have the requests answered, add the DatabasePatcher as it needs the db object
     *
     * @return boolean
     */
    public function checkRegistryRequestsAnswers()
    {
        $this->escort = \Gems\Escort::getInstance();

        //Load the dbaModel
        $model = new \Gems\Model\DbaModel($this->db, $this->escort->getDatabasePaths());
        if ($this->project->databaseFileEncoding) {
            $model->setFileEncoding($this->project->databaseFileEncoding);
        }
        $this->dbaModel = $model;

        return true;
    }
}