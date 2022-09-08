<?php

namespace Gems\AuthTfa\Adapter;

use Gems\User\User;
use OTPHP\HOTP;

class HotpAdapter implements OtpInterface
{
    private readonly HOTP $otp;

    public function __construct(
        array $config,
        private readonly User $user,
    ) {
        $this->otp = HOTP::create(
            $user->getTwoFactorKey(),
            $user->getOtpCount(),
            'sha1',
            (int)$config['codeLength'],
        );
    }

    public function generateCode(): string
    {
        $code = $this->otp->at($this->user->getOtpCount());

        $this->user->incrementOtpCount();

        return $code;
    }

    public function verify(string $code): bool
    {
        return $this->otp->verify($code, $this->user->getOtpCount(), 0);
    }
}
