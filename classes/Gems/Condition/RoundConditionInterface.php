<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
interface RoundConditionInterface extends ConditionInterface
{
    /**
     * Returns a short text to show in the track definition about this condition
     * 
     * Example:
     * AgeCondition, parameters 10 and 12 could tesult in
     * "Respondent between 10 year and 12 year"
     */
    public function getRoundDisplay($trackId, $roundId);
       
    /**
     * Short text explaining why this condition is not valid for this
     * track and round combination
     * 
     * @see $this->isValid()
     * 
     * @param int $trackId
     * @param int $roundId
     * 
     * @return string
     */
    public function getNotValidReason($trackId, $roundId);
   
    /**
     * Is the condition for this round (token) valid or not
     * 
     * This is the actual implementation of the condition
     * 
     * @param \Gems_Tracker_Token $token
     * 
     * @return bool
     */
    public function isRoundValid(\Gems_Tracker_Token $token);
    
    /**
     * Can this condition be applied to this track/round
     * 
     * This helps to prevent people assigning conditions to tracks/rounds that
     * can never fulfill the condition (trackfield not available for example)
     * 
     * @param type $trackId
     * @param type $roundId
     * 
     * @return bool
     */
    public function isValid($trackId, $roundId);

}