<?php

namespace Gems\AuthTfa\Adapter;

use DateInterval;
use Gems\User\User;
use OTPHP\HOTP;

class HotpAdapter implements OtpAdapterInterface
{
    private readonly HOTP $otp;

    private int $codeValidSeconds = 300;

    public function __construct(
        array $settings,
        private readonly User $user,
    ) {
        $this->otp = HOTP::create(
            $user->getTwoFactorKey(),
            $user->getOtpCount(),
            'sha1',
            (int)$settings['codeLength'],
        );

        if (isset($settings['codeValidSeconds'])) {
            $this->codeValidSeconds = (int)$settings['codeValidSeconds'];
        }
    }

    public function generateCode(): string
    {
        $code = $this->otp->at($this->user->getOtpCount());

        $this->user->incrementOtpCount();

        return $code;
    }

    public function verify(string $code): bool
    {
        $currentOtpRequested = $this->user->getOtpRequested();

        $otpValidUntil = $currentOtpRequested->add(new DateInterval('PT' . $this->codeValidSeconds . 'S'));

        if ($otpValidUntil->getTimestamp() < time()) {
            return false;
        }

        return $this->otp->verify($code, $this->user->getOtpCount(), 0);
    }
}
