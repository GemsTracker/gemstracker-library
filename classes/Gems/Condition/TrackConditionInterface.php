<?php

/**
 *
 *
 * @package    Gems\Condition
 * @subpackage 
 * @author     mjong
 * @license    Not licensed, do not copy
 */

namespace Gems\Condition;

/**
 *
 * @package    Gems\Condition
 * @subpackage 
 * @since      Class available since version 1.8.8
 */
interface TrackConditionInterface extends ConditionInterface
{
    /**
     * Returns a short text to show in the track definition about this condition
     *
     * Example:
     * AgeConditionAbstract, parameters 10 and 12 could result in
     * "Respondent between 10 year and 12 year"
     */
    public function getTrackDisplay($trackId);

    /**
     * Is the condition for this round (token) valid or not
     *
     * This is the actual implementation of the condition
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack     *
     * @return bool
     */
    public function isTrackValid(\Gems_Tracker_RespondentTrack $respTrack);
}