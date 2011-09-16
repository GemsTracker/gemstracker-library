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
 * @version    $Id$
 * @package    MUtil
 * @subpackage Application
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/** 
 * Hook 1: init() 
 *   $this->_init{Name} method, like _initView()
 *   Resources defined in config loaded.
 * Hook 2: beforeRun() 
 *   $this->request created
 * Hook 3: requestChanged() 
 *   $this->response created 
 * Hook 4: responseChanged() 
 *   $router and $dispatcher initialized (not accesible)
 * Hook 5: routeStartup() 
 *   $router->route() 
 * Hook 6: routeShutdown() 
 * Hook 7: dispatchLoopStartup() 
 *   
 * dispatchLoop: enters next via {@link Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}
 *    Hook 8: preDispatch()
 *      $dispatcher->dispatch
 *      $this->controller->__construct()
 *    Hook 9: controllerInit()
 *      $this->controller->init()
 *      ob_start()
 *      $this->controller->dispatch()
 *    Hook 10: controllerBeforeAction()
 *      $this->controller->preDispatch()
 *      $this->controller->{name}Action()
 *      $this->controller->postDispatch()
 *      ViewRenderer->render() (unless triggered earlier or $this->run() was called with a $stackIndex of < -80)
 *    Hook 11: controllerAfterAction()
 *      $response->appendBody(ob_get_clean());
 *    Hook 12: postDispatch()
 *      Layout->render() (unless $this->run() was called with a $stackIndex of > 99)
 *  
 * while (! Request->isDispatched())
 *  
 * Hook 13: dispatchLoopShutdown() 
 *   $response->sendResponse()
 * Hook 14: responseSend()
 * 
 * @package    MUtil
 * @subpackage Application
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
abstract class MUtil_Application_Escort extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     *
     * @var Zend_Controller_Action_Interface
     */
    public $controller;

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    public $request;

    /**
     *
     * @var Zend_Controller_Response_Abstract
     */
    public $response;

    /** 
     * Hook 2: Called in $this->run(). 
     *  
     * This->init() has ran and the constructor has finisched so all _init{name} and application.ini 
     * resources have been loaded. The code between the constructor and the call to $this->run() has 
     * been executed in $this->run() has hooked $this as both a Zend_Controller_Plugin and a 
     * Zend_Controller_Action_Helper.
     *  
     * Not initialized are the $request, $response and $controller objects.
     *  
     * Previous hook: init()
     * Actions since: $this->_inti{Name}; resources from configuration initialized
     * Actions after: $this->request object created
     * Next hook: requestChanged() 
     *  
     * @return void 
     */ 
    public function beforeRun()
    { }


    /**
     * Hook 11: Called after $controller->{name}Action and $controller->postDispatch() 
     * methods have been called.
     *  
     * Here you can check what was done in the action. All output echoed here is captured 
     * for the output. E.g. Zend_Controller_Action_Helper_ViewRenderer uses this event to 
     * render the view when this has not already been done explicitely. As the ViewRenderer 
     * has a higher priority than the Escort this will already have happened, unless 
     * $this->run() was called with a stackIndex of -81 or lower.
     *  
     * Still all output echoed here is captured for the output. 
     *  
     * Previous hook: controllerBeforeAction()
     * Actions since: $controller->preDispatch(); $controller->{name}Action(); $controller->postDispatch()
     * Actions after: ob_get_clean(); $response->appendBody()
     * Next hook: postDispatch() 
     *
     * @param Zend_Controller_Action $actionController
     * @return void
     */
    public function controllerAfterAction(Zend_Controller_Action $actionController = null)
    { }


    /**
     * Hook 10: Called before the $controller->preDispatch() and $controller->{name}Action 
     * methods have been called.
     *  
     * Here you can change or check all values set in $controller->init(). All output echoed 
     * here is captured for the output.
     *  
     * Previous hook: controllerInit()
     * Actions since: $controller->init(); ob_start(); $controller->dispatch()
     * Actions after: $controller->preDispatch(); $controller->{name}Action(); $controller->postDispatch()
     * Next hook: controllerAfterAction() 
     *  
     * @param Zend_Controller_Action $actionController
     * @return void
     */
    public function controllerBeforeAction(Zend_Controller_Action $actionController = null)
    { }

    /**
     * Hook 9: During action controller initialization.
     *  
     * This hook is called in the constructor of the controller. Nothing is done and 
     * $controller->init has not been called, so this is a good moment to change settings 
     * that should influence $controller->init().
     *  
     * Previous hook: preDispatch()
     * Actions since: $dispatcher->dispatch(); $controller->__construct()
     * Actions after: $controller->init(); ob_start(); $controller->dispatch()
     * Next hook: controllerBeforeAction() 
     *  
     * @param Zend_Controller_Action $actionController
     * @return void
     */
    public function controllerInit(Zend_Controller_Action $actionController = null)
    { }


    /**
     * Hook 13: Called before Zend_Controller_Front exits its dispatch loop.
     *
     * Last change to change anything in the $response. 
     *  
     * Previous hook: postDispatch()
     * Actions since: while (! Request->isDispatched())
     * Actions after: $response->sendResponse() 
     * Next hook: responseSend()
     *  
     * @return void
     */
    public function dispatchLoopShutdown()
    { }


    /**
     * Hook 7: Called before Zend_Controller_Front enters its dispatch loop.
     *
     * This events enables you to adjust the request after the routing has been done. 
     *  
     * This is the final hook before the dispatchLoop starts. All the hooks in the dispatchLoop 
     * can be executed more then once.  
     *  
     * Not yet initialized is the $controller object - as the $controller can change during 
     * the dispatchLoop.
     * 
     * Previous hook: routeShutdown()
     * Actions since: nothing, but the route consisting of controller, action and module should now be fixed
     * Actions after: dispatch loop started
     * Next hook: preDispatch() 
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    { }


    /** 
     * Hook 1: Called in constructor. 
     *  
     * First chance to run code, before anything is set or started.
     * Next the _init{Name} methods are run. 
     * After that the resources specified in the application.ini are loaded. 
     *  
     * Next any code after the construction of this object is ran until the call to 
     * $this->run(). $this->run calls $this->beforeRun(): the next hook you can 
     * put code in after $this->init(). 
     *  
     * Previous hook: none
     * Actions since: nothing
     * Actions after: $this->_inti{Name}; resources from configuration initialized
     * Next hook: beforeRun()
     *  
     * @return void 
     */ 
    public function init() {}


    /**
     * Hook 12: Called after an action is dispatched by Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the 
     * request and resetting its dispatched flag (via {@link 
     * Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}), 
     * a new action may be specified for dispatching.
     *
     * Zend_Layout_Controller_Plugin_Layout uses this event to change the output 
     * of the $response with the rendering of the layout. As the Layout plugin 
     * has a priority of 99, this Escort event will take place before the layout
     * is rendered, unless $this->run() was called with a stackIndex lower than zero.
     *  
     * Previous hook: controllerAfterAction()
     * Actions since: ob_get_clean(); $response->appendBody()
     * Actions after: while (! Request->isDispatched()) or back to Hook 8 preDispatch()
     * Next hook: dispatchLoopShutdown() 
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    { }


    /**
     * Hook 8: Start of dispatchLoop. Called before an action is dispatched 
     * by Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the request 
     * and resetting its dispatched flag (via {@link Zend_Controller_Request_Abstract::setDispatched() 
     * setDispatched(false)}), the current action may be skipped. 
     *  
     * Not yet initialized is the $controller object - as the $controller can change during 
     * the dispatchLoop.
     * 
     * Previous hook: dispatchLoopStartup() or new loop
     * Actions since: dispatch loop started
     * Actions after: $dispatcher->dispatch(); $controller->__construct()
     * Next hook: controllerInit() 
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    { }


    /** 
     * Hook 3: Called in $this->setRequest. 
     *  
     * All resources have been loaded and the $request object is created. 
     * Theoretically this event can be triggered multiple times, but this does 
     * not happen in a standard Zend application. 
     *  
     * Not initialized are the $response and $controller objects.
     *  
     * Previous hook: beforeRun()
     * Actions since: $this->request object created
     * Actions after: $this->response object created
     * Next hook: responseChanged() 
     *  
     * @param Zend_Controller_Request_Abstract $request
     * @return void 
     */ 
    public function requestChanged(Zend_Controller_Request_Abstract $request)
    { }


    /** 
     * Hook 4: Called in $this->setResponse. 
     *  
     * All resources have been loaded and both the $request and $response object have been 
     * created - though $response is not yet know here. This is the first change to get at 
     * the response object. 
     *  
     * Theoretically this event can be triggered multiple times, but this does 
     * not happen in a standard Zend application. 
     *  
     * Not initialized is the $controller object and the routing has not yet been executed.
     *  
     * Previous hook: requestChanged()
     * Actions since: $this->response object created
     * Actions after: $router & $dispatcher objects created (both not accessible though)
     * Next hook: routeStartup() 
     *  
     * @param Zend_Controller_Response_Abstract $response
     * @return void 
     */ 
    public function responseChanged(Zend_Controller_Response_Abstract $response)
    { }


    /** 
     * Hook 14: Called in $this->run for cleanup after the response has been send. 
     *  
     * The output is send, so this is kind of the finalize of the escort object.
     *  
     * Previous hook: dispatchLoopShutdown()
     * Actions since: $response->sendResponse() 
     * Actions after: nothing
     * Next hook: nothing
     *  
     * @return void 
     */ 
    public function responseSend()
    { }


    /**
     * Hook 6: Called after Zend_Controller_Router has determined the route set by the request.
     *
     * This events enables you to adjust the route after the routing has run it's course.
     *  
     * Not initialized is the $controller object.
     * 
     * Previous hook: routeStartup()
     * Actions since: $router->route()
     * Actions after: nothing, but the route consisting of controller, action and module should now be fixed
     * Next hook: dispatchLoopStartup() 
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    { }


    /**
     * Hook 5: Called before Zend_Controller_Front begins evaluating the
     * request against its routes.
     *  
     * All resources have been loaded and the $request and $response object have been created. 
     *  
     * Not initialized is the $controller object and the routing has not yet been executed.
     * 
     * Previous hook: responseChanged()
     * Actions since: $router & $dispatcher objects created (both not accessible though)
     * Actions after: $router->route()
     * Next hook: routeShutdown() 
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    { }


    /**
     * No hook. Run the application.
     *
     * Registers the bootstrap as a FrontController Plugin and calls
     * beforeRun() and the parent::run(). Final as these actions are 
     * crucial to the workings of this class. 
     *  
     * As long as $stackIndex is < 99 then postDispatch() becalled before the 
     * $layout is rendered. If $stackIndex is < -80 then the controllerAfterAction() 
     * event will be called before the $view is rendered as well. 
     * By default the layout is called after postDispatch() while 
     * controllerAfterAction() will be called after the rendering of the view. 
     *
     * @param  int $stackIndex Optional; stack index for plugins
     * @return void
     * @throws Zend_Application_Bootstrap_Exception
     */
    final public function run($stackIndex = null)
    {
        MUtil_Application_EscortPlugin::register($this, $stackIndex);
        MUtil_Application_EscortControllerHelper::register($this, $stackIndex);

        $this->beforeRun();

        parent::run();

        $this->responseSend();
    }

    /**
     * No hook. Called by MUtil_Application_EscortControllerHelper->setActionController() 
     * and sets the controller. No event hooked up as controllerInit() is called straigt 
     * after this call.
     *
     * @param  Zend_Controller_Action $actionController
     * @return Zend_Controller_ActionHelper_Abstract Provides a fluent interface
     */
    public final function setActionController(Zend_Controller_Action $actionController = null)
    {
        $this->controller = $actionController;

        return $this;
    }


   /**
     * No hook. Sets request object for $this-> and triggers $this->requestChanged().
     *  
     * Final as the workings are central to the Escort class. 
     *  
     * @param Zend_Controller_Request_Abstract $request
     * @return MUtil_Application_Escort
     */
    public final function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;

        $this->requestChanged($request);

        return $this;
    }


    /**
     * No hook. Sets response object for $this-> and triggers $this->reSponseChanged().
     *  
     * Final as the workings are central to the Escort class. 
     *
     * @param Zend_Controller_Response_Abstract $response
     * @return MUtil_Application_Escort
     */
    public final function setResponse(Zend_Controller_Response_Abstract $response)
    {
        $this->response = $response;

        $this->responseChanged($response);

        return $this;
    }
}