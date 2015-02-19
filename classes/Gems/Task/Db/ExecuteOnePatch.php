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
 * Execute a certain patch command
 *
 * @package    Gems
 * @subpackage Task_Db
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Task_Db_ExecuteOnePatch extends \Gems_Task_Db_PatchAbstract
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
        $data['gpa_changed']  = new \MUtil_Db_Expr_CurrentTimestamp();

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
            if (\MUtil_String::contains($message, 'Duplicate column name')) {
                $data['gpa_result'] = 'Column exists in table';
                $data['gpa_completed'] = 1;
            } elseif (\MUtil_String::contains($message, "DROP") &&
                    \MUtil_String::contains($message, 'check that column/key exists')) {
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
        // \MUtil_Echo::track($data, $patchId);

        $batch->addToCounter('executed');
        $batch->setMessage('executed', sprintf($this->_('%d patch(es) executed.'), $batch->getCounter('executed')));
    }
}