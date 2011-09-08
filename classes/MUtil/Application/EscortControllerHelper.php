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
 * @version    $Id: EscortControllerHelper.php 345 2011-07-28 08:39:24Z 175780 $
 * @package    MUtil
 * @subpackage Application
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * @package    MUtil
 * @subpackage Application
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class MUtil_Application_EscortControllerHelper extends Zend_Controller_Action_Helper_Abstract
{
    private $_escort;

    public function __construct(MUtil_Application_Escort $escort)
    {
        $this->setEscort($escort);
    }

    public function getEscort()
    {
        return $this->_escort;
    }

    /**
     * Hook into action controller initialization
     *
     * @return void
     */
    public function init()
    {
        $this->_escort->controllerInit($this->getActionController());
    }

    /**
     * Hook into action controller preDispatch() workflow
     *
     * @return void
     */
    public function preDispatch()
    {
        $this->_escort->controllerBeforeAction($this->getActionController());
    }

    /**
     * Hook into action controller postDispatch() workflow
     *
     * @return void
     */
    public function postDispatch()
    {
        $this->_escort->controllerAfterAction($this->getActionController());
    }

    /**
     * Register escort as a controller helper.
     *
     * @param  MUtil_Application_Escort $escort
     * @return self
     */
    public static function register(MUtil_Application_Escort $escort)
    {
        $helper = new self($escort);

        Zend_Controller_Action_HelperBroker::addHelper($helper);

        return $helper;
    }

    /**
     * setActionController()
     *
     * @param  Zend_Controller_Action $actionController
     * @return Zend_Controller_ActionHelper_Abstract Provides a fluent interface
     */
    public function setActionController(Zend_Controller_Action $actionController = null)
    {
        $result = parent::setActionController($actionController);

        $this->_escort->setActionController($actionController);

        return $result;
    }

    public function setEscort(MUtil_Application_Escort $escort)
    {
        $this->_escort = $escort;

        return $this;
    }
}