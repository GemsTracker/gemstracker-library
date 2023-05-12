<?php

/**
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent;

use Gems\Tracker\RespondentTrack;

/**
 * Track completed event interface
 *
 * Run on completion of an event
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.1
 */
interface TrackCompletedEventInterface extends EventInterface
{
    /**
     * Process the data and do what must be done
     *
     * Storing the changed $values is handled by the calling function.
     *
     * @param RespondentTrack $respTrack \Gems respondent track object
     * @param array $values The values to update the track with, before they were saved
     * @param int   $userId The current userId
     * @return void
     */
    public function processTrackCompletion(RespondentTrack $respTrack, &$values, $userId): void;
}
