<?php

/**
 * @package    Gems
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * This helper provides an easy method for running tasks in batch.
 *
 * Just provide the batch and the title to use and you will be fine.
 *
 * @package    Gems
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Controller_Action_Helper_BatchRunner extends \Zend_Controller_Action_Helper_Abstract
{
    /**
     *
     * @param \MUtil_Batch_BatchAbstract $batch
     * @param string $title
     * @param \Gems_AccessLog $accessLog
     */
    public function direct(\MUtil_Batch_BatchAbstract $batch, $title, $accessLog = null)
    {

        if ($batch->isConsole()) {
            $batch->runContinuous();

            $messages = array_values($batch->getMessages(true));
            echo implode("\n", $messages) . "\n";

            $echo = array_filter(array_map('trim', preg_split('/<[^>]+>/', \MUtil_Echo::out())));
            if ($echo) {
                echo "\n\n================================================================\nECHO OUTPUT:\n\n";
                echo implode("\n", $echo);
            }
            if ($accessLog instanceof \Gems_AccessLog) {
                array_unshift($messages, $title);
                $accessLog->logChange($this->getRequest(), $messages, $echo);
            }
            exit;
        } elseif ($batch->run($this->getRequest())) {
            exit;
        } else {
            $controller = $this->getActionController();
            $batchContainer = $controller->html->div(array('class' => 'batch-container'));
            $batchContainer->h3($title);

            if ($batch->isFinished()) {
                $messages = array_values($batch->getMessages(true));
                $controller->addMessage($messages, 'info');
                $batchContainer->pInfo($batch->getRestartButton($controller->_('Prepare recheck'), array('class' => 'actionlink')));
                if ($accessLog instanceof \Gems_AccessLog) {
                    array_unshift($messages, $title);
                    $echo = array_filter(array_map('trim', preg_split('/<[^>]+>/', \MUtil_Echo::getOutput())));
                    $accessLog->logChange($this->getRequest(), $messages, $echo);
                }
            } else {
                if ($batch->count()) {
                    $batchContainer->pInfo($batch->getStartButton(sprintf($controller->_('Start %s jobs'), $batch->count())));
                    $batchContainer->append($batch->getPanel($controller->view, $batch->getProgressPercentage() . '%'));
                } else {
                    $batchContainer->pInfo($controller->_('Nothing to do.'));
                }
            }
        }
    }
}
