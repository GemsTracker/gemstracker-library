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
 * See MUtil_Batch_WaitBatch for example usage.
 *
 * A nice future extension would be to separate the storage engine used so we
 * could use e.g. Zend_Queue as an alternative for storing the command stack.
 * However, as this package needs more state info than available in Zend_Queue
 * we would need an extra extension for that.
 *
 * @see MUtil_Batch_WaitBatch
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
    public $autoStart = false;

    /**
     * The number of bytes to pad during push communication in Kilobytes.
     *
     * This is needed as many servers need extra output passing to avoid buffering.
     *
     * Also this allows you to keep the server buffer high while using this JsPush.
     *
     * @var int
     */
    public $extraPushPaddingKb = 0;

    /**
     * The mode to use for the panel: PUSH or PULL
     *
     * @var string
     */
    protected $method = self::PULL;

    /**
     * The minimal time used between send progress reports.
     *
     * This enables quicker processing as multiple steps can be taken in a single
     * run(), without the run taking too long to answer.
     *
     * Set to 0 to report back on each step.
     *
     * @var int
     */
    public $minimalStepDurationMs = 1000;

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
        if (! isset($this->_session->counters[$name])) {
            $this->_session->counters[$name] = 0;
        }
        $this->_session->counters[$name] += $add;

        return $this->_session->counters[$name];
    }

    /**
     * The number of commands in this batch (both processed
     * and unprocessed).
     *
     * @return int
     */
    public function count()
    {
        return count($this->_session->commands) + $this->_session->processed;
    }

    /**
     * Return the value of a named counter
     *
     * @param string $name
     * @return integer
     */
    public function getCounter($name)
    {
        MUtil_Echo::track($this->_session->counters);
        if (isset($this->_session->counters[$name])) {
            return $this->_session->counters[$name];
        }

        return 0;
    }

    /**
     * Returns the prefix used for the function names for the PUSH method to avoid naming clashes.
     *
     * Set automatically to get_class($this) . '_' $this->_id . '_', use different name
     * in case of name clashes.
     *
     * @see setFunctionPrefix()
     *
     * @return string
     */
    protected function getFunctionPrefix()
    {
        if (! $this->_functionPrefix) {
            $this->setFunctionPrefix(get_class($this) . '_' . $this->_id . '_');
        }

        return (string) $this->_functionPrefix;
    }

    /**
     * String of messages from the batch
     *
     * Do not forget to reset() the batch if you're done with it after
     * displaying the report.
     *
     * @param boolean $reset When true the batch is reset afterwards
     * @return array
     */
    public function getMessages($reset = false)
    {
        $messages = $this->_session->messages;

        if ($reset) {
            $this->reset();
        }

        return $messages;
    }

    /**
     * Return a progress panel object, set up to be used by
     * this batch.
     *
     * @param Zend_View_Abstract $view
     * @param mixed $arg_array MUtil_Ra::args() arguments to populate progress bar with
     * @return MUtil_Html_ProgressPanel
     */
    public function getPanel(Zend_View_Abstract $view, $arg_array = null)
    {
        $args = func_get_args();

        ZendX_JQuery::enableView($view);

        $urlFinish = $view->url(array($this->progressParameterName => $this->progressParameterReportValue));
        $urlRun    = $view->url(array($this->progressParameterName => $this->progressParameterRunValue));

        $panel = new MUtil_Html_ProgressPanel($args);
        $panel->id = $this->_id;

        $js = new MUtil_Html_Code_JavaScript(dirname(__FILE__) . '/Batch' . $this->method . '.js');
        $js->setInHeader(false);
        // Set the fields, in case they where not set earlier
        $js->setDefault('__AUTOSTART__', $this->autoStart ? 'true' : 'false');
        $js->setDefault('{PANEL_ID}', '#' . $this->_id);
        $js->setDefault('{TEXT_ID}', $panel->getDefaultChildTag() . '.' . $panel->progressTextClass);
        $js->setDefault('{URL_FINISH}', addcslashes($urlFinish, "/"));
        $js->setDefault('{URL_START_RUN}', addcslashes($urlRun, "/"));
        $js->setDefault('FUNCTION_PREFIX_', $this->getFunctionPrefix());

        $panel->append($js);

        return $panel;
    }

    /**
     * The Zend ProgressBar handles the communication through
     * an adapter interface.
     *
     * @return Zend_ProgressBar
     */
    public function getProgressBar()
    {
        if (! $this->progressBar instanceof Zend_ProgressBar) {
            $this->setProgressBar(new Zend_ProgressBar($this->getProgressBarAdapter(), 0, 100, $this->_session->getNamespace() . '_pb'));
        }
        return $this->progressBar;
    }

    /**
     * The communication adapter for the ProgressBar.
     *
     * @return Zend_ProgressBar_Adapter
     */
    public function getProgressBarAdapter()
    {
        // Does the current adapter accord with the method?
        if ($this->progressBarAdapter instanceof Zend_ProgressBar_Adapter) {
            if ($this->isPull()) {
                if (! $this->progressBarAdapter instanceof Zend_ProgressBar_Adapter_JsPull) {
                    $this->progressBarAdapter = null;
                }
            } else {
                if (! $this->progressBarAdapter instanceof Zend_ProgressBar_Adapter_JsPush) {
                    $this->progressBarAdapter = null;
                }
            }
        } else {
            $this->progressBarAdapter = null;
        }

        // Create when needed
        if ($this->progressBarAdapter === null) {
            if ($this->isPull()) {
                $this->setProgressBarAdapter(new Zend_ProgressBar_Adapter_JsPull());
            } else {
                $this->setProgressBarAdapter(new MUtil_ProgressBar_Adapter_JsPush());
            }
        }

        // Check for extra padding
        if ($this->progressBarAdapter instanceof MUtil_ProgressBar_Adapter_JsPush) {
            $this->progressBarAdapter->extraPaddingKb = $this->extraPushPaddingKb;
        }

        return $this->progressBarAdapter;
    }

    /**
     * Get the current progress percentage
     *
     * @return float
     */
    public function getProgressPercentage()
    {
        return round($this->_session->processed / (count($this->_session->commands) + $this->_session->processed) * 100, 2);
    }

    /**
     * Returns a button that can be clicked to restart the progress bar.
     *
     * @param mixed $arg_array MUtil_Ra::args() arguments to populate link with
     * @return MUtil_Html_HtmlElement
     */
    public function getRestartButton($args_array = 'Restart')
    {
        $args = MUtil_Ra::args(func_get_args());
        $args['onclick'] = new MUtil_Html_OnClickArrayAttribute(
            new MUtil_Html_Raw('if (! this.disabled) {location.href = "'),
            new MUtil_Html_HrefArrayAttribute(array($this->progressParameterName => null)),
            new MUtil_Html_Raw('";} this.disabled = true; event.cancelBubble=true;'));

        return new MUtil_Html_HtmlElement('button', $args);
    }

    /**
     * Returns a link that can be clicked to restart the progress bar.
     *
     * @param mixed $arg_array MUtil_Ra::args() arguments to populate link with
     * @return MUtil_Html_AElement
     */
    public function getRestartLink($args_array = 'Restart')
    {
        $args = MUtil_Ra::args(func_get_args());
        $args['href'] = array($this->progressParameterName => null);

        return new MUtil_Html_AElement($args);
    }

    /**
     * Returns a button that can be clicked to start the progress bar.
     *
     * @param mixed $arg_array MUtil_Ra::args() arguments to populate link with
     * @return MUtil_Html_HtmlElement
     */
    public function getStartButton($args_array = 'Start')
    {
        $args = MUtil_Ra::args(func_get_args());
        $args['onclick'] = 'if (! this.disabled) {' . $this->getFunctionPrefix() . 'Start();} this.disabled = true; event.cancelBubble=true;';

        return new MUtil_Html_HtmlElement('button', $args);
    }

    /**
     * Return true after commands all have been ran.
     *
     * @return boolean
     */
    public function isFinished()
    {
        return $this->_session->finished;
    }

    /**
     * Return true when at least one command has been loaded.
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->_session->commands || $this->_session->processed;
    }

    /**
     * Does the batch use the PULL method for communication.
     *
     * @return boolean
     */
    public function isPull()
    {
        return $this->method === self::PULL;
    }

    /**
     * Does the batch use the PUSH method for communication.
     *
     * @return boolean
     */
    public function isPush()
    {
        return $this->method === self::PUSH;
    }

    /**
     * Reset and empty the session storage
     *
     * @return MUtil_Batch_BatchAbstract (continuation pattern)
     */
    public function reset()
    {
        $this->_session->commands  = array();
        $this->_session->counters  = array();
        $this->_session->count     = 0;
        $this->_session->finished  = false;
        $this->_session->messages  = array();
        $this->_session->processed = 0;

        return $this;
    }

    /**
     * Run as much code as possible, but do report back.
     *
     * Returns true if any output was communicated, i.e. the "normal"
     * page should not be displayed.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return boolean
     */
    public function run(Zend_Controller_Request_Abstract $request)
    {
        // Is there something to run?
        if ($this->isFinished() || (! $this->isLoaded())) {
            return false;
        }

        // Check for run url
        if ($request->getParam($this->progressParameterName) === $this->progressParameterRunValue) {
            $bar = $this->getProgressBar();

            $isPull    = $this->isPull();
            $reportRun = microtime(true) + ($this->minimalStepDurationMs / 1000);
            // error_log('Rep: ' . $reportRun);
            while ($this->step()) {
                // error_log('Cur: ' . microtime(true) . ' report is '. (microtime(true) > $reportRun ? 'true' : 'false'));
                if (microtime(true) > $reportRun) {
                    // Communicate progress
                    $bar->update($this->getProgressPercentage(), end($this->_session->messages));

                    // INFO: When using PULL $bar->update() should exit the program,
                    // but just let us make sure.
                    if ($isPull) {
                        return true;
                    }
                }
            }
            if (! $this->_session->commands) {
                $this->_session->finished  = true;
                $bar->finish();
            }

            // There is progressBar output
            return true;
        } else {
            // No ProgressBar output
            return false;
        }
    }

    /**
     * Run the whole batch at once, without communicating with a progress bar.
     *
     * @return int Number of steps taken
     */
    public function runAll()
    {
        while ($this->step());

        return $this->_session->processed;
    }

    /**
     * Name prefix for PUSH functions.
     *
     * Set automatically to get_class($this) . '_' $this->_id . '_', use different name
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
     * Sets the communication method for progress reporting.
     *
     * @param string $method One of the constants of this object
     * @return MUtil_Batch_BatchAbstract (continuation pattern)
     */
    public function setMethod($method)
    {
        switch ($method) {
            case self::PULL:
            case self::PUSH:
                $this->method = $method;
                return $this;

            default:
                throw new MUtil_Batch_BatchException("Invalid batch usage method '$method'.");
        }
    }

    /**
     * Set the communication method used by this batch to PULL.
     *
     * This is the most stable method as it works independently of
     * server settings. Therefore it is the default method.
     *
     * @return MUtil_Batch_BatchAbstract (continuation pattern)
     */
    public function setMethodPull()
    {
        $this->setMethod(self::PULL);

        return $this;
    }

    /**
     * Set the communication method used by this batch to PUSH.
     *
     * I.e. the start page opens an iFrame, the url of the iFrame calls the
     * batch with the RUN parameter and the process returns JavaScript tags
     * that handle the progress reporting.
     *
     * This is a very fast and resource inexpensive method for batch processing
     * but it is only suitable for short running processes as servers tend to
     * cut off http calls that take more than some fixed period of time to run -
     * even when those processes keep returning data.
     *
     * Another problem with this method is buffering, i.e. the tendency of servers
     * to wait sending data back until a process has been completed or enough data
     * has been send.
     *
     * E.g. on IIS 7 you have to adjust the file %windir%\System32\inetsrv\config\applicationHost.config
     * and add the attribute responseBufferLimit="1024" twice, both to
     * ../handlers/add name="PHP_via_FastCGI" and to ../handlers/add name="CGI-exe".
     *
     * Still the above works only partially, IIS tends to wait longer before sending the
     * first batch of data. The trick is to add extra spaces to the output until the
     * threshold is reached. This is done by specifying the $extraPaddingKb parameter.
     * Just increase it until it works.
     *
     * @param int $extraPaddingKb
     * @return MUtil_Batch_BatchAbstract (continuation pattern)
     */
    public function setMethodPush($extraPaddingKb = null)
    {
        $this->setMethod(self::PUSH);
        if ((null !== $extraPaddingKb) && is_numeric($extraPaddingKb)) {
            $this->extraPushPaddingKb = $extraPaddingKb;
        }

        return $this;
    }

    /**
     * The Zend ProgressBar handles the communication through
     * an adapter interface.
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
     * The communication adapter for the ProgressBar.
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
     * Progress a single step on the command stack
     *
     * @return boolean
     */
    protected function step()
    {
        if (isset($this->_session->commands) && $this->_session->commands) {
            $command = array_shift($this->_session->commands);
            $this->_session->processed++;

            try {
                call_user_func_array(array($this, $command['method']), $command['parameters']);
            } catch (Exception $e) {
                $this->addMessage('ERROR!!!');
                $this->addMessage('While calling:' . $command['method'] . '(', implode(',', MUtil_Ra::flatten($command['parameters'])), ')');
                $this->addMessage($e->getMessage());
            }
            return true;
        } else {
            return false;
        }
    }
}
