<?php

/**
 * Utility class to report the result of a merge
 * 
 * @package    Gems
 * @subpackage Modelt
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */


namespace Gems\Model;

/**
 * Utility class to report the result of a merge
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
class MergeResult {
    const FIRST  = 1;   // First record found
    const SECOND = 2;   // Second record found
    const BOTH   = 3;   // Both records found
    const NONE   = 0;   // No record found
    
}
