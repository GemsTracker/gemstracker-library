<?php
/**
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Db;

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
class CreateNewTable extends \MUtil\Task\TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Model\DbaModel
     */
    protected $dbaModel;

    /**
     * @var \Gems\Escort
     */
    protected $escort;

    /**
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function execute($tableData = array())
    {
        $batch = $this->getBatch();
        $batch->addToCounter('createTableStep');

        $result = $this->dbaModel->runScript($tableData);
        $result[] = sprintf(
                $this->_('Finished %s %s creation script for object %d of %d'),
                $tableData['name'],
                $this->_(strtolower($tableData['type'])),
                $batch->getCounter('createTableStep'),
                $batch->getCounter('NewTableCount')
                );

        if (count($result)>0) {
            foreach ($result as $result)
            {
                $batch->addMessage($result);
            }
            //Perform a clean cache only when needed
            $batch->setTask('CleanCache', 'cleancache'); //If already scheduled, don't reschedule
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