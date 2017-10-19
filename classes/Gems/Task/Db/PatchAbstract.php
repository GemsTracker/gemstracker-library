<?php

/**
 *
 * @package    Gems
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
abstract class Gems_Task_Db_PatchAbstract extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Util_DatabasePatcher
     */
    protected $patcher;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Now we have the requests answered, add the DatabasePatcher as it needs the db object
     *
     * @return boolean
     */
    public function checkRegistryRequestsAnswers()
    {
        $escort = \GemsEscort::getInstance();

        //As an upgrade almost always includes executing db patches, make a DatabasePatcher object available
        $this->patcher = new \Gems_Util_DatabasePatcher($this->db, 'patches.sql', $escort->getDatabasePaths(), $this->project->databaseFileEncoding);

        return parent::checkRegistryRequestsAnswers();
    }
}
