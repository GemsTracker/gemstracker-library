<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

/**
 * Keeps and reuse earlier request parameters in session cache
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 * @deprecated since 1.7.2
 */
class RequestCache extends \Gems\Registry\TargetAbstract
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
     * True if the cache should not be written to.
     *
     * @var boolean
     */
    protected $_readonly = false;

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
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var \Zend_Session_Namespace
     */
    protected $session;

    /**
     *
     * @var string Optional different action to use from that of the current request
     */
    protected $sourceAction;

    /**
     *
     * @param string  $sourceAction    The action to get the cache from if not the current one.
     * @param boolean $readonly        Optional, tell the cache not to store any new values
     */
    public function __construct($sourceAction = null, $readonly = false)
    {
        if ($sourceAction) {
            $this->setSourceAction($sourceAction);
        }
        $this->setReadonly($readonly);
    }

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
     * @return \Gems\Menu
     */
    protected function getMenu()
    {
        if (! $this->menu) {
            $escort = \Gems\Escort::getInstance();
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

            $programParams = $request->getParams();
            foreach ($this->getRequestKey() as $key => $value) {
                unset($programParams[$key]);
            }

            if (isset($programParams[self::RESET_PARAM]) && $programParams[self::RESET_PARAM]) {
                unset($this->session->requestCache[$this->_storageKey]);
                $request->setParam(self::RESET_PARAM, null);
            } else {
                // Add cache
                $programParams = $programParams + $this->getCachedRequest();

                // Set menu up for reset
                $menu->getCurrent()->addParameters(self::RESET_PARAM);
                // Means this
                $menu->getParameterSource()->offsetSet(self::RESET_PARAM, 1);
            }
            unset($programParams[self::RESET_PARAM]);

            $this->setProgramParams($programParams);

            // \MUtil\EchoOut\EchoOut::track($programParams);

        }
        return $this->_programParams;
    }

    /**
     *
     * @return \Zend_Controller_Request_Abstract
     */
    protected function getRequest()
    {
        if (! $this->request) {
            $front = \Zend_Controller_Front::getInstance();
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
     * @return \Gems\Util\RequestCache
     */
    public function removeParams($key_arg1)
    {
        $args = \MUtil\Ra::flatten(func_get_args());

        $this->_baseUrl = null;

        $params = $this->getProgramParams();

        foreach ($args as $key) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
        // \MUtil\EchoOut\EchoOut::track($params);

        $this->setProgramParams($params);

        return $this;
    }

    /**
     *
     * @param \Gems\Menu $menu
     * @return \Gems\Util\RequestCache (continuation pattern)
     */
    public function setMenu(\Gems\Menu $menu)
    {
        $this->menu = $menu;

        return $this;
    }

    /**
     * Set the keys stored fot this cache
     *
     * @param array $programParams
     * @return \Gems\Util\RequestCache (continuation pattern)
     */
    public function setProgramParams(array $programParams)
    {
        // Store result
        $this->_programParams = $programParams;

        if (! $this->_readonly) {
            $this->session->requestCache[$this->getStorageKey()] = $programParams;
        }

        return $this;
    }

    /**
     * Makes sure any new values in the request are not written to the cache.
     *
     * @param boolen $value
     * @return \Gems\Util\RequestCache (continuation pattern)
     */
    public function setReadonly($value = true)
    {
        $this->_readonly = (boolean) $value;

        return $this;
    }

    /**
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return \Gems\Util\RequestCache (continuation pattern)
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the actiuon to use instead of the current one.
     *
     * @param string $action
     * @return \Gems\Util\RequestCache (continuation pattern)
     */
    public function setSourceAction($action)
    {
        $this->sourceAction = $action;

        return $this;
    }
}
