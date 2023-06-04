<?php

/**
 * @package    Gems
 * @subpackage Filter
 * @author     Andries Bezem <abezem@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Filter;

/**
 * @package    Gems
 * @subpackage Filter
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class CleanEmail implements \Zend_Filter_Interface
{
    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws \Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        if ($value === null) {
            return $value;
        }
        // Add elements to be removed from $value to array $cleanup
        $cleanup[] = 'mailto:';

        foreach ($cleanup as $cleanupvalue) {
            $value = str_ireplace($cleanupvalue, '', $value);
        }
        
        // Remove all whitespace characters
        $value = preg_replace('/\s+/', '', $value);
        
        // Return substring between two lookup values
        $startsearch = '<';
        $endsearch = '>';

        $startpos = stripos($value, $startsearch);
        $endpos = stripos($value, $endsearch, $startpos);
        
        if ($startpos !== false && $endpos) {
                $value = trim(substr($value, $startpos + strlen($startsearch), $endpos - $startpos - strlen($startsearch)));
        }
        
        return $value;
    }
}
