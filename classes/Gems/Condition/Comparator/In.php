<?php

/**
 *
 * @package    Gems
 * @subpackage Condition\Comparator
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition\Comparator;

/**
 *
 * @package    Gems
 * @subpackage Condition\Comparator
 * @copyright  Copyright (c) 2018, Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class In extends ComparatorAbstract
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
                $this->_('%s in the list %s'),
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
    
    public function getParamDescriptions() {
        return [
            $this->_('Separate multiple values with a vertical bar (|)')
        ];
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
        $validOptions = explode('|', $this->_options[0]);
        return in_array($value, $validOptions);
    }

}
