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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Batch
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class MUtil_Batch_BatchAbstract extends MUtil_Registry_TargetAbstract implements Countable
{
    const PULL = 'Pull';
    const PUSH = 'Push';

    /**
     *
     * @var Zend_Session_Namespace
     */
    private $_session;

    /**
     *
     * @param string $name The name of this batch, defaults to classname
     */
    public function __construct($name = null)
    {
        if (null === $name) {
            $name = get_class($this);
        }

        $this->_initSession($name);
    }

    private function _initSession($name)
    {
        $this->_session = new Zend_Session_Namespace($name);

        if (! isset($this->_session->commands)) {
            $this->_session->commands  = array();
            $this->_session->counters  = array();
            $this->_session->count     = 0;
            $this->_session->processed = 0;
        }
    }

    /**
     * Add an execution step to the command stack.
     *
     * @param string $method Name of a method of this object
     * @param mixed $id A unique id to prevent double adding of something to do
     * @param mixed $param1 Scalar or array with scalars, as many parameters as needed allowed
     * @return MUtil_Batch_BatchAbstract
     */
    protected function addStep($method, $id, $param1 = null)
    {
        $params = array_slice(func_get_args(), 2);

        if (! method_exists($this, $method)) {
            throw new MUtil_Batch_BatchException("Invalid batch method: '$method'.");
        }
        if (! MUtil_Ra::isScalar($params)) {
            throw new MUtil_Batch_BatchException("Non scalar batch parameter for method: '$method'.");
        }

        $command['method']     = $method;
        $command['parameters'] = $params;

        $this->_session->commands[$id] = $command;

        return $this;
    }

    protected function addToCounter($name, $add = 1)
    {
        if (! isset($this->session->counters[$name])) {
            $this->session->counters[$name] = 0;
        }
        $this->session->counters[$name] += $add;

        return $this->session->counters[$name];
    }

	/**
	 * Count the number of commands
     *
	 * @return int The custom count as an integer.
	 */
	public function count()
    {
        return count($this->_session->commands);
    }

    public function getPanel()
    {
        return new MUtil_Html_ProgressPanel('0%');
    }

    public function hasStarted(Zend_Controller_Request_Abstract $request)
    {
        return false;
    }

    /**
     * Return true after commands all have been ran and there was at least one command to run.
     *
     * @return boolean
     */
    public function isFinished()
    {
        return (0 == $this->_session->count()) && ($this->_session->processed > 0);
    }

    /**
     * Return true when at least one command has been loaded.
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->count() || $this->_session->processed;
    }

    public function runAll()
    {
        while ($this->step());

        return $this->_session->processed;
    }

    public function step()
    {
        if (isset($this->_session->commands) && $this->_session->commands) {
            $command = array_shift($this->_session->commands);
            $this->_session->processed++;

            call_user_func_array(array($this, $command['method']), $command['parameters']);
        }

        return count($this->_session->commands) > 0;
    }
}
