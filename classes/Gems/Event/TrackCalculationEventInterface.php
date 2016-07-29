<?php
/**
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Track calculation event interface
 *
 * Run on completion of an event
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
interface Gems_Event_TrackCalculationEventInterface extends \Gems_Event_EventInterface
{
    /**
     * Process the data and do what must be done
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack Gems respondent track object
     * @param int   $userId The current userId
     * @return int The number of changed tokens
     */
    public function processTrackCalculation(\Gems_Tracker_RespondentTrack $respTrack, $userId);
}
