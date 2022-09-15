<?php

namespace Gems\AuthTfa\Adapter;

use Gems\Cache\HelperAdapter;
use Gems\User\User;
use OTPHP\TOTP;

class TotpAdapter implements OtpAdapterInterface
{
    use ThrottleVerifyTrait;

    private readonly TOTP $otp;

    private readonly int $codeLength;

    private readonly int $codeValidSeconds;

    public function __construct(
        array $settings,
        User $user,
        private readonly HelperAdapter $throttleCache,
    ) {
        $this->codeLength = (int)$settings['codeLength'];
        $this->codeValidSeconds = (int)$settings['codeValidSeconds'];

        $this->otp = TOTP::create(
            $user->getTwoFactorKey(),
            $this->codeValidSeconds,
            'sha1',
            $this->codeLength,
        );

        $this->initThrottleVerifyTrait(
            isset($settings['maxVerifyOtpAttempts']) ? (int)$settings['maxVerifyOtpAttempts'] : null,
        );
    }

    private function getThrottleCache(): HelperAdapter
    {
        return $this->throttleCache;
    }

    public function generateCode(): string
    {
        return $this->otp->now();
    }

    public function verify(string $code): bool
    {
        return $this->otp->verify($code, null, $this->otp->getPeriod() - 1);
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
