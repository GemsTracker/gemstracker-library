<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event;

/**
 *
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 19-okt-2014 18:20:03
 */
interface TrackFieldUpdateEventInterface extends \Gems\Event\EventInterface
{
    /**
     * Process the data and do what must be done
     *
     * Storing the changed $values is handled by the calling function.
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack \Gems respondent track object
     * @param int   $userId The current userId
     * @return void
     */
    public function processFieldUpdate(\Gems\Tracker\RespondentTrack $respTrack, $userId);
}
