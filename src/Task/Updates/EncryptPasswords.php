<?php

/**
 *
 * @package    Gems
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Updates;

/**
 *
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 3-okt-2014 17:21:55
 */
class EncryptPasswords extends \MUtil\Task\TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Project\ProjectSettings
     */
    public $project;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($tableName = '', $idField = '', $passwordField = '')
    {
        $passwords = $this->db->fetchPairs(
                "SELECT $idField, $passwordField FROM $tableName WHERE $passwordField IS NOT NULL"
                );

        if ($passwords) {
            foreach ($passwords as $key => $password) {
                $values[$passwordField] = $this->project->encrypt($this->project->decrypt($password));

                $this->db->update($tableName, $values, "$idField = '$key'");
            }
            $this->getBatch()->addMessage(sprintf($this->_('%d passwords encrypted for table %s.'), count($passwords), $tableName));
        } else {
            $this->getBatch()->addMessage(sprintf($this->_('No passwords found in table %s.'), $tableName));
        }
    }
}
