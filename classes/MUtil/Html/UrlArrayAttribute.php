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
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Html
 */

class MUtil_Html_UrlArrayAttribute extends MUtil_Html_ArrayAttribute
{
    protected $_request;
    protected $_routeReset;

    protected $_separator = '&';

    private static function _rerouteUrlOption(Zend_Controller_Request_Abstract $request, $name, &$options)
    {
        if (! array_key_exists($name, $options)) {
            if ($value = $request->getParam($name)) {
                $options[$name] = $value;
            }
        }
    }

    public function get()
    {
        $url_string = '';
        $url_parameters = array();

        foreach ($this->_getArrayRendered() as $key => $value) {
            // $value = rawurlencode($value);
            if (is_numeric($key)) {
                $url_string .= $value;
            } else {
                // Prevent double escaping by using rawurlencode() instead
                // of urlencode() that is used by Zend_Controller_Router_Route
                $url_parameters[$key] =  rawurlencode($value);
            }
        }

        // If a string is defined we assume it is a full url
        if ($url_string) {
            if ($url_parameters) {
                foreach ($url_parameters as $key => $value) {
                    $params[] = $this->getKeyValue($key, $value);
                }
                return $url_string . '?' . implode($this->getSeparator(), $params);
            }

            return $url_string;
        }

        // Only when no string is defined we assume this is a Zend MVC url
        if ($url_parameters) {
            // Make sure controllor, action, module are specified
            $url_parameters = self::rerouteUrl($this->getRequest(), $url_parameters);

            return $this->getView()->url($url_parameters, null, $this->getRouteReset(), false);
        }

        return null;
    }

    public function getKeyValue($key, $value)
    {
        return $key . '=' . $value;
    }

    public function getRequest()
    {
        if (! $this->_request) {
            $front = Zend_Controller_Front::getInstance();
            $this->_request = $front->getRequest();
        }

        return $this->_request;
    }

    public function getRouteReset()
    {
        return $this->_routeReset;
    }

    public function isMvcUrl()
    {
        foreach ($this->getArray() as $key => $value) {
            if (is_numeric($key)) {
                return false;
            }
        }

        return true;
    }

    public static function rerouteUrl(Zend_Controller_Request_Abstract $request, $options, $addRouteReset = false)
    {
        self::_rerouteUrlOption($request, 'module', $options);
        self::_rerouteUrlOption($request, 'controller', $options);
        self::_rerouteUrlOption($request, 'action', $options);

        if ($addRouteReset) {
            $options['RouteReset'] = true;
        }

        return $options;
    }

    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;
        return $this;
    }

    public function setRouteReset($routeReset = true)
    {
        $this->_routeReset = $routeReset;
        return $this;
    }

    public function toPage($label)
    {
        if ($this->isMvcUrl()) {

            $options = $this->getArray();
            // Make sure controllor, action, module are specified
            $options = self::rerouteUrl($this->getRequest(), $options);
            $options['label'] = $label;

            return new Zend_Navigation_Page_Mvc($options);

        } else {
            $options['url'] = $this;
            $options['label'] = $label;

            return new Zend_Navigation_Page_Uri($options);
        }
    }
}