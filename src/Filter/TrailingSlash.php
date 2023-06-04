<?php

/**
 *
 * @package    Gems
 * @subpackage Filter
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Filter;

/**
 * Removes slashes from both the end of string and words
 *
 * @package    Gems
 * @subpackage Filter
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class TrailingSlash implements \Zend_Filter_Interface
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
        $values = explode(' ', $value);

        foreach ($values as &$val) {
            if (substr($val, -1) === '/') {
                $val = substr($val, 0, -1);
            }
        }

        return implode(' ', $values);
    }
}
