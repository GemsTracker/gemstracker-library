<?php


/**
 * @package    Gems
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Action controller, initialises the html object
 *
 * @package    Gems
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Controller_Action extends \MUtil_Controller_Action
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Zend_Controller_Action_Helper_FlashMessenger
     */
    public $messenger;

    /**
     *
     * @var \Gems_Util
     */
    public $util;

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed $message_args Can be an array or multiple argemuents. Each sub element is a single message string
     * @return \MUtil_Controller_Action
     */
    public function addMessage($message, $status=null)
    {
        $messenger = $this->getMessenger();
        $messenger->addMessage($message, $status);

        return $this;
    }

    /**
     * Returns a session based message store for adding messages to.
     *
     * @return \Zend_Controller_Action_Helper_FlashMessenger
     */
    public function getMessenger()
    {
        if (! $this->messenger) {
            $this->setMessenger($this->loader->getMessenger());
        }

        return $this->messenger;
    }

    /**
     * Intializes the html component.
     *
     * @param boolean $reset Throws away any existing html output when true
     * @return void
     */
    public function initHtml($reset = false)
    {
        if (! $this->html) {
            \Gems_Html::init();
        }

        parent::initHtml();
    }

    /**
     * Stub for overruling default snippet loader initiation.
     */
    protected function loadSnippetLoader()
    {
        // Create the snippet with this controller as the parameter source
        $this->snippetLoader = $this->loader->getSnippetLoader($this);
    }

    /**
     * Set the session based message store.
     *
     * @param \Zend_Controller_Action_Helper_FlashMessenger $messenger
     * @return \MUtil_Controller_Action
     */
    public function setMessenger(\Zend_Controller_Action_Helper_FlashMessenger $messenger)
    {
        $this->messenger = $messenger;
        $this->view->messenger = $messenger;

        return $this;
    }
}