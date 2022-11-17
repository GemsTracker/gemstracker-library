<?php
/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent;

use Gems\Tracker\RespondentTrack;
use Gems\Tracker\Token;

/**
 * After a round has changed/completed run this code.
 *
 * As it passes \Gems\Tracker objects it is more powerfull than survey completion events,
 * but then the code may be more difficult to implement.
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
interface RoundChangedEventInterface extends EventInterface
{
    /**
     * Process the token and return true when data has changed.
     *
     * The event has to handle the actual storage of the changes.
     *
     * @param Token $token
     * @param RespondentTrack $respondentTrack
     * @param int $userId The current user
     * @return int The number of tokens changed by this event
     */
    public function processChangedRound(Token $token, RespondentTrack $respondentTrack, $userId);
}
