<?php

/**
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Checks a respondentTrack for changes, mostly started by \Gems_Task_ProcessTokenCompletion
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class Gems_Task_Tracker_CheckTrackTokens extends \MUtil_Task_TaskAbstract
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
    public function execute($respTrackData = null, $userId = null)
    {
        $batch     = $this->getBatch();
        $tracker   = $this->loader->getTracker();
        $respTrack = $tracker->getRespondentTrack($respTrackData);

        $i = $batch->addToCounter('checkedRespondentTracks');

        if ($result = $respTrack->checkTrackTokens($userId)) {
            $a = $batch->addToCounter('tokenDateCauses');
            $b = $batch->addToCounter('tokenDateChanges', $result);
            $batch->setMessage('tokenDateChanges',
                    sprintf($this->_('%2$d token date changes in %1$d tracks.'), $a, $b)
                    );
        }

        $batch->setMessage('checkedRespondentTracks', sprintf($this->_('Checked %d tracks.'), $i));
    }
}