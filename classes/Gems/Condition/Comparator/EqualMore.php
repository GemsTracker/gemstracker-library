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
class EqualMore extends ComparatorAbstract {

    public function getDescription(string $subject): string
    {
        return sprintf(
                $this->_('%s >= %s'),
                $subject,
                $this->_options[0]
                );
    }
    
    public function getNumParams(): int
    {
        return 1;
    }

    public function isValid(mixed $value): bool {
        return $value >= $this->_options[0];
    }

}
