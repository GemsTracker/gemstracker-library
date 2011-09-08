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
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RequestCache.php 430 2011-08-18 10:40:21Z 175780 $
 */

/**
 * Keeps and reuse earlier request parameters in session cache
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Util_RequestCache extends Gems_Registry_TargetAbstract
{
    /**
     * Url parameter to reset
     */
    const RESET_PARAM = 'reset';

    /**
     *
     * @var array
     */
    protected $_baseUrl;

    /**
     *
     * @var array
     */
    protected $_programParams = array();

    /**
     * The module / controller /action of the request in an array.
     *
     * @var array
     */
    protected $_requestKey;

    /**
     * String identifying the current module / controller /action of the request.
     *
     * @var string
     */
    protected $_storageKey;

    /**
     *
     * @var Gems_Menu
     */
    protected $menu;

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     *
     * @var string Optional different action to use from that of the current request
     */
    protected $sourceAction;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return (boolean) $this->session;
    }

    /**
     *
     * @return array
     */
    public function getBaseUrl()
    {
        if (! $this->_baseUrl) {
            $this->_baseUrl = $this->getProgramParams() + array(self::RESET_PARAM => null);
        }

        return $this->_baseUrl;
    }

    /**
     *
     * @return array
     */
    public function getCachedRequest()
    {
        $key = $this->getStorageKey();

        if (isset($this->session->requestCache[$key])) {
            return $this->session->requestCache[$key];
        } else {
            return array();
        }
    }

    /**
     *
     * @return Gems_Menu
     */
    protected function getMenu()
    {
        if (! $this->menu) {
            $escort = GemsEscort::getInstance();
            $this->setMenu($escort->menu);
        }

        return $this->menu;
    }

    /**
     *
     * @return array
     */
    public function getProgramParams()
    {
        if (! $this->_programParams) {
            $menu    = $this->getMenu();
            $request = $this->getRequest();

            $programParams = array_diff($request->getParams(), $this->getRequestKey());

            if (isset($programParams[self::RESET_PARAM]) && $programParams[self::RESET_PARAM]) {
                unset($programParams[self::RESET_PARAM]);
                unset($this->session->requestCache[$this->_storageKey]);
            } else {
                // Add cache
                $programParams = $programParams + $this->getCachedRequest();

                // Set menu up for reset
                $menu->getCurrent()->addParameters(self::RESET_PARAM);
                // Means this
                $request->setParam(self::RESET_PARAM, 1);
            }

            $this->setProgramParams($programParams);

            // MUtil_Echo::track($programParams);

        }
        return $this->_programParams;
    }

    /**
     *
     * @return Zend_Controller_Request_Abstract
     */
    protected function getRequest()
    {
        if (! $this->request) {
            $front = Zend_Controller_Front::getInstance();
            $this->setRequest($front->getRequest());
        }

        return $this->request;
    }

    /**
     * The module / controller /action of the request in an array.
     *
     * @return array
     */
    protected function getRequestKey()
    {
        if (! $this->_requestKey) {
            $request = $this->getRequest();

            $this->_requestKey[$request->getModuleKey()]     = $request->getModuleName();
            $this->_requestKey[$request->getControllerKey()] = $request->getControllerName();
            $this->_requestKey[$request->getActionKey()]     = $this->sourceAction ? $this->sourceAction : $request->getActionName();
        }

        return $this->_requestKey;
    }

    /**
     *
     * @return string Key identifying the current request
     */
    protected function getStorageKey()
    {
        if (! $this->_storageKey) {
            $this->_storageKey = implode('/', $this->getRequestKey());
        }

        return $this->_storageKey;
    }

    /**
     *
     * @param string $key_arg1 First of optionally many arguments
     * @return Gems_Util_RequestCache
     */
    public function removeParams($key_arg1)
    {
        $args = MUtil_Ra::flatten(func_get_args());

        $this->_baseUrl = null;

        $params = $this->getProgramParams();

        foreach ($args as $key) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
        // MUtil_Echo::r($params);

        $this->setProgramParams($params);

        return $this;
    }

    /**
     *
     * @param Gems_Menu $menu
     * @return Gems_Util_RequestCache (continuation pattern)
     */
    public function setMenu(Gems_Menu $menu)
    {
        $this->menu = $menu;

        return $this;
    }

    public function setProgramParams(array $programParams)
    {
        foreach ($programParams as $key => $value) {
            if ((is_array($value) && empty($value)) || (is_string($value) && 0 === strlen($value))) {
                unset($programParams[$key]);
            }
        }

        // Store result
        $this->_programParams = $programParams;
        $this->session->requestCache[$this->getStorageKey()] = $programParams;

        return $this;
    }

    /**
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return Gems_Util_RequestCache (continuation pattern)
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the actiuon to use instead of the current one.
     *
     * @param string $action
     * @return Gems_Util_RequestCache (continuation pattern)
     */
    public function setSourceAction($action)
    {
        $this->sourceAction = $action;

        return $this;
    }
}
