<?php

/**
 * Description of ViewHelper
 *
 * @author 175780
 */
class Gems_Controller_Action_Helper_ViewRenderer extends \Zend_Controller_Action_Helper_ViewRenderer
{
    public function setActionController($actionController = null)
    {
        $this->_actionController = $actionController;
        return $this;
    }
}
