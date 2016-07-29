<?php

/**
 *
 * @package    Gems
 * @subpackage TrackFieldUpdateEventInterface
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrackFieldUpdateEventInterface.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage TrackFieldUpdateEventInterface
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 19-okt-2014 18:20:03
 */
interface Gems_Event_TrackFieldUpdateEventInterface extends \Gems_Event_EventInterface
{
    /**
     * Process the data and do what must be done
     *
     * Storing the changed $values is handled by the calling function.
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack Gems repsondent track object
     * @param int   $userId The current userId
     * @return void
     */
    public function processFieldUpdate(\Gems_Tracker_RespondentTrack $respTrack, $userId);
}
