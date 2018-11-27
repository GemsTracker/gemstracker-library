<?php

/**
 *
 * @package    Gems
 * @subpackage Condition\Comparator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Condition\Comparator;

/**
 *
 * @package    Gems
 * @subpackage Condition\Comparator
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 26-Nov-2018 17:19:43
 */
class Contains extends ComparatorAbstract
{
    /**
     * Return a readable description, using the given subject and configured options
     *
     * @param string $subject
     * @return string
     */
    public function getDescription($subject)
    {
        return sprintf(
                $this->_('%s contains %s'),
                $subject,
                $this->_options[0]
                );
    }

    /**
     * The number of parameters this comparator expects
     *
     * @return int Less than 5
     */
    public function getNumParams()
    {
        return 1;
    }

    /**
     * IS the comparison valid?
     *
     * Settings should already be in place by the construtor.
     *
     * @param mixed $value The id of the condition
     * @return bool
     */
    public function isValid($value)
    {
        foreach ((array) $value as $val) {
            if (\MUtil_String::contains($this->_options[0], $val)) {
                return true;
            }
        }
        return false;
    }

}
