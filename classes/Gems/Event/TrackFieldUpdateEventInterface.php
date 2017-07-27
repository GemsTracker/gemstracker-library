<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Event
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
     * @param \Gems_Tracker_RespondentTrack $respTrack Gems respondent track object
     * @param int   $userId The current userId
     * @return void
     */
    public function processFieldUpdate(\Gems_Tracker_RespondentTrack $respTrack, $userId);
}
