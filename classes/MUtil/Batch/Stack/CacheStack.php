<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Batch
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SessionStack.php$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Batch
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Batch_Stack_CacheStack extends MUtil_Batch_Stack_StackAbstract
{
    /**
     *
     * @var Zend_Cache_Core
     */
    private $_cache;

    /**
     *
     * @var string
     */
    private $_cacheId;

    /**
     *
     * @var array
     */
    private $_commands;

    /**
     *
     * @param string $id A unique name identifying the batch
     */
    public function __construct($id, Zend_Cache_Core $cache)
    {
        $this->_cacheId  = 'batch_' . session_id() . '_' . $id;
        $this->_cache    = $cache;
        $this->_commands = $this->_cache->load($this->_cacheId, true);

        if (! $this->_commands) {
            $this->_commands = array();
        }
    }

    /**
     * Save the cache here
     */
    public function __destruct()
    {
        // MUtil_Echo::track(count($this->_commands));
        if ($this->_commands) {
            $this->_cache->save($this->_commands, $this->_cacheId, array('batch', 'sess_' . session_id()), null);
        } else {
            $this->_cache->remove($this->_cacheId);
        }
    }

    /**
     * Add/set the command to the stack
     *
     * @param array $command
     * @param string $id Optional id to repeat double execution
     * @return boolean When true, increment the number of commands, otherwise the command existed
     */
    protected function _addCommand(array $command, $id = null)
    {
        $result = (null === $id) || !isset($this->_commands[$id]);

        if (null === $id) {
            $this->_commands[] = $command;
        } else {
            $this->_commands[$id] = $command;
        }

        return $result;
    }

    /**
     * Get the next command from the stack
     *
     * @return array $command Same as the array set in _addCommand()
     */
    protected function _getNextCommand()
    {
        return array_shift($this->_commands);
    }

    /**
     * Return true when there still exist unexecuted commands
     *
     * @return boolean
     */
    public function hasNext()
    {
        return (boolean) $this->_commands;
    }

    /**
     * Reset the stack
     *
     * @return \MUtil_Batch_Stack_Stackinterface (continuation pattern)
     */
    public function reset()
    {
        $this->_commands = array();

        return $this;
    }
}
