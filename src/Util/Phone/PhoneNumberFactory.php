<?php

namespace Gems\Util\Phone;

use libphonenumber\PhoneNumberUtil;

class PhoneNumberFactory
{
    private readonly string|null $defaultRegion;

    public function __construct(
        array $config,
    )
    {
        $this->defaultRegion = $config['account']['edit-auth']['defaultRegion'] ?? null;
    }

    public function fromString(string $raw): PhoneNumber
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsedPhone = $phoneUtil->parse($raw, $this->defaultRegion);
        return new PhoneNumber($parsedPhone, $phoneUtil);
    }
}