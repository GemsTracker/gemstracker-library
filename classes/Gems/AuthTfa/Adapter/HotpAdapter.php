<?php

namespace Gems\AuthTfa\Adapter;

use DateInterval;
use Gems\Cache\HelperAdapter;
use Gems\User\User;
use OTPHP\HOTP;

class HotpAdapter implements OtpAdapterInterface
{
    use ThrottleVerifyTrait;

    private readonly HOTP $otp;

    private readonly int $codeLength;

    private int $codeValidSeconds = 300;

    public function __construct(
        array $settings,
        private readonly HelperAdapter $throttleCache,
    ) {
        $this->codeLength = (int)$settings['codeLength'];

        if (isset($settings['codeValidSeconds'])) {
            $this->codeValidSeconds = (int)$settings['codeValidSeconds'];
        }

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
        return HOTP::create(digest: 'sha1', digits: $this->codeLength)->getSecret();
    }

    private function createHotp(User $user): HOTP
    {
        return HOTP::create(
            $user->getTwoFactorKeyForAdapter('Hotp'),
            $user->getOtpCount(),
            'sha1',
            $this->codeLength,
        );
    }

    public function generateCode(User $user): string
    {
        $user->incrementOtpCount();

        return $this->createHotp($user)->at($user->getOtpCount());
    }

    public function verify(User $user, string $code): bool
    {
        $currentOtpRequested = $user->getOtpRequested();

        $otpValidUntil = $currentOtpRequested->add(new DateInterval('PT' . $this->codeValidSeconds . 'S'));

        if ($otpValidUntil->getTimestamp() < time()) {
            return false;
        }

        return $this->createHotp($user)->verify($code, $user->getOtpCount(), 0);
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
