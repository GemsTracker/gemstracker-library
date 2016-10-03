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
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.1 29-sep-2016 15:46:03
 */
interface TrackBeforeFieldUpdateEventInterface extends \Gems_Event_EventInterface
{
    /**
     * Process the data and do what must be done
     *
     * Storing the changed $values is handled by the calling function.
     *
     * @param array $fieldData fieldname/codename => value
     * @param \Gems_Tracker_RespondentTrack $respTrack Gems repsondent track object
     * @return array Of changed fields. Codename using items overwrite any key using items
     */
    public function prepareFieldUpdate(array $fieldData, \Gems_Tracker_RespondentTrack $respTrack);
}
