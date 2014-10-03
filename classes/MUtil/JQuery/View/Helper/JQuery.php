<?php

class MUtil_JQuery_View_Helper_JQuery extends ZendX_JQuery_View_Helper_JQuery
{
   /**
     * Initialize helper
     *
     * Retrieve container from registry or create new container and store in
     * registry.
     *
     * @return void
     */
    public function __construct()
    {
        $registry = Zend_Registry::getInstance();
        if (!isset($registry[__CLASS__])) {
            $container = new MUtil_JQuery_View_Helper_JQuery_Container();
            $registry[__CLASS__] = $container;
        }
        $this->_container = $registry[__CLASS__];
    }
}
