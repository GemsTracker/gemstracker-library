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
 * @package    MUtil
 * @subpackage Application
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    MUtil
 * @subpackage Application
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.0
 */
class MUtil_Application_EscortPlugin extends \Zend_Controller_Plugin_Abstract
{
    /**
     *
     * @var \MUtil_Application_Escort
     */
    private $_escort;

    /**
     *
     * @param \MUtil_Application_Escort $escort
     */
    public function __construct(\MUtil_Application_Escort $escort)
    {
        $this->setEscort($escort);
    }

    /**
     * Called before \Zend_Controller_Front exits its dispatch loop.
     *
     * @return void
     */
    public function dispatchLoopShutdown()
    {
        $this->_escort->dispatchLoopShutdown();
    }

    /**
     * Called before \Zend_Controller_Front enters its dispatch loop.
     *
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request)
    {
        $this->_escort->dispatchLoopStartup($request);
    }

    /**
     *
     * @return \MUtil_Application_Escort
     */
    public function getEscort()
    {
        return $this->_escort;
    }

    /**
     * Called after an action is dispatched by \Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the
     * request and resetting its dispatched flag (via
     * {@link \Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * a new action may be specified for dispatching.
     *
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(\Zend_Controller_Request_Abstract $request)
    {
        $this->_escort->postDispatch($request);
    }

    /**
     * Called before an action is dispatched by \Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior.  By altering the
     * request and resetting its dispatched flag (via
     * {@link \Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * the current action may be skipped.
     *
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
        $this->_escort->preDispatch($request);
    }

    /**
     * Register escort as a frontcontroller plugin.
     *
     * @param  \MUtil_Application_Escort $escort
     * @param  int $stackIndex Optional; stack index for plugin
     * @return self
     */
    public static function register(\MUtil_Application_Escort $escort, $stackIndex = null)
    {
        $plugin = new self($escort);
        $front = \Zend_Controller_Front::getInstance();

        $front->registerPlugin($plugin, $stackIndex);

        return $plugin;
    }

    /**
     * Called after \Zend_Controller_Router exits.
     *
     * Called after \Zend_Controller_Front exits from the router.
     *
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeShutdown(\Zend_Controller_Request_Abstract $request)
    {
        $this->_escort->routeShutdown($request);
    }

    /**
     * Called before \Zend_Controller_Front begins evaluating the
     * request against its routes.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request)
    {
        $this->_escort->routeStartup($request);
    }

    /**
     *
     * @param \MUtil_Application_Escort $escort
     * @return \MUtil_Application_EscortPlugin
     */
    public function setEscort(\MUtil_Application_Escort $escort)
    {
        $this->_escort = $escort;

        return $this;
    }

    /**
     * Set request object, both for this and the boostrap class.
     *
     * If the bootstrap class has a setRequest method it is set.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return \Zend_Controller_Plugin_Abstract
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->_escort->setRequest($request);

        return parent::setRequest($request);
    }

    /**
     * Set response object, both for this and the boostrap class.
     *
     * If the bootstrap class has a setResponse method it is set.
     *
     * @param \Zend_Controller_Response_Abstract $response
     * @return \Zend_Controller_Plugin_Abstract
     */
    public function setResponse(\Zend_Controller_Response_Abstract $response)
    {
        $this->_escort->setResponse($response);

        return parent::setResponse($response);
    }

}