<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * The Batch package is for the sequential processing of commands which may
 * take to long to execute in a single request.
 *
 * To use this package just sub class this class, write methods that run
 * the code to be execute and then write the code that add's those functions
 * to be executed.
 *
 * Each step in the sequence consists of a method name of the child object
 * and any number of scalar variables and array's containing scalar variables.
 *
 * A nice future extension would be to separate the storage engine used so we
 * could use e.g. Zend_Queue as an alternative for storing the command stack.
 * However, as this package needs more state info than available in Zend_Queue
 * we would need an extra extension for that.
 *
 * @package    MUtil
 * @subpackage Batch
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class MUtil_Batch_BatchAbstract extends MUtil_Registry_TargetAbstract implements Countable
{
    const PULL = 'Pull';
    const PUSH = 'Push';

    /**
     * Name to prefix the functions, to avoid naming clashes.
     *
     * @var string Default is the classname with an extra underscore
     */
    protected $_functionPrefix;

    /**
     * An id unique for this session.
     *
     * @var string Unique id
     */
    private $_id;

    /**
     * Stack to keep existing id's.
     *
     * @var array
     */
    private static $_idStack = array();

    /**
     *
     * @var Zend_Session_Namespace
     */
    private $_session;

    /**
     * When true the progressbar should start immediately. When false the user has to perform an action.
     *
     * @var boolean
     */
    public $autoStart = true;

    /**
     * The mode to use for the panel: push or pull
     *
     * @var string
     */
    public $method = self::PULL;

    /**
     *
     * @var Zend_ProgressBar
     */
    protected $progressBar;

    /**
     *
     * @var Zend_ProgressBar_Adapter
     */
    protected $progressBarAdapter;

    /**
     * The name of the parameter used for progress panel signals
     *
     * @var string
     */
    public $progressParameterName = 'progress';

    /**
     * The value required for the progress panel to report and reset
     *
     * @var string
     */
    public $progressParameterReportValue = 'report';

    /**
     * The value required for the progress panel to start running
     *
     * @var string
     */
    public $progressParameterRunValue = 'run';

    /**
     *
     * @param string $id A unique name identifying this batch
     */
    public function __construct($id = null)
    {
        if (null === $id) {
            $id = 'batchId' . (1 + count(self::$_idStack));
        }
        foreach (self::$_idStack as $existingId) {
            if ($existingId == $id) {
                throw new MUtil_Batch_BatchException("Duplicate batch id created: $id");
            }
        }
        self::$_idStack[] = $id;
        $this->_id = $id;

        $this->_initSession($id);
    }

    /**
     * Checks parameters and returns a command array.
     *
     * @param string $method
     * @param array $params
     * @return array A command array
     */
    private function _checkParams($method, array $params)
    {
        if (! method_exists($this, $method)) {
            throw new MUtil_Batch_BatchException("Invalid batch method: '$method'.");
        }
        if (! MUtil_Ra::isScalar($params)) {
            throw new MUtil_Batch_BatchException("Non scalar batch parameter for method: '$method'.");
        }

        $command['method']     = $method;
        $command['parameters'] = $params;

        return $command;
    }

    /**
     * Initialize persistent storage
     *
     * @param string $name The id of this batch
     */
    private function _initSession($id)
    {
        $this->_session = new Zend_Session_Namespace(get_class($this) . '_' . $id);

        if (! isset($this->_session->commands)) {
            $this->reset();
        }
    }

    /**
     * Add an execution step to the command stack.
     *
     * @param string $method Name of a method of this object
     * @param mixed $param1 Scalar or array with scalars, as many parameters as needed allowed
     * @return MUtil_Batch_BatchAbstract
     */
    protected function addStep($method, $param1 = null)
    {
        $params = array_slice(func_get_args(), 1);

        $this->_session->commands[] = $this->_checkParams($method, $params);

        return $this;
    }

    /**
     * Add a message to the message stack.
     *
     * @param string $text A message to the user
     * @return MUtil_Batch_BatchAbstract (continuation pattern)
     */
    protected function addMessage($text)
    {
        $this->_session->messages[] = $text;

        return $this;
    }

    /**
     * Increment a named counter
     *
     * @param string $name
     * @param integer $add
     * @return integer
     */
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

    /**
     * Returns the prefix used for the function names to avoid naming clashes.
     *
     * @return string
     */
    public function getFunctionPrefix()
    {
        if (! $this->_functionPrefix) {
            $this->setFunctionPrefix(__CLASS__ . '_' . $this->_id . '_');
        }

        return (string) $this->_functionPrefix;
    }

    /**
     * Return
     * @return MUtil_Html_ProgressPanel
     */
    public function getPanel(Zend_View_Abstract $view)
    {
        ZendX_JQuery::enableView($view);

        if ($this->isFinished()) {
            $content = '100%';
        } else {
            $content = '100%';
        }

        $panel = new MUtil_Html_ProgressPanel('0%');

        $panel->id = $this->_id;
        $panel->method = $this->method;

        $js = new MUtil_Html_Code_JavaScript(dirname(__FILE__) . '/Batch' . $this->method . '.js');
        $js->setInHeader(false);
        // Set the fields, in case they where not set earlier
        $js->setDefault('__AUTOSTART__', $this->autoStart ? 'true' : 'false');
        $js->setDefault('{ID}', $this->_id);
        $js->setDefault('{TEXT_TAG}', $panel->getDefaultChildTag());
        $js->setDefault('{TEXT_CLASS}', $panel->progressTextClass);
        $js->setDefault('{URL_FINISH}', addcslashes($view->url(array($this->progressParameterName => $this->progressParameterReportValue)), "/"));
        $js->setDefault('{URL_START}', addcslashes($view->url(array($this->progressParameterName => $this->progressParameterRunValue)), "/"));
        $js->setDefault('FUNCTION_PREFIX_', $this->getFunctionPrefix());

        $panel->append($js);

        return $panel;
    }

    /**
     *
     * @return Zend_ProgressBar
     */
    public function getProgressBar()
    {
        if (! $this->progressBar instanceof Zend_ProgressBar) {
            $this->setProgressBar(new Zend_ProgressBar($this->getProgressBarAdapter(), 0, 100));
        }
        return $this->progressBar;
    }

    /**
     *
     * @return Zend_ProgressBar_Adapter
     */
    public function getProgressBarAdapter()
    {
        if (! $this->progressBarAdapter instanceof Zend_ProgressBar_Adapter) {
            if ($this->method == self::PULL) {
                $this->setProgressBarAdapter(new Zend_ProgressBar_Adapter_JsPull());
            } else {
                $this->setProgressBarAdapter(new MUtil_ProgressBar_Adapter_JsPush());
                $this->progressBarAdapter->extraPaddingKb = 3;
            }
        }

        return $this->progressBarAdapter;
    }

    public function getReport()
    {
        $messages = $this->_session->messages;

        return $messages;
    }

    /**
     * Returns true when the parameters passed mean the program has started.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return boolean
     */
    public function hasStarted(Zend_Controller_Request_Abstract $request)
    {
        return $request->getParam($this->progressParameterName) === $this->progressParameterRunValue;
    }

    /**
     * Return true after commands all have been ran and there was at least one command to run.
     *
     * @return boolean
     */
    public function isFinished()
    {
        return (0 == $this->count()) && ($this->_session->processed > 0);
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

    public function reset()
    {
        $this->_session->commands  = array();
        $this->_session->counters  = array();
        $this->_session->count     = 0;
        $this->_session->messages  = array();
        $this->_session->processed = 0;
    }

    public function runAll()
    {
        while ($this->step());

        return $this->_session->processed;
    }

    /**
     * Name prefix for functions.
     *
     * Set automatically to __CLASS___, use different name
     * in case of name clashes.
     *
     * @param string $prefix
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function setFunctionPrefix($prefix)
    {
        $this->_functionPrefix = $prefix;
        return $this;
    }

    /**
     * Add/set a message on the message stack with a specific id.
     *
     * @param scalar $id
     * @param string $text A message to the user
     * @return MUtil_Batch_BatchAbstract (continuation pattern)
     */
    protected function setMessage($id, $text)
    {
        $this->_session->messages[$id] = $text;

        return $this;
    }

    /**
     *
     * @param Zend_ProgressBar $progressBar
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function setProgressBar(Zend_ProgressBar $progressBar)
    {
        $this->progressBar = $progressBar;
        return $this;
    }

    /**
     *
     * @param Zend_ProgressBar_Adapter_Interface $adapter
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function setProgressBarAdapter(Zend_ProgressBar_Adapter $adapter)
    {
        if ($adapter instanceof Zend_ProgressBar_Adapter_JsPush) {
            $prefix = $this->getFunctionPrefix();

            // Set the fields, in case they where not set earlier
            $adapter->setUpdateMethodName($prefix . 'Update');
            $adapter->setFinishMethodName($prefix . 'Finish');
        }

        $this->progressBarAdapter = $adapter;
        return $this;
    }

    /**
     * Add an execution step to the command stack.
     *
     * @param string $method Name of a method of this object
     * @param mixed $id A unique id to prevent double adding of something to do
     * @param mixed $param1 Scalar or array with scalars, as many parameters as needed allowed
     * @return MUtil_Batch_BatchAbstract
     */
    protected function setStep($method, $id, $param1 = null)
    {
        $params = array_slice(func_get_args(), 2);

        $this->_session->commands[$id] = $this->_checkParams($method, $params);

        return $this;
    }

    /**
     * Returns true when the parameters passed mean the program has started.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return boolean
     */
    public function showReport(Zend_Controller_Request_Abstract $request)
    {
        return $request->getParam($this->progressParameterName) === $this->progressParameterReportValue;
    }

    /**
     * Workhorse function that does all the real work.
     *
     * @return int
     */
    public function step()
    {
        $bar = $this->getProgressBar();

        if (isset($this->_session->commands) && $this->_session->commands) {
            $command = array_shift($this->_session->commands);
            $this->_session->processed++;

            call_user_func_array(array($this, $command['method']), $command['parameters']);

            $percent = round($this->_session->processed / ($this->count() + $this->_session->processed) * 100, 2);

            $bar->update($percent, end($this->_session->messages));
            return true;
        } else {
            $bar->finish();

            return false;
        }
        return count($this->_session->commands) > 0;
    }
}
