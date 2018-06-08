<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition\Comparator;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
interface ComparatorInterface
{
    /**
     * @param array $options
     */
    public function __construct($options = array());
    
    /**
     * Return a readable desription, using the given subject and configured options
     * 
     * @param string $subject
     */
    public function getDescription($subject);
    
    /**
     * IS the comparision valid?
     * 
     * Settings should already be in place by the construtor.
     * 
     * @param int $value The id of the condition
     * 
     * @return bool
     */
    public function isValid($value);

}