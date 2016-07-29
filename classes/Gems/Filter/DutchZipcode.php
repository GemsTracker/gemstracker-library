<?php

/**
 * @package    Gems
 * @subpackage Filter
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * @package    Gems
 * @subpackage Filter
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Filter_DutchZipcode implements \Zend_Filter_Interface
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
        // perform some transformation upon $value to arrive on $valueFiltered
        $valueFiltered = strtoupper(trim($value));
        if (strlen($valueFiltered) == 6) {
            if (preg_match('/\d{4,4}[A-Z]{2,2}/', $valueFiltered)) {
                $valueFiltered = substr_replace($valueFiltered, ' ', 4, 0);
            }
        }

        return $valueFiltered;
    }
}
