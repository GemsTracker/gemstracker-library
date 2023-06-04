<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\Cache\HelperAdapter;
use Gems\Cache\RateLimiter;
use Gems\User\User;

trait ThrottleSendTrait
{
    private RateLimiter $sendRateLimiter;

    /**
     * @var int Maximum otp send attempts per time period: Number of attempts
     */
    private int $maxSendAttempts = 3;

    /**
     * @var int Maximum otp send attempts per time period: Period in seconds. Default per day (24 * 60 * 60)
     */
    private int $maxSendPeriod = 86400;

    abstract private function getThrottleCache(): HelperAdapter;

    protected function initThrottleSendTrait(?int $maxSendAttempts = null, ?int $maxSendPeriod = null): void
    {
        $this->sendRateLimiter = new RateLimiter($this->getThrottleCache());

        if ($maxSendAttempts !== null) {
            $this->maxSendAttempts = $maxSendAttempts;
        }

        if ($maxSendPeriod !== null) {
            $this->maxSendPeriod = $maxSendPeriod;
        }
    }

    private function getMaxSendOtpKey(User $user): string
    {
        return sha1($user->getUserId()) . '_otp_send_max';
    }

    protected function canSendOtp(User $user): bool
    {
        return !$this->sendRateLimiter->tooManyAttempts($this->getMaxSendOtpKey($user), $this->maxSendAttempts);
    }

    protected function hitSendOtp(User $user): void
    {
        $this->sendRateLimiter->hit($this->getMaxSendOtpKey($user), $this->maxSendPeriod);
    }
}
