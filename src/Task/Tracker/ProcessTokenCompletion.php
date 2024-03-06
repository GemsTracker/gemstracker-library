<?php

/**
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

use Gems\Legacy\CurrentUserRepository;
use Gems\Tracker;

/**
 * Handles completion of a token, mostly started by \Gems_Task_CheckTokenCompletion
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class ProcessTokenCompletion extends \MUtil\Task\TaskAbstract
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($tokenData = null, $userId = null, $lowMemoryUse = true)
    {
        $batch   = $this->getBatch();
        $token   = $this->tracker->getToken($tokenData);

//        if ($token->isCompleted()) {
            $respTrack = $token->getRespondentTrack();
            $userId    = $userId ? $userId : $this->currentUserRepository->getCurrentUserId();

            if ($result = $respTrack->handleRoundCompletion($token, $userId)) {
                $a = $batch->addToCounter('roundCompletionCauses');
                $b = $batch->addToCounter('roundCompletionChanges', $result);
                $batch->setMessage('roundCompletionChanges',
                        sprintf($this->_('%d token round completion events caused changed to %d tokens.'), $a, $b)
                        );
            }

            $trackId = $respTrack->getRespondentTrackId();
            $batch->setTask('Tracker\\CheckTrackTokens', 'chktrck-' . $trackId, $trackId, $userId);
//        }

        if ($lowMemoryUse) {
            // Free memory
            $this->tracker->removeToken($token);
        }
    }
}