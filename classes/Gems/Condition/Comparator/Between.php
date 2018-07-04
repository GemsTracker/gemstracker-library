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
class Between extends ComparatorAbstract {

    public function getDescription($subject) {
        return sprintf(
                $this->_('%s <= %s <= %s'),
                $this->_options[0],
                $subject,
                $this->_options[1]
                );
    }
    
    public function getNumParams()
    {
        return 2;
    }

    public function isValid($value) {
        return $this->_options[0] <= $value && $value <= $this->_options[1];
    }

}
