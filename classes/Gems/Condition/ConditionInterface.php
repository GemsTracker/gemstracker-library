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
     * Get the settings for the gcon_condition_textN fields without the 
     * gcon_connection prefix
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

}