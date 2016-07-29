<?php
/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * After a round has changed/completed run this code.
 *
 * As it passes \Gems_Tracker objects it is more powerfull than survey completion events,
 * but then the code may be more difficult to implement.
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
interface Gems_Event_RoundChangedEventInterface extends \Gems_Event_EventInterface
{
    /**
     * Process the token and return true when data has changed.
     *
     * The event has to handle the actual storage of the changes.
     *
     * @param \Gems_Tracker_Token $token
     * @param \Gems_Tracker_RespondentTrack $respondentTrack
     * @param int $userId The current user
     * @return int The number of tokens changed by this event
     */
    public function processChangedRound(\Gems_Tracker_Token $token, \Gems_Tracker_RespondentTrack $respondentTrack, $userId);
}
