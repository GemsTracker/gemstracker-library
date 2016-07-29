<?php
/**
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Check token completion in a batch job
 *
 * This task handles the token completion check, adding tasks to the queue
 * when needed.
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_Tracker_CheckTokenCompletion extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    protected $accesslog;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($tokenData = null, $userId = null)
    {
        $batch   = $this->getBatch();
        $tracker = $this->loader->getTracker();

        $batch->addToCounter('checkedTokens');
        $token = $tracker->getToken($tokenData);

        $wasAnswered = $token->isCompleted();

        if ($result = $token->checkTokenCompletion($userId)) {
            if ($result & \Gems_Tracker_Token::COMPLETION_DATACHANGE) {
                $i = $batch->addToCounter('resultDataChanges');
                $batch->setMessage('resultDataChanges', sprintf(
                        $this->_('Results and timing changed for %d tokens.'),
                        $i
                        ));

                if ($wasAnswered) {
                    $action  = 'token.data-changed';
                    $message = sprintf($this->_("Token '%s' data has changed."), $token->getTokenId());
                } else {
                    $action  = 'token.answered';
                    $message = sprintf($this->_("Token '%s' was answered."), $token->getTokenId());
                }
                if (! $this->request instanceof \Zend_Controller_Request_Abstract) {
                    $this->request = \Zend_Controller_Front::getInstance()->getRequest();
                }
                $this->accesslog->logEntry(
                        $this->request,
                        $action,
                        true,
                        $message,
                        $token->getArrayCopy(),
                        $token->getRespondentId()
                        );

            }
            if ($result & \Gems_Tracker_Token::COMPLETION_EVENTCHANGE) {
                $i = $batch->addToCounter('surveyCompletionChanges');
                $batch->setMessage('surveyCompletionChanges', sprintf(
                        $this->_('Answers changed by survey completion event for %d tokens.'),
                        $i
                        ));
            }
        }

        if ($token->isCompleted()) {
            $batch->setTask('Tracker_ProcessTokenCompletion', 'tokproc-' . $token->getTokenId(), $tokenData, $userId);
        }

        $batch->setMessage('checkedTokens', sprintf(
                $this->_('Checked %d tokens.'),
                $batch->getCounter('checkedTokens')
                ));

        // Free memory
        $tracker->removeToken($token);
    }
}