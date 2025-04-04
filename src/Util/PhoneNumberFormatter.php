<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Util;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * @package    Gems
 * @subpackage Util
 * @since      Class available since version 1.0
 */
class PhoneNumberFormatter
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function __invoke($value)
    {
        return $value ? $this->format($value) : $value;
    }

    public function format($value)
    {
        if ($value === null) {
            return null;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsedPhone = $phoneUtil->parse($value, $this->config['account']['edit-auth']['defaultRegion']);

        if (!$phoneUtil->isValidNumber($parsedPhone)) {
            return $value;
            //throw new \Exception("An invalid phone number was entered."); // This should have been validated first
        }

        return $phoneUtil->format($parsedPhone, PhoneNumberFormat::E164);
    }
}