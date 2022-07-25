<?php
/**
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Db;

/**
 * Execute a certain patch command
 *
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class ExecuteOnePatch extends \Gems\Task\Db\PatchAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param string $location
     * @param string $sql
     * @param int $completed
     * @param int $patchId
     */
    public function execute($location = null, $sql = null, $completed = null, $patchId = null)
    {
        $batch = $this->getBatch();

        $db = $this->patcher->getPatchDatabase($location);

        $data['gpa_executed'] = 1;
        $data['gpa_changed']  = new \MUtil\Db\Expr\CurrentTimestamp();

        try {
            $stmt = $db->query($sql);
            if ($rows = $stmt->rowCount()) {
                // No translation to avoid conflicting translations
                $data['gpa_result'] = 'OK: ' . $rows . ' changed';
            } else {
                $data['gpa_result'] = 'OK';
            }
            $data['gpa_completed'] = 1;

        } catch (\Zend_Db_Statement_Exception $e) {
            $message = $e->getMessage();

            // Make sure these do not remain uncompleted
            if (\MUtil\StringUtil\StringUtil::contains($message, 'Duplicate column name')) {
                $data['gpa_result'] = 'Column exists in table';
                $data['gpa_completed'] = 1;
            } elseif (\MUtil\StringUtil\StringUtil::contains($message, "DROP") &&
                    \MUtil\StringUtil\StringUtil::contains($message, 'check that column/key exists')) {
                $data['gpa_result'] = 'Column does not exists in table';
                $data['gpa_completed'] = 1;
            } else {
                $data['gpa_result'] = substr($message, 0, 254);
                $data['gpa_completed'] = $completed ? $completed : 0;
            }
            $batch->addMessage($data['gpa_result']);
        }

        // $this->db, not the database the patch was executed on
        $this->db->update('gems__patches', $data, $this->db->quoteInto('gpa_id_patch = ?', $patchId));
        // \MUtil\EchoOut\EchoOut::track($data, $patchId);

        $batch->addToCounter('executed');
        $batch->setMessage('executed', sprintf($this->_('%d patch(es) executed.'), $batch->getCounter('executed')));
    }
}