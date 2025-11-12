<?php

namespace Gems\Util\Phone;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

class PhoneNumber
{
    public function __construct(
        public readonly \libphonenumber\PhoneNumber $phoneNumber,
        private readonly PhoneNumberUtil $phoneUtil,
    )
    {
    }

    public function format(int $format = PhoneNumberFormat::E164): string
    {
        return $this->phoneUtil->format($this->phoneNumber, $format);
    }

    public function getCountryCode():int
    {
        return $this->phoneNumber->getCountryCode();
    }

    public function getNumberType():int
    {
        return $this->phoneUtil->getNumberType($this->phoneNumber);
    }

    public function isFixedLine(): bool
    {
        $type = $this->getNumberType();
        return $type === PhoneNumberType::FIXED_LINE;
    }

    public function isMobile(): bool
    {
        $type = $this->getNumberType();
        return $type === PhoneNumberType::MOBILE;
    }

    public function isValid(): bool
    {
        return $this->phoneUtil->isValidNumber($this->phoneNumber);
    }

    public function mightBeFixedLine(): bool
    {
        $type = $this->getNumberType();
        return $type === PhoneNumberType::FIXED_LINE || $type === PhoneNumberType::FIXED_LINE_OR_MOBILE;
    }

    public function mightBeMobile(): bool
    {
        $type = $this->getNumberType();
        return $type === PhoneNumberType::MOBILE || $type === PhoneNumberType::FIXED_LINE_OR_MOBILE;
    }


}