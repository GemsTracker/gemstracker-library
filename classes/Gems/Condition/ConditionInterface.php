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
interface ConditionInterface
{
    /**
     * Load the object from a data array
     *
     * @param array $data
     */
    public function exchangeArray(array $data);
               
    /**
     * Return a help text for this filter.
     * 
     * It can be multiline but should not use formatting other than line endings.
     * 
     * @return string
     */
    public function getHelp();
    
    /**
     * Get the settings for the gcon_condition_textN fields 
     *
     * @return array textN => array(modelFieldName => fieldValue)
     */
    public function getModelFields($context, $new);    
    
    /**
     * Get the name to use in dropdowns for this condition
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Short text explaining why this condition is not valid
     * 
     * @see $this->isValid()
     * 
     * @param int $value The id of the condition
     * @param array $context The other variables in the form
     * 
     * @return string
     */
    public function getNotValidReason($value, $context);
    
    /**
     * Can this condition be applied to this track/round
     * 
     * This helps to prevent people assigning conditions to places where the
     * condition can never be fulfilled (trackfield not available for example)
     * 
     * @param int $value The id of the condition
     * @param array $context The other variables in the form
     * 
     * @return bool
     */
    public function isValid($value, $context);

}