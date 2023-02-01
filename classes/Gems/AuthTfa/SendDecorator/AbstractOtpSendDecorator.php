<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\AuthTfa\Adapter\OtpAdapterInterface;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractOtpSendDecorator implements OtpAdapterInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly OtpAdapterInterface $otp,
    ) {
    }

    public function generateSecret(): string
    {
        return $this->otp->generateSecret();
    }

    public function generateCode(): string
    {
        return $this->otp->generateCode();
    }

    public function verify(string $code): bool
    {
        return $this->otp->verify($code);
    }

    public function getCodeValidSeconds(): int
    {
        return $this->otp->getCodeValidSeconds();
    }

    public function getMinLength(): int
    {
        return $this->otp->getMinLength();
    }

    public function getMaxLength(): int
    {
        return $this->otp->getMaxLength();
    }

    public function canVerifyOtp(User $user): bool
    {
        return $this->otp->canVerifyOtp($user);
    }

    public function hitVerifyOtp(User $user): void
    {
        $this->otp->hitVerifyOtp($user);
    }
}
