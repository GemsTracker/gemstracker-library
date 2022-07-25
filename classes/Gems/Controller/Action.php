<?php


/**
 * @package    Gems
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Controller;

/**
 * Action controller, initialises the html object
 *
 * @package    Gems
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Action extends \MUtil\Controller\Action
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     *
     * @var \Zend_Controller_Action_Helper_FlashMessenger
     */
    public $messenger;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed $message_args Can be an array or multiple argemuents. Each sub element is a single message string
     * @return \MUtil\Controller\Action
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
    public function getMessenger(): \Mezzio\Flash\FlashMessagesInterface
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
    public function initHtml(bool $reset = false): void
    {
        if (! $this->html) {
            \Gems\Html::init();
        }

        parent::initHtml();
    }

    /**
     * Stub for overruling default snippet loader initiation.
     */
    protected function loadSnippetLoader(): void
    {
        // Create the snippet with this controller as the parameter source
        $this->snippetLoader = $this->loader->getSnippetLoader($this);
    }

    /**
     * Set the session based message store.
     *
     * @param \Zend_Controller_Action_Helper_FlashMessenger $messenger
     * @return \MUtil\Controller\Action
     */
    public function setMessenger(\Zend_Controller_Action_Helper_FlashMessenger $messenger): self
    {
        $this->messenger = $messenger;
        $this->view->messenger = $messenger;

        return $this;
    }
}
