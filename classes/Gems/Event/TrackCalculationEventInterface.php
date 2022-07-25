<?php
/**
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event;

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
interface TrackCalculationEventInterface extends \Gems\Event\EventInterface
{
    /**
     * Process the data and do what must be done
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack \Gems respondent track object
     * @param int   $userId The current userId
     * @return int The number of changed tokens
     */
    public function processTrackCalculation(\Gems\Tracker\RespondentTrack $respTrack, $userId);
}
