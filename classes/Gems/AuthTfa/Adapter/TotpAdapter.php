<?php

namespace Gems\AuthTfa\Adapter;

use Gems\User\User;
use OTPHP\TOTP;

class TotpAdapter implements OtpInterface
{
    private readonly TOTP $otp;

    public function __construct(
        array $config,
        private readonly User $user,
    ) {
        $this->otp = TOTP::create(
            $user->getTwoFactorKey(),
            (int)$config['codeValidSeconds'],
            'sha1',
            (int)$config['codeLength'],
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
