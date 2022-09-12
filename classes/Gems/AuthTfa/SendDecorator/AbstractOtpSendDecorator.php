<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\AuthTfa\Adapter\OtpAdapterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractOtpSendDecorator implements OtpAdapterInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly OtpAdapterInterface $otp,
    ) {
    }

    public function generateCode(): string
    {
        return $this->otp->generateCode();
    }

    public function verify(string $code): bool
    {
        return $this->otp->verify($code);
    }
}
