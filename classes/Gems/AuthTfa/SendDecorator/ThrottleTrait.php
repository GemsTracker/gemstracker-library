<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\Cache\HelperAdapter;
use Gems\Cache\RateLimiter;
use Gems\User\User;

trait ThrottleTrait
{
    private RateLimiter $rateLimiter;

    /**
     * @var int Maximum otp send attempts per time period: Number of attempts
     */
    private int $maxSendAttempts = 3;

    /**
     * @var int Maximum otp send attempts per time period: Period in seconds. Default per day (24 * 60 * 60)
     */
    private int $maxSendPeriod = 86400;

    abstract private function getThrottleCache(): HelperAdapter;

    protected function initThrottleTrait(?int $maxSendAttempts = null, ?int $maxSendPeriod = null): void
    {
        $this->rateLimiter = new RateLimiter($this->getThrottleCache());

        if ($maxSendAttempts !== null) {
            $this->maxSendAttempts = $maxSendAttempts;
        }

        if ($maxSendPeriod !== null) {
            $this->maxSendPeriod = $maxSendPeriod;
        }
    }

    private function getMaxSendOtpKey(User $user): string
    {
        return sha1($user->getUserId()) . '_otp_max';
    }

    protected function canSendOtp(User $user): bool
    {
        return !$this->rateLimiter->tooManyAttempts($this->getMaxSendOtpKey($user), $this->maxSendAttempts);
    }

    protected function hitSendOtp(User $user): void
    {
        $this->rateLimiter->hit($this->getMaxSendOtpKey($user), $this->maxSendPeriod);
    }
}
