<?php

namespace Gems\AuthTfa\Decorator;

use Gems\AuthTfa\Adapter\OtpInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractOtp
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly OtpInterface $otp,
    ) {
    }
}
