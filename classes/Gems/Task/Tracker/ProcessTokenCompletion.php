<?php

/**
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Handles completion of a token, mostly started by \Gems_Task_CheckTokenCompletion
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_Tracker_ProcessTokenCompletion extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Gems_Loader
     */
    public $loader;

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
        $token   = $tracker->getToken($tokenData);

        if ($token->isCompleted()) {
            $respTrack = $token->getRespondentTrack();
            $userId    = $userId ? $userId : $this->loader->getCurrentUser()->getUserId();

            if ($result = $respTrack->handleRoundCompletion($token, $userId)) {
                $a = $batch->addToCounter('roundCompletionCauses');
                $b = $batch->addToCounter('roundCompletionChanges', $result);
                $batch->setMessage('roundCompletionChanges',
                        sprintf($this->_('%d token round completion events caused changed to %d tokens.'), $a, $b)
                        );
            }

            $trackId = $respTrack->getRespondentTrackId();
            $batch->setTask('Tracker_CheckTrackTokens', 'chktrck-' . $trackId, $trackId, $userId);
        }

        // Free memory
        $tracker->removeToken($token);
    }
}