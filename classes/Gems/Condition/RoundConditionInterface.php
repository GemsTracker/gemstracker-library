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
     * AgeConditionAbstract, parameters 10 and 12 could result in
     * "Respondent between 10 year and 12 year"
     */
    public function getRoundDisplay($trackId, $roundId);
       
    /**
     * Is the condition for this round (token) valid or not
     * 
     * This is the actual implementation of the condition
     * 
     * @param \Gems\Tracker\Token $token
     * @return bool
     */
    public function isRoundValid(\Gems\Tracker\Token $token);    
}