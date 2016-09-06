<?php

/**
 *
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event;

/**
 *
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Sep 6, 2016 3:33:50 PM
 */
interface RespondentChangedEventInterface extends \Gems_Event_EventInterface
{
    /**
     * Process the respondent and return true when data has changed.
     *
     * The event has to handle the actual storage of the changes.
     *
     * @param \Gems_Tracker_Respondent $respondent
     * @param int $userId The current user
     * @return boolean True when something changed
     */
    public function processChangedRespondent(\Gems_Tracker_Respondent $respondent);
}
