<?php

namespace Gems\AuthTfa\Adapter;

use Gems\Cache\HelperAdapter;
use Gems\User\User;
use OTPHP\TOTP;

class TotpAdapter implements OtpAdapterInterface
{
    use ThrottleVerifyTrait;

    private readonly int $codeLength;

    private readonly int $codeValidSeconds;

    public function __construct(
        array $settings,
        private readonly HelperAdapter $throttleCache,
    ) {
        $this->codeLength = (int)$settings['codeLength'];
        $this->codeValidSeconds = (int)$settings['codeValidSeconds'];

        $this->initThrottleVerifyTrait(
            isset($settings['maxVerifyOtpAttempts']) ? (int)$settings['maxVerifyOtpAttempts'] : null,
        );
    }

    private function getThrottleCache(): HelperAdapter
    {
        return $this->throttleCache;
    }

    public function generateSecret(): string
    {
        return TOTP::create(period: $this->codeValidSeconds, digest: 'sha1', digits: $this->codeLength)->getSecret();
    }

    private function createTotp(User $user): TOTP
    {
        return TOTP::create(
            $user->getTwoFactorKeyForAdapter('Totp'),
            $this->codeValidSeconds,
            'sha1',
            $this->codeLength,
        );
    }

    public function generateCode(User $user): string
    {
        return $this->createTotp($user)->now();
    }

    public function verify(User $user, string $code): bool
    {
        $totp = $this->createTotp($user);
        return $totp->verify($code, null, $totp->getPeriod() - 1);
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
