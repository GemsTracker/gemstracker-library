<?php

namespace Gems\User\Filter;

class DutchPhonenumberFilter implements \Zend_Filter_Interface
{
    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        $value = preg_replace("/\([^)]+\)/", "", $value);
        $numeric = preg_replace("/[^0-9]/", "", $value);

        if (strpos($numeric, '00') === 0 && (strlen($numeric) === 12 || strlen($numeric) === 13)) {
            $numeric = ltrim($numeric, '0');
        }

        if ((strpos($numeric, '06') === 0 && strlen($numeric) === 10) || (strpos($numeric, '6') && strlen($numeric) === 9)) {
            $numeric = '31' .  ltrim($numeric, '0');
        }

        return (string)$numeric;
    }
}
