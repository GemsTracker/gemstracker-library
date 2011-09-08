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
 */

/**
 * ZFDebug Zend Additions
 *
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 * @version    $Id: Time.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 * @see Zend_Session
 */


/**
 * @see Zend_Session_Namespace
 */


/**
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 */
class ZFDebug_Controller_Plugin_Debug_Plugin_Time extends Zend_Controller_Plugin_Abstract implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'time';

    /**
     * @var array
     */
    protected $_timer = array();

    /**
     * Creating time plugin
     * @return void
     */
    public function __construct()
    {
        Zend_Controller_Front::getInstance()->registerPlugin($this);
    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
        return round($this->_timer['postDispatch'],2) . ' ms';
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        $html = '<h4>Custom Timers</h4>';
        $html .= 'Controller: ' . round(($this->_timer['postDispatch']-$this->_timer['preDispatch']),2) .' ms<br />';
        if (isset($this->_timer['user']) && count($this->_timer['user'])) {
            foreach ($this->_timer['user'] as $name => $time) {
                $html .= ''.$name.': '. round($time,2).' ms<br>';
            }
        }

        if(!Zend_Session::isStarted())
        {
            Zend_Session::start();
        }

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $this_module = $request->getModuleName();
        $this_controller = $request->getControllerName();
        $this_action = $request->getActionName();

        $timerNamespace = new Zend_Session_Namespace('ZFDebug_Time',false);
        $timerNamespace->data[$this_module][$this_controller][$this_action][] = $this->_timer['postDispatch'];

        $html .= '<h4>Overall Timers</h4>';

        foreach($timerNamespace->data as $module => $controller)
        {
            if ($module == $this_module) {
                $module = '<strong>'.$module.'</strong>';
            }
            $html .= $module . '<br />';
            $html .= '<div class="pre">';
            foreach($controller as $con => $action)
            {
                if ($con == $this_controller) {
                    $con = '<strong>'.$con.'</strong>';
                }
                $html .= '    ' . $con . '<br />';
                $html .= '<div class="pre">';
                foreach ($action as $key => $data)
                {
                    if ($key == $this_action) {
                        $key = '<strong>'.$key.'</strong>';
                    }
                    $html .= '        ' . $key . '<br />';
                    $html .= '<div class="pre">';
                    $html .= '            Avg: ' . $this->_calcAvg($data) . ' ms / '.count($data).' requests<br />';
                    $html .= '            Min: ' . round(min($data), 2) . ' ms<br />';
                    $html .= '            Max: ' . round(max($data), 2) . ' ms<br />';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '<br />Reset timers by sending ZFDEBUG_RESET as a GET/POST parameter';

        return $html;
    }

    /**
     * Sets a time mark identified with $name
     *
     * @param string $name
     */
    public function mark($name) {
        if (isset($this->_timer['user'][$name]))
            $this->_timer['user'][$name] = (microtime(true)-$_SERVER['REQUEST_TIME'])*1000-$this->_timer['user'][$name];
        else
            $this->_timer['user'][$name] = (microtime(true)-$_SERVER['REQUEST_TIME'])*1000;
    }

    #public function routeStartup(Zend_Controller_Request_Abstract $request) {
    #     $this->timer['routeStartup'] = (microtime(true)-$_SERVER['REQUEST_TIME'])*1000;
    #}

    #public function routeShutdown(Zend_Controller_Request_Abstract $request) {
    #     $this->timer['routeShutdown'] = (microtime(true)-$_SERVER['REQUEST_TIME'])*1000;
    #}

    /**
     * Defined by Zend_Controller_Plugin_Abstract
     *
     * @param Zend_Controller_Request_Abstract
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $reset = Zend_Controller_Front::getInstance()->getRequest()->getParam('ZFDEBUG_RESET');
        if (isset($reset)) {
            $timerNamespace = new Zend_Session_Namespace('ZFDebug_Time',false);
            $timerNamespace->unsetAll();
        }
        
        $this->_timer['preDispatch'] = (microtime(true)-$_SERVER['REQUEST_TIME'])*1000;
    }

    /**
     * Defined by Zend_Controller_Plugin_Abstract
     *
     * @param Zend_Controller_Request_Abstract
     * @return void
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_timer['postDispatch'] = (microtime(true)-$_SERVER['REQUEST_TIME'])*1000;
    }

    /**
     * Calculate average time from $array
     *
     * @param array $array
     * @param int $precision
     * @return float
     */
    protected function _calcAvg(array $array, $precision=2)
    {
        if(!is_array($array)) {
            return 'ERROR in method _calcAvg(): this is a not array';
        }

        foreach($array as $value)
            if(!is_numeric($value)) {
                return 'ERROR in method _calcAvg(): the array contains one or more non-numeric values';
            }

        $cuantos=count($array);
        return round(array_sum($array)/$cuantos,$precision);
    }
}