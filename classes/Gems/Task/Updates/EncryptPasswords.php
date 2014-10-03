<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: EncryptPasswords.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 3-okt-2014 17:21:55
 */
class Gems_Task_Updates_EncryptPasswords extends MUtil_Task_TaskAbstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($tableName = '', $idField = '', $passwordField = '', $methodField = '')
    {
        $passwords = $this->db->fetchPairs("SELECT $idField, $passwordField FROM $tableName WHERE $passwordField IS NOT NULL AND $methodField IS NULL");

        if ($passwords) {
            $values[$methodField] = 'default';

            foreach ($passwordField as $key => $password) {
                $values[$passwordField] = $this->project->encrypt($password, $values[$methodField]);

                $this->db->update($tableName, $values, "$idField = '$key'");
            }
            $this->getBatch()->addMessage(sprintf($this->_('%d passwords encrypted for table %s.'), count($passwords), $tableName));
        } else {
            $this->getBatch()->addMessage(sprintf($this->_('No passwords found in table %s.'), $tableName));
        }
    }
}
