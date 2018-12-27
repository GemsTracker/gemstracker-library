<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Legacy\Controller\Action;

/**
 * Description of HelperBroker
 *
 * @author 175780
 */
class HelperBroker extends \Zend_Controller_Action_HelperBroker
{
    public function __construct($actionController)
    {
        $this->_actionController = $actionController;
        foreach (self::getStack() as $helper) {
            $helper->setActionController($actionController);
            $helper->init();
        }
    }
}
