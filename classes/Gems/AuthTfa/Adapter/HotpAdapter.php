<?php

namespace Gems\AuthTfa\Adapter;

use DateInterval;
use Gems\User\User;
use OTPHP\HOTP;

class HotpAdapter implements OtpAdapterInterface
{
    private readonly HOTP $otp;

    private readonly int $codeLength;

    private int $codeValidSeconds = 300;

    public function __construct(
        array $settings,
        private readonly User $user,
    ) {
        $this->codeLength = (int)$settings['codeLength'];

        $this->otp = HOTP::create(
            $user->getTwoFactorKey(),
            $user->getOtpCount(),
            'sha1',
            $this->codeLength,
        );

        if (isset($settings['codeValidSeconds'])) {
            $this->codeValidSeconds = (int)$settings['codeValidSeconds'];
        }
    }

    public function generateCode(): string
    {
        $this->user->incrementOtpCount();

        return $this->otp->at($this->user->getOtpCount());
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

    public function getCodeValidSeconds(): int
    {
        return $this->codeValidSeconds;
    }

    public function getMinLength(): int
    {
        return $this->codeLength;
    }

    public function getMaxLength(): int
    {
        return $this->codeLength;
    }
}
