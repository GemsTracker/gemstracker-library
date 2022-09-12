<?php

namespace Gems\AuthTfa\Adapter;

use Gems\User\User;
use OTPHP\TOTP;

class TotpAdapter implements OtpAdapterInterface
{
    private readonly TOTP $otp;

    public function __construct(
        array $settings,
        User $user,
    ) {
        $this->otp = TOTP::create(
            $user->getTwoFactorKey(),
            (int)$settings['codeValidSeconds'],
            'sha1',
            (int)$settings['codeLength'],
        );
    }

    public function generateCode(): string
    {
        return $this->otp->now();
    }

    public function verify(string $code): bool
    {
        return $this->otp->verify($code, 0, $this->otp->getPeriod() - 1);
    }
}
