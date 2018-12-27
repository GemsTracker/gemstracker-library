<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Gems_Layout_Controller_Action_Helper_Layout
 *
 * @author 175780
 */
class Gems_Layout_Controller_Action_Helper_Layout extends Zend_Layout_Controller_Action_Helper_Layout
{
    public function setActionController($actionController = null)
    {
        $this->_actionController = $actionController;
        return $this;
    }
}
