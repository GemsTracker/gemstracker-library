<?php

namespace Gems\User\Filter;

use Laminas\Filter\FilterInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Note: Currently, this filter assumes the $value has been validated by PhoneNumberValidator. Hence,
 * it cannot be used as an actual Laminas filter. It is intended to be used with setOnSave().
 *
 * @deprecated Replaced by PhoneNumberFormatter as this is not used as a filter
 */
class PhoneNumberFilter implements FilterInterface, \Zend_Filter_Interface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function filter($value)
    {
        if ($value === null) {
            return null;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsedPhone = $phoneUtil->parse($value, $this->config['account']['edit-auth']['defaultRegion']);

        if (!$phoneUtil->isValidNumber($parsedPhone)) {
            throw new \Exception(); // This should have been validated first
        }

        return $phoneUtil->format($parsedPhone, PhoneNumberFormat::E164);
    }
}
