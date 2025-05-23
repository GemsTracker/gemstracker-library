<?php
/**
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

use Gems\Tracker;
use Gems\Tracker\Token;

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
class CheckTokenCompletion extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Gems\Audit\AuditLog
     */
    protected $accesslog;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * @var \Gems\Loader
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
    public function execute($tokenData = null, $userId = null, $lowMemoryUse = true)
    {
        $batch   = $this->getBatch();
        $tracker = $this->loader->getTracker();

        $batch->addToCounter('checkedTokens');
        $token = $tracker->getToken($tokenData);

        $wasAnswered = $token->isCompleted();

        $oldValues = $this->getTokenValues($token);
        if ($result = $token->checkTokenCompletion($userId)) {
            if ($result & \Gems\Tracker\Token::COMPLETION_DATACHANGE) {
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

                $this->accesslog->registerChanges(
                    $this->getTokenValues($token),
                    $oldValues,
                    [sprintf("%s was answered", $token->getTokenId())],
                    $token->getRespondentId()
                );

                // Reload the data
                $token->refresh();
            }
            if ($result & \Gems\Tracker\Token::COMPLETION_EVENTCHANGE) {
                $i = $batch->addToCounter('surveyCompletionChanges');
                $batch->setMessage('surveyCompletionChanges', sprintf(
                        $this->_('Answers changed by survey completion event for %d tokens.'),
                        $i
                        ));
            }
        }

        if ($token->isCompleted()) {
            $batch->setTask('Tracker\\ProcessTokenCompletion', 'tokproc-' . $token->getTokenId(), $tokenData, $userId, $lowMemoryUse);
        }

        $batch->setMessage('checkedTokens', sprintf(
                $this->_('Checked %d tokens.'),
                $batch->getCounter('checkedTokens')
                ));

        // Free memory
        if ($lowMemoryUse) {
            $tracker->removeToken($token);
        }
    }

    protected function getTokenValues(Token $token)
    {
        return [
            'gto_id_token' => $token->getTokenId(),
            'gto_start_time' => $token->getStartTime()?->format(Tracker::DB_DATETIME_FORMAT),
            'gto_in_source' => $token->inSource() ? 1 : 0,
            'gto_by' => $token->getBy(),
            'gto_completion_time' => $token->getCompletionTime()?->format(Tracker::DB_DATETIME_FORMAT),
            'gto_duration_in_sec' => $token->getDuration(),
            'gto_result' => $token->getResult(),
        ];
    }
}