<?php

/**
 *
 * @package    Gems
 * @subpackage Legacy\Controller\Dispatcher
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Legacy\Controller\Dispatcher;

use MUtil\Controller\Request\ExpressiveRequestWrapper;
use Zend\Diactoros\ServerRequestFactory;
use Zend_Controller_Action;
use Zend_Controller_Action_HelperBroker;
use Zend_Controller_Action_Interface;
use Zend_Controller_Dispatcher_Exception;
use Zend_Controller_Dispatcher_Standard;
use Zend_Controller_Request_Abstract;
use Zend_Controller_Response_Abstract;

/**
 *
 * @package    Gems
 * @subpackage Legacy\Controller\Dispatcher
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 2.0.0
 */
class Expressive extends Zend_Controller_Dispatcher_Standard
{
    /**
     * Dispatch to a controller/action
     *
     * By default, if a controller is not dispatchable, dispatch() will throw
     * an exception. If you wish to use the default controller instead, set the
     * param 'useDefaultControllerAlways' via {@link setParam()}.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @return void
     * @throws Zend_Controller_Dispatcher_Exception
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
        $this->setResponse($response);

        /**
         * Get controller class
         */
        if (!$this->isDispatchable($request)) {
            $controller = $request->getControllerName();
            if (!$this->getParam('useDefaultControllerAlways') && !empty($controller)) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new Zend_Controller_Dispatcher_Exception('Invalid controller specified (' . $request->getControllerName() . ')');
            }

            $className = $this->getDefaultControllerClass($request);
        } else {
            $className = $this->getControllerClass($request);
            if (!$className) {
                $className = $this->getDefaultControllerClass($request);
            }
        }

        /**
         * If we're in a module or prefixDefaultModule is on, we must add the module name
         * prefix to the contents of $className, as getControllerClass does not do that automatically.
         * We must keep a separate variable because modules are not strictly PSR-0: We need the no-module-prefix
         * class name to do the class->file mapping, but the full class name to insantiate the controller
         */
        $moduleClassName = $className;
        if (($this->_defaultModule != $this->_curModule)
            || $this->getParam('prefixDefaultModule'))
        {
            $moduleClassName = $this->formatClassName($this->_curModule, $className);
        }

        /**
         * Load the controller class file
         */
        $className = $this->loadClass($className);

        /**
         * Instantiate controller with request, response, and invocation
         * arguments; throw exception if it's not an action controller
         */
        //$controller = new $moduleClassName($request, $this->getResponse(), $this->getParams());
        /** CHANGES FROM HERE */
        $serverRequest  = ServerRequestFactory::fromGlobals();
        $expressiveRequest = new ExpressiveRequestWrapper($serverRequest);
        
        /** @var \Psr\Container\ContainerInterface $container */
        $container = require VENDOR_DIR . 'gemstracker/api/config/container.php';
        $routerFactory = new \Zend\Expressive\Router\FastRouteRouterFactory();
        $router = $routerFactory($container);
        $urlHelper = new \Zend\Expressive\Helper\UrlHelper($router);
        $controller = new $moduleClassName($expressiveRequest, $urlHelper);
        \MUtil\Controller\Front::setRequest($expressiveRequest);
        $routerWrapper = new \MUtil\Controller\Router\ExpressiveRouteWrapper($serverRequest, $urlHelper);
        \MUtil\Controller\Front::setRouter($routerWrapper);
        /*$controller->setRequest($request)
             ->setResponse($this->getResponse())
             ->_setInvokeArgs($this->getParams());*/
        $controller->init();
        
        
        /*if (!($controller instanceof Zend_Controller_Action_Interface) &&
            !($controller instanceof Zend_Controller_Action)) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception(
                'Controller "' . $moduleClassName . '" is not an instance of Zend_Controller_Action_Interface'
            );
        }*/
        /** END CHANGES */

        /**
         * Retrieve the action name
         */
        $action = $this->getActionMethod($request);

        /**
         * Dispatch the method call
         */
        $request->setDispatched(true);

        // by default, buffer output
        $disableOb = $this->getParam('disableOutputBuffering');
        $obLevel   = ob_get_level();
        if (empty($disableOb)) {
            ob_start();
        }

        try {
            $controller->dispatch($action);
        } catch (Exception $e) {
            // Clean output buffer on error
            $curObLevel = ob_get_level();
            if ($curObLevel > $obLevel) {
                do {
                    ob_get_clean();
                    $curObLevel = ob_get_level();
                } while ($curObLevel > $obLevel);
            }
            throw $e;
        }

        if (empty($disableOb)) {
            $content = ob_get_clean();
            $response->appendBody($content);
        }

        // Destroy the page controller instance and reflection objects
        $controller = null;
    }
    
}
